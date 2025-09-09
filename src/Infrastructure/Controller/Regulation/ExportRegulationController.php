<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Measure\GetMeasuresQuery;
use App\Application\Regulation\Query\RegulationOrderTemplate\GetRegulationOrderTemplateQuery;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\RegulationOrderTemplateTransformer;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ExportRegulationController extends AbstractRegulationController
{
    public function __construct(
        QueryBusInterface $queryBus,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        private readonly RegulationOrderTemplateTransformer $regulationOrderTemplateTransformer,
        private readonly \Twig\Environment $twig,
        private readonly string $projectDir,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/export.{_format}',
        name: 'app_regulation_export',
        defaults: ['_format' => 'docx'],
        requirements: ['uuid' => Requirement::UUID, '_format' => 'docx'],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        /** @var GeneralInfoView */
        $generalInfo = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetGeneralInfoQuery($uuid));
        });

        $regulationOrderTemplate = $this->queryBus->handle(new GetRegulationOrderTemplateQuery($generalInfo->regulationOrderTemplateUuid));
        $signingAuthority = $this->queryBus->handle(new GetSigningAuthorityByOrganizationQuery($generalInfo->organizationUuid));
        $measures = $this->queryBus->handle(new GetMeasuresQuery($uuid));
        $regulationOrderTransformed = $this->regulationOrderTemplateTransformer->transform($regulationOrderTemplate, $generalInfo, $signingAuthority);

        $content = $this->twig->render(
            name: 'regulation/export.html.twig',
            context: [
                'regulationOrderTransformed' => $regulationOrderTransformed,
                'generalInfo' => $generalInfo,
                'measures' => $measures,
                'signingAuthority' => $signingAuthority,
            ],
        );

        $response = new Response(
            (new \Pandoc\Pandoc())
            ->from('html')
            ->input($content)
            ->option('reference-doc', $this->projectDir . '/data/regulation-order-template.docx')
            ->to('docx')
            ->run(),
        );
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, \sprintf('%s.docx', $uuid));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
