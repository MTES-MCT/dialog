<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Exception\OrganizationAlreadyHasRegulationOrderWithThisIdentifierException;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

trait GeneralInfoFormTrait
{
    private \Twig\Environment $twig;
    private FormFactoryInterface $formFactory;
    private CommandBusInterface $commandBus;
    private TranslatorInterface $translator;

    abstract protected function getGeneralInfoFormTemplateName(): string;

    abstract protected function getGeneralInfoFormSuccessUrl(RegulationOrderRecord $regulationOrderRecord): string;

    protected function handleGeneralInfoForm(Request $request, SymfonyUser $user, SaveRegulationOrderCommand $command): Response
    {
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
                    url: $this->getGeneralInfoFormSuccessUrl($regulationOrderRecord),
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
                name: $this->getGeneralInfoFormTemplateName(),
                context: ['form' => $form->createView(), 'uuid' => $command->regulationOrderRecord?->getUuid()],
            ),
            status: ($form->isSubmitted() && !$form->isValid()) || $hasCommandFailed
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}
