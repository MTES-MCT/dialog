<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Security\Provider\UserProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Se référer à la documentation technique
 * https://github.com/numerique-gouv/proconnect-documentation/blob/main/doc_fs/implementation_technique.md
 */
class ProConnectAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UrlGeneratorInterface $urlGenerator,
        private UserProvider $userProvider,
        private TokenStorageInterface $tokenStorage,
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

            // Stockage de l'id_token pour la déconnexion
            $session->set('id_token', $tokenData['id_token']);

            // Récupération des infos utilisateur
            $userInfo = $this->getUserInfo($tokenData['access_token']);

            // Vérification des données utilisateur
            if (!isset($userInfo['email'])) {
                throw new AuthenticationException('Email not found in user info');
            }

            // Création du Passport
            return new SelfValidatingPassport(
                new UserBadge($userInfo['email'], function (string $email) use ($userInfo) {
                    return $this->userProvider->loadUserByIdentifier($email, $userInfo);
                }),
            );
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
                'Accept' => 'application/json',
            ],
        ]);
        $jwt = $response->getContent();

        // Decodage du JWT
        $parts = explode('.', $jwt);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return $payload;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->remove('oauth2_state');
        $request->getSession()->remove('oauth2_nonce');

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->remove('oauth2_state');
        $request->getSession()->remove('oauth2_nonce');
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());

        return new RedirectResponse(
            $this->urlGenerator->generate('app_login'),
        );
    }
}
