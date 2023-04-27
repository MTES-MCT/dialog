<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\DeleteRegulationLocationCommand;
use App\Domain\Regulation\Exception\LocationCannotBeDeletedException;
use App\Domain\Regulation\Exception\LocationDoesntBelongsToRegulationOrderException;
use App\Domain\Regulation\Exception\LocationNotFoundException;
use App\Domain\Regulation\Specification\CanDeleteLocations;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\Turbo\TurboBundle;

final class DeleteLocationFragmentController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private CommandBusInterface $commandBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CanDeleteLocations $canDeleteLocations,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/location/{uuid}/delete',
        name: 'fragment_regulations_location_delete',
        methods: ['DELETE'],
    )]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid): Response
    {
        $csrfToken = new CsrfToken('delete-location', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }

        if (!Uuid::isValid($uuid)) {
            throw new BadRequestHttpException();
        }

        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        try {
            $this->commandBus->handle(
                new DeleteRegulationLocationCommand(
                    uuid: $uuid,
                    regulationOrderRecord: $regulationOrderRecord,
                ),
            );
        } catch (LocationCannotBeDeletedException) {
            throw new BadRequestHttpException();
        } catch (LocationNotFoundException) {
            throw new NotFoundHttpException();
        } catch (LocationDoesntBelongsToRegulationOrderException) {
            throw new AccessDeniedHttpException();
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_location.deleted.stream.html.twig',
                context: [
                    'uuid' => $uuid,
                    'canDelete' => $this->canDeleteLocations->isSatisfiedBy($regulationOrderRecord),
                    'locationUuids' => $regulationOrderRecord->getLocationUuids(),
                ],
            ),
        );
    }
}
