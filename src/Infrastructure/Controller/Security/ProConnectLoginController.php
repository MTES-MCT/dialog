<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class ProConnectLoginController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private string $proConnectClientId,
        private string $proConnectDomain,
    ) {
    }

    #[Route('/proconnect/start', name: 'pro_connect_start')]
    public function proConnectLogin(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        $request->getSession()->set('oauth2_state', $state);
        $request->getSession()->set('oauth2_nonce', $nonce);

        $params = [
            'response_type' => 'code',
            'client_id' => $this->proConnectClientId,
            'scope' => 'openid email given_name usual_name siret',
            'state' => $state,
            'nonce' => $nonce,
            'redirect_uri' => $this->urlGenerator->generate('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new RedirectResponse(
            \sprintf('%s/authorize?%s', $this->proConnectDomain, http_build_query($params)),
        );
    }

    #[Route('/proconnect/auth', name: 'pro_connect_callback')]
    public function proConnectCallback(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_regulations_list'));
    }

    #[Route('/proconnect/logout', name: 'pro_connect_logout')]
    public function logout(Request $request): RedirectResponse
    {
        $params = [
            'post_logout_redirect_uri' => $this->urlGenerator->generate('app_landing', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $request->getSession()->invalidate();
        $this->security->logout();

        return new RedirectResponse(
            \sprintf('%s/session/end?%s', $this->proConnectDomain, http_build_query($params)),
        );
    }

}
