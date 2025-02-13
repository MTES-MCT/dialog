<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProConnectLoginController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $proConnectClientId,
    ) {
    }

    #[Route('/proconnect/auth', name: 'pro_connect_start')]
    public function proConnectLogin(): RedirectResponse
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->proConnectClientId,
            'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'scope' => 'openid email profile organization',
            'state' => bin2hex(random_bytes(10)),
        ];

        return new RedirectResponse(
            'https://auth.entreprise.api.gouv.fr/authorize?' . http_build_query($params),
        );
    }

    #[Route('/proconnect/auth/callback', name: 'pro_connect_callback')]
    public function proConnectCallback()
    {
        // Géré par l'authenticator
    }
}
