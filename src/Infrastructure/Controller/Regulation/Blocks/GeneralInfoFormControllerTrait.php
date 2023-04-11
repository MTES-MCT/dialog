<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Blocks;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Exception\OrganizationAlreadyHasRegulationOrderWithThisIdentifierException;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

trait GeneralInfoFormControllerTrait
{
    private \Twig\Environment $twig;
    private FormFactoryInterface $formFactory;
    private CommandBusInterface $commandBus;
    private Security $security;
    private TranslatorInterface $translator;

    abstract protected function getTemplateName(): string;

    abstract protected function getSuccessUrl(RegulationOrderRecord $regulationOrderRecord): string;

    protected function handle(Request $request, SaveRegulationOrderCommand $command): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => [$user->getOrganization()],
                'action' => $request->getUri(),
            ],
        );

        $form->handleRequest($request);

        $hasCommandFailed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $regulationOrderRecord = $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->getSuccessUrl($regulationOrderRecord),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (OrganizationAlreadyHasRegulationOrderWithThisIdentifierException) {
                $hasCommandFailed = true;
                $form->get('identifier')->addError(
                    new FormError(
                        $this->translator->trans('regulation.general_info.error.identifier', [], 'validators'),
                    ),
                );
            }
        }

        return new Response(
            $this->twig->render(
                name: $this->getTemplateName(),
                context: ['form' => $form->createView(), 'uuid' => $command->regulationOrderRecord?->getUuid()],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $hasCommandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
