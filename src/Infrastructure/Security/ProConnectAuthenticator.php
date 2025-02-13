<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
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
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'pro_connect_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get('code');

        // Récupération du token
        $response = $this->httpClient->request('POST', 'https://auth.entreprise.api.gouv.fr/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->proConnectClientId,
                'client_secret' => $this->proConnectClientSecret,
                'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ]);

        $token = $response->toArray()['access_token'];

        // Récupération des infos utilisateur
        $userInfo = $this->httpClient->request('GET', 'https://auth.entreprise.api.gouv.fr/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ])->toArray();

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
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_landing'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('app_login', [
                'error' => $exception->getMessageKey(),
            ]),
        );
    }
}
