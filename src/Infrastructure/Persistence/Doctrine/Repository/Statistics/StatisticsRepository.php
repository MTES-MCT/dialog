<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Statistics;

use App\Application\Cifs\CifsExportClientInterface;
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
        private CifsExportClientInterface $cifsExportClient,
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
            'cifs.incidents' => $this->cifsExportClient->getIncidentsCount(),
        ];

        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_count(id, uploaded_at, name, value)
            VALUES (uuid_generate_v4(), :uploadedAt, :name, :value)',
        );

        foreach ($counts as $name => $value) {
            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('name', $name);
            $stmt->bindValue('value', $value);
            $stmt->executeStatement();
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
            $stmt->bindValue('lastActiveAt', $row['last_active_at']);
            $stmt->executeStatement();
        }
    }

    public function addOrganizationCoverageStatistics(\DateTimeInterface $now): void
    {
        // Export les géométries des organisations pour visualiser leur couverture territoriale dans Metabase
        $organizationRows = $this->organizationRepository->findAllForStatistics();
        $this->bulkInsertOrganizationCoverageStatistics($now, $organizationRows);
    }

    private function bulkInsertOrganizationCoverageStatistics(\DateTimeInterface $now, array $organizationRows): void
    {
        // D'abord, on nettoie les anciennes données pour ne garder que la dernière version
        // Cela évite d'accumuler des données obsolètes si les géométries changent
        $date = \DateTimeImmutable::createFromInterface($now)->sub(new \DateInterval('P7D'));

        $this->metabaseConnection->executeStatement(
            'DELETE FROM analytics_organization_coverage WHERE uploaded_at < :date',
            [
                'date' => $date->format(\DateTimeInterface::ATOM),
            ],
        );

        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_organization_coverage(id, uploaded_at, organization_uuid, organization_name, geometry)
            VALUES (uuid_generate_v4(), :uploadedAt, :organizationUuid, :organizationName, ST_GeomFromGeoJSON(:geometry))',
        );

        foreach ($organizationRows as $row) {
            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('organizationUuid', $row['uuid']);
            $stmt->bindValue('organizationName', $row['name']);
            $stmt->bindValue('geometry', $row['geometry']);
            $stmt->executeStatement();
        }
    }
}
