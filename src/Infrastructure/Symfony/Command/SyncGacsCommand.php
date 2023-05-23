<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Infrastructure\Integrations\Gacs\Client\GacsClient;
use App\Infrastructure\Integrations\Gacs\Exception\SkipException;
use App\Infrastructure\Integrations\Gacs\Models\GacsArrete;
use App\Infrastructure\Integrations\Gacs\Models\GacsLocalisation;
use App\Infrastructure\Integrations\Gacs\Models\GacsMesure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:integrations:gacs:sync',
    description: 'Sync data from the GACS database',
    hidden: false,
)]
class SyncGacsCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private GacsClient $gacsClient,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $numRows = 0;
        $numAdded = 0;
        $numAddedPartially = 0;
        $numSkipped = 0;

        $log = function (string $event, array $data) use ($output) {
            $output->writeln(json_encode([
                'event' => $event,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE));
        };

        // TODO: Use organization of Ville de Paris
        $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery('e0d93630-acf7-4722-81e8-ff7d5fa64b66'));

        try {
            $arreteRows = $this->gacsClient->findActiveTemporaryArreteRows();
            $numRows = \count($arreteRows);

            foreach ($arreteRows as $arreteRow) {
                $this->entityManager->getConnection()->beginTransaction();

                try {
                    $arrete = GacsArrete::fromRow($arreteRow);

                    $regulationOrderRecord = $this->commandBus->handle($arrete->asCommand($organization));

                    $mesureRows = $this->gacsClient->findMesureRowsByArreteFileId($arrete->fileId);

                    $log('fetch.mesures', [
                        'arreteId' => $arrete->id,
                        'num_mesures' => \count($mesureRows),
                    ]);

                    $wereLocationsCreated = false;
                    $hasPartialSkips = false;

                    foreach ($mesureRows as $mesureIndex => $mesureRow) {
                        $mesureLoc = ['index' => $mesureIndex, 'fileId' => $mesureRow['fileId']];

                        try {
                            $mesure = GacsMesure::fromRow($mesureRow);
                            $measureCommand = $mesure->asCommand();
                        } catch (SkipException $exc) {
                            $hasPartialSkips = true;
                            $log('skip.mesure', [
                                'loc' => [
                                    $arreteRow['fields'][GacsArrete::ID],
                                    'mesures',
                                    $mesureLoc,
                                ],
                                'message' => $exc->getMessage(),
                            ]);
                            continue;
                        }

                        $localisationRowsForMesure = $this->gacsClient->findLocalisationRowsByMesureFileId($mesure->fileId);

                        foreach ($localisationRowsForMesure as $localisationIndex => $localisationRow) {
                            $localisationLoc = ['index' => $localisationIndex, 'fileId' => $localisationRow['fileId']];

                            try {
                                $localisation = GacsLocalisation::fromRow($localisationRow);
                                $locationCommand = $localisation->asCommand($regulationOrderRecord, $measureCommand);
                            } catch (SkipException $exc) {
                                $hasPartialSkips = true;
                                $log('skip.localisation', [
                                    'loc' => [
                                        $arreteRow['fields'][GacsArrete::ID],
                                        'mesures',
                                        $mesureLoc,
                                        'localisations',
                                        $localisationLoc,
                                    ],
                                    'message' => $exc->getMessage(),
                                ]);
                                continue;
                            }

                            $this->commandBus->handle($locationCommand);
                            $wereLocationsCreated = true;
                        }
                    }

                    if ($wereLocationsCreated) {
                        $this->entityManager->getConnection()->commit();

                        if ($hasPartialSkips) {
                            ++$numAddedPartially;
                        } else {
                            ++$numAdded;
                        }
                    } else {
                        // Don't create empty regulation orders, e.g. if content was skipped.
                        $log('skip.arrete.empty', [
                            'loc' => [
                                $arreteRow['fields'][GacsArrete::ID],
                            ],
                        ]);
                        ++$numSkipped;
                        $this->entityManager->getConnection()->rollBack();
                    }
                } catch (SkipException $exc) {
                    $this->entityManager->getConnection()->rollBack();
                    $log('skip.arrete', [
                        'loc' => [
                            $arreteRow['fields'][GacsArrete::ID],
                        ],
                        'message' => $exc->getMessage(),
                    ]);
                    ++$numSkipped;
                    continue;
                } catch (\Exception $exc) {
                    $this->entityManager->getConnection()->rollBack();
                    throw $exc;
                }
            }
        } catch (\Exception $exc) {
            $log('failure', [
                'message' => $exc->getMessage(),
            ]);

            return Command::FAILURE;
        }

        $log('success', [
            'num_rows' => $numRows,
            'num_added' => $numAdded,
            'num_added_partially' => $numAddedPartially,
            'num_skipped' => $numSkipped,
            'percent_added' => round(100 * $numAdded / $numRows, 2),
            'percent_added_partially' => round(100 * $numAddedPartially / $numRows, 2),
            'percent_total_added' => round(100 * ($numAdded + $numAddedPartially) / $numRows, 2),
            'percent_skipped' => round(100 * $numSkipped / $numRows, 2),
        ]);

        return Command::SUCCESS;
    }
}
