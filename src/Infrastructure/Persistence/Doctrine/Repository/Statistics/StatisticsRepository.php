<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Statistics;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Statistics\Repository\StatisticsRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Connection;

final class StatisticsRepository implements StatisticsRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private Connection $metabaseConnection,
    ) {
    }

    public function addCountStatistics(\DateTimeInterface $now): void
    {
        // On peut tracer le graphique d'évolution de chaque count en groupant par 'name' et
        // en utilisant 'uploadedAt' (la date d'exécution) comme abscisse.
        $counts = [
            'users' => $this->userRepository->countUsers(),
            'organizations' => $this->organizationRepository->countOrganizations(),
            'regulationOrderRecords' => $this->regulationOrderRecordRepository->countTotalRegulationOrderRecords(),
            'regulationOrderRecords.published' => $this->regulationOrderRecordRepository->countPublishedRegulationOrderRecords(),
            'regulationOrderRecords.permanent' => $this->regulationOrderRecordRepository->countPermanentRegulationOrderRecords(),
            'regulationOrderRecords.temporary' => $this->regulationOrderRecordRepository->countTemporaryRegulationOrderRecords(),
        ];

        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_count(id, uploaded_at, name, value)
            VALUES (uuid_generate_v4(), :uploadedAt, :name, :value)',
        );

        foreach ($counts as $name => $value) {
            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('name', $name);
            $stmt->bindValue('value', $value);
            $stmt->execute();
        }
    }

    public function addUserActiveStatistics(\DateTimeInterface $now): void
    {
        // À chaque export des statistiques, on ajoute la liste des dates de dernière activité pour chaque utilisateur, et la date d'exécution.
        // Dans Metabase cela permet de calculer le nombre d'utilisateurs actifs au moment de chaque exécution.
        // (Par exemple avec un filtre : "[last_active_at] >= [uploaded_at] - 7 jours", puis en groupant sur le uploaded_at.)
        $userRows = $this->userRepository->findAllForStatistics();
        $this->bulkInsertUserActiveStatistics($now, $userRows);
    }

    private function bulkInsertUserActiveStatistics(\DateTimeInterface $now, array $userRows): void
    {
        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_user_active(id, uploaded_at, last_active_at)
            VALUES (:id, (:uploadedAt)::timestamp(0), (:lastActiveAt)::timestamp(0))',
        );

        foreach ($userRows as $row) {
            $stmt->bindValue('id', $row['uuid']);
            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('lastActiveAt', $row['lastActiveAt']?->format(\DateTimeInterface::ATOM));
            $stmt->execute();
        }
    }
}
