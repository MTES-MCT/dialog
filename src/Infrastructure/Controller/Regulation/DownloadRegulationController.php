<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class DownloadRegulationController extends AbstractRegulationController
{
    public function __construct(
        QueryBusInterface $queryBus,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        private readonly string $generatedRegulationOrderFolder,
        private readonly \Twig\Environment $twig,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/download',
        name: 'app_regulation_download',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecordUsing(function () use ($uuid) {
            return $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));
        });

        $content = $this->twig->render(
            name: 'regulation/export.md.twig',
            context: [
                'regulationOrderRecord' => $regulationOrderRecord,
            ],
        );

        $file = sprintf('%s%s.docx', $this->generatedRegulationOrderFolder, $uuid);
        $filesystem = new Filesystem();
        $filesystem->touch($file);
        $filesystem->appendToFile(
            $file,
            (new \Pandoc\Pandoc())
                ->from('markdown')
                ->input($content)
                ->option('reference-doc', __DIR__ . '/reference.docx')
                ->to('docx')
                ->run(),
        );

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('%s.docx', $uuid),
        );

        return $response;
    }
}
