<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repository\Regulation;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Repository\AdministratorRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class AdministratorRepository implements AdministratorRepositoryInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function findAll(): array
    {
        return $this->cache->get('administrators', function (ItemInterface $item) {
            $item->expiresAfter(86400); // one day

            $content = file_get_contents(__DIR__ . '/../../../../../../data/gestionnaires.txt');

            preg_match_all(
                '/Gestionnaire = « (?P<administrator>.+) »\sValeur du champ « Type de route » associée : (?P<roadType>.+)/',
                $content,
                $matches,
            );

            $roadTypeMap = [
                'Départementale' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'Nationale' => RoadTypeEnum::NATIONAL_ROAD->value,
                // Tous les autres types que DiaLog ne gère pas pour l'instant
                'Autoroute' => '__skip__',
                'Voie verte' => '__skip__',
                'Route intercommunale' => '__skip__',
            ];

            $administratorsByRoadType = [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value => [],
                RoadTypeEnum::NATIONAL_ROAD->value => [],
            ];

            foreach ($matches['administrator'] as $i => $administrator) {
                $roadType = $roadTypeMap[$matches['roadType'][$i]];

                if ($roadType === '__skip__') {
                    continue;
                }

                $administratorsByRoadType[$roadType][] = $administrator;
            }

            return $administratorsByRoadType;
        });
    }
}
