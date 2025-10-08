<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Authenticator;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class ApiClientAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        $hasId = $request->headers->has('X-Client-Id');
        $hasSecret = $request->headers->has('X-Client-Secret');

        return str_starts_with($request->getPathInfo(), '/api') && $hasId && $hasSecret;
    }

    public function authenticate(Request $request): Passport
    {
        $clientId = $request->headers->get('X-Client-Id');
        $clientSecret = $request->headers->get('X-Client-Secret');

        return new Passport(
            new UserBadge($clientId),
            new PasswordCredentials($clientSecret),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
