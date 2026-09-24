<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Authenticator;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Se référer à la documentation technique
 * https://github.com/numerique-gouv/proconnect-documentation/blob/main/doc_fs/implementation_technique.md
 */
class ProConnectAuthenticator extends AbstractAuthenticator
{
    // Clés de signature ProConnect, mises en cache le temps de la requête
    private ?array $signingKeys = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private UrlGeneratorInterface $urlGenerator,
        private CommandBusInterface $commandBus,
        private TranslatorInterface $translator,
        private string $proConnectClientId,
        private string $proConnectClientSecret,
        private string $proConnectDomain,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'pro_connect_callback';
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $session = $request->getSession();

            // Vérification du state
            $receivedState = $request->query->get('state');
            $originalState = $session->get('oauth2_state');

            if (empty($receivedState) || $receivedState !== $originalState) {
                throw new AuthenticationException('Invalid state parameter');
            }

            // Vérification du code
            $code = $request->query->get('code');
            if (empty($code)) {
                throw new AuthenticationException('No authorization code provided');
            }

            // Échange du code contre un token
            $tokenData = $this->exchangeCodeForToken($code);
            // Vérification de la réponse du token
            if (!isset($tokenData['access_token']) || !isset($tokenData['id_token'])) {
                throw new AuthenticationException('Invalid token response');
            }

            // Vérification cryptographique de l'id_token : signature via la JWKS de
            // ProConnect, expiration, émetteur et audience
            $idTokenPayload = $this->decodeAndVerifyJwt($tokenData['id_token']);

            // Vérification du nonce (protection anti-rejeu OIDC) : la claim de
            // l'id_token doit correspondre au nonce généré avant la redirection
            $expectedNonce = $session->get('oauth2_nonce');
            if (empty($expectedNonce) || !hash_equals($expectedNonce, (string) ($idTokenPayload['nonce'] ?? ''))) {
                throw new AuthenticationException('Invalid nonce');
            }

            // Exige que l'utilisateur se soit authentifié en double facteur côté ProConnect.
            // Le claim `amr` (demandé via le paramètre `claims` à l'authorize) doit contenir
            // la valeur `mfa`, sinon on refuse la connexion.
            $amr = (array) ($idTokenPayload['amr'] ?? []);
            if (!\in_array('mfa', $amr, true)) {
                throw new CustomUserMessageAuthenticationException('login.proconnect.two_factor_required');
            }

            // Stockage de l'id_token pour la déconnexion
            $session->set('id_token', $tokenData['id_token']);

            // Récupération des infos utilisateur (JWT signé, vérifié également)
            $userInfo = $this->getUserInfo($tokenData['access_token']);

            // Le sujet du userinfo doit correspondre à celui de l'id_token (spéc. OIDC)
            if (!isset($userInfo['sub']) || $userInfo['sub'] !== ($idTokenPayload['sub'] ?? null)) {
                throw new AuthenticationException('UserInfo subject mismatch');
            }

            // Vérification des données utilisateur
            if (!isset($userInfo['email'])) {
                throw new AuthenticationException('Email not found in user info');
            }

            // Creation du user proConnect s'il n'existe pas
            $this->commandBus->handle(
                new CreateProConnectUserCommand(
                    $userInfo['email'],
                    $userInfo['given_name'],
                    $userInfo['usual_name'],
                    $userInfo['siret'],
                ),
            );

            return new SelfValidatingPassport(new UserBadge($userInfo['email']));
        } catch (CustomUserMessageAuthenticationException $e) {
            // Message déjà destiné à l'utilisateur (ex. 2FA requise) : on le laisse remonter tel quel.
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function exchangeCodeForToken(string $code): array
    {
        $response = $this->httpClient->request('POST', $this->proConnectDomain . '/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->proConnectClientId,
                'client_secret' => $this->proConnectClientSecret,
                'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ]);

        return $response->toArray();
    }

    private function getUserInfo(string $accessToken): array
    {
        $response = $this->httpClient->request('GET', $this->proConnectDomain . '/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/jwt',
            ],
        ]);
        $jwt = $response->getContent();

        return $this->decodeAndVerifyJwt($jwt);
    }

    /**
     * Décode un JWT émis par ProConnect en vérifiant sa signature (via la JWKS),
     * son expiration (exp), son émetteur (iss) et son audience (aud).
     */
    private function decodeAndVerifyJwt(string $jwt): array
    {
        // Tolérance de 30 s sur exp/iat/nbf pour absorber une dérive d'horloge
        // entre notre serveur et ProConnect
        JWT::$leeway = 30;

        // La signature et l'expiration sont vérifiées par JWT::decode
        $payload = (array) JWT::decode($jwt, $this->getSigningKeys());

        if (($payload['iss'] ?? null) !== $this->proConnectDomain) {
            throw new AuthenticationException('Invalid token issuer');
        }

        if (!\in_array($this->proConnectClientId, (array) ($payload['aud'] ?? []), true)) {
            throw new AuthenticationException('Invalid token audience');
        }

        return $payload;
    }

    private function getSigningKeys(): array
    {
        if ($this->signingKeys === null) {
            $jwks = $this->httpClient->request('GET', $this->proConnectDomain . '/jwks')->toArray();
            $this->signingKeys = JWK::parseKeySet($jwks, 'RS256');
        }

        return $this->signingKeys;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $session->remove('oauth2_state');
        $session->remove('oauth2_nonce');

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $session->remove('oauth2_state');
        $session->remove('oauth2_nonce');

        // Les CustomUserMessageAuthenticationException portent un message destiné à
        // l'utilisateur (clé de traduction) ; les autres restent des messages techniques.
        $message = $exception instanceof CustomUserMessageAuthenticationException
            ? $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
            : $exception->getMessage();

        $session->getFlashBag()->add('error', $message);

        return new RedirectResponse(
            $this->urlGenerator->generate('app_login'),
        );
    }
}
