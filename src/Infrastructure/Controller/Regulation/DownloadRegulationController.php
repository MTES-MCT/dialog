<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

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
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

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
