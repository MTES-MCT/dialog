<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProConnectAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UrlGeneratorInterface $urlGenerator,
        private CommandBusInterface $commandBus,
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
            $originalState = $session->get('proconnect_state');

            if (empty($receivedState) || $receivedState !== $originalState) {
                throw new AuthenticationException('Invalid state parameter');
            }

            // Vérification du code
            $code = $request->query->get('code');
            if (empty($code)) {
                throw new AuthenticationException('No authorization code provided');
            }

            // Récupération du token
            $response = $this->httpClient->request('POST', $this->proConnectDomain . '/api/v2/token', [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->proConnectClientId,
                    'client_secret' => $this->proConnectClientSecret,
                    'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
            ]);

            $tokenData = $response->toArray();

            if (!isset($tokenData['access_token']) || !isset($tokenData['id_token'])) {
                throw new AuthenticationException('Invalid token response');
            }

            // Vérification de l'expiration du token
            if ($this->isTokenExpired($tokenData)) {
                throw new AuthenticationException('Token expired');
            }

            $accessToken = $tokenData['access_token'];
            $idToken = $tokenData['id_token'];

            // Stockage de l'id_token pour la déconnexion
            $session->set('proconnect_id_token', $idToken);

            // Vérification du nonce dans l'id_token
            $decodedToken = $this->decodeAndVerifyToken($idToken);
            $savedNonce = $session->get('proconnect_nonce');

            if (!isset($decodedToken['nonce']) || $decodedToken['nonce'] !== $savedNonce) {
                throw new AuthenticationException('Invalid nonce in ID token');
            }

            // Récupération des infos utilisateur
            $userInfo = $this->httpClient->request('GET', $this->proConnectDomain . '/api/v2/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ])->toArray();

            if (!isset($userInfo['email'])) {
                throw new AuthenticationException('Email not found in user info');
            }

            return new SelfValidatingPassport(
                new UserBadge($userInfo['email'], function (string $email) use ($userInfo) {
                    return $this->commandBus->handle(
                        new CreateProConnectUserCommand(
                            $email,
                            $userInfo,
                        ),
                    );
                }),
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentication failed: ' . $e->getMessage());
        }
    }

    private function isTokenExpired(array $tokenData): bool
    {
        return !isset($tokenData['expires_in']) || $tokenData['expires_in'] <= 0;
    }

    private function decodeAndVerifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->proConnectClientSecret, 'HS256'));

            return (array) $decoded;
        } catch (\Exception $e) {
            throw new AuthenticationException('JWT verification failed: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();
        $session->remove('proconnect_state');
        $session->remove('proconnect_nonce');

        return new RedirectResponse($this->urlGenerator->generate('app_landing'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();
        $session->remove('proconnect_state');
        $session->remove('proconnect_nonce');

        return new RedirectResponse(
            $this->urlGenerator->generate('app_login', [
                'error' => $exception->getMessageKey(),
            ]),
        );
    }
}
