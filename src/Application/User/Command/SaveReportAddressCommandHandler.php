<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;

final class SaveReportAddressCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private ReportAddressRepositoryInterface $reportAddressRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveReportAddressCommand $command): void
    {
        $reportAddress = new ReportAddress(
            uuid: $this->idFactory->make(),
            content: $command->content,
            location: $command->location,
            user: $command->user,
        );
        $reportAddress->setCreatedAt($this->dateUtils->getNow());

        $this->reportAddressRepository->add($reportAddress);
    }
}
