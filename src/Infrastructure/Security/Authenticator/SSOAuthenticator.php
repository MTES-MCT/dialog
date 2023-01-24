<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Authenticator;

use App\Infrastructure\Security\Provider\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class SSOAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private AuthenticationSuccessHandler $successHandler,
        private AuthenticationFailureHandler $failureHandler,
        private UserProvider $userProvider,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->request->has('token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->request->get('token');

        if (null === $token) {
            throw new TokenNotFoundException('No user token provided');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $token,
                fn ($token) => $this->userProvider->loadUserByIdentifier($token),
            ),
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }
}
