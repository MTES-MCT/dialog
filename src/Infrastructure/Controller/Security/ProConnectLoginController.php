<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProConnectLoginController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $proConnectClientId,
        private string $proConnectDomain,
    ) {
    }

    #[Route('/proconnect/auth', name: 'pro_connect_start')]
    public function proConnectLogin(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        $request->getSession()->set('proconnect_state', $state);
        $request->getSession()->set('proconnect_nonce', $nonce);

        $params = [
            'response_type' => 'code',
            'client_id' => $this->proConnectClientId,
            'scope' => 'openid email profile organization',
            'state' => $state,
            'nonce' => $nonce,
            'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new RedirectResponse(
            \sprintf('%s/api/v2/authorize?%s', $this->proConnectDomain, http_build_query($params)),
        );
    }

    #[Route('/proconnect/auth/callback', name: 'pro_connect_callback')]
    public function proConnectCallback(): void
    {
        // Géré par l'authenticator
    }

    #[Route('/proconnect/logout', name: 'pro_connect_logout')]
    public function logout(Request $request): RedirectResponse
    {
        $idToken = $request->getSession()->get('proconnect_id_token');

        $params = [
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $request->getSession()->remove('proconnect_id_token');

        return new RedirectResponse(
            \sprintf('%s/api/v2/session/end?%s', $this->proConnectDomain, http_build_query($params)),
        );
    }
}
