<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Handler de succès de connexion pour le formulaire de login.
 *
 * Lorsque la double authentification est requise (le jeton devient un TwoFactorToken),
 * on redirige immédiatement vers le formulaire 2FA plutôt que vers la cible habituelle.
 * Sinon, on conserve le comportement standard (cible demandée avant login, ou cible par défaut).
 */
final class TwoFactorAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly TwoFactorFirewallConfig $twoFactorFirewallConfig,
        private readonly string $firewallName,
        private readonly string $defaultTargetPath,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $request->getSession()->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        if ($token instanceof TwoFactorTokenInterface) {
            return $this->httpUtils->createRedirectResponse($request, $this->twoFactorFirewallConfig->getAuthFormPath());
        }

        $targetPath = $this->getTargetPath($request->getSession(), $this->firewallName);
        if ($targetPath !== null) {
            $this->removeTargetPath($request->getSession(), $this->firewallName);

            return $this->httpUtils->createRedirectResponse($request, $targetPath);
        }

        return $this->httpUtils->createRedirectResponse($request, $this->defaultTargetPath);
    }
}
