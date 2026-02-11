<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Statistics;

use App\Application\Cifs\CifsExportClientInterface;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Statistics\Repository\StatisticsRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class StatisticsRepository implements StatisticsRepositoryInterface
{
    private const VALIDITY_ACTIVE = 'actif';
    private const VALIDITY_EXPIRED = 'expire';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
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
        $userRows = $this->userRepository->findActiveUsersLastWeek();
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

    public function addOrganizationExtractStatistics(\DateTimeImmutable $now): void
    {
        $rows = $this->organizationRepository->findAllForMetabaseExport();

        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_organization_extract(id, uploaded_at, organization_uuid, organization_name, organization_type, nb_users, nb_published_regulation_orders)
            VALUES (uuid_generate_v4(), (:uploadedAt)::timestamp(0), :organizationUuid, :organizationName, :organizationType, :nbUsers, :nbPublished)',
        );

        foreach ($rows as $row) {
            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('organizationUuid', $row['organization_uuid']);
            $stmt->bindValue('organizationName', $row['organization_name']);
            $stmt->bindValue('organizationType', $row['code_type']);
            $stmt->bindValue('nbUsers', $row['nb_users']);
            $stmt->bindValue('nbPublished', $row['nb_published_regulation_orders']);
            $stmt->executeStatement();
        }
    }

    public function addRegulationOrderRecordsExtractStatistics(\DateTimeImmutable $now): void
    {
        $rows = $this->regulationOrderRecordRepository->findAllForMetabaseExport();

        if (0 === \count($rows)) {
            return;
        }

        $regulationOrderUuids = array_unique(array_column($rows, 'regulation_order_uuid'));
        $publicationDates = $this->regulationOrderHistoryRepository->findPublicationDatesByRegulationOrderUuids($regulationOrderUuids);

        $stmt = $this->metabaseConnection->prepare(
            'INSERT INTO analytics_regulation_order_record(id, uploaded_at, record_uuid, organization_uuid, status, category, subject, source, created_at, publication_date, start_date, end_date, is_permanent, validity_status)
            VALUES (uuid_generate_v4(), (:uploadedAt)::timestamp(0), :recordUuid, :organizationUuid, :status, :category, :subject, :source, (:createdAt)::timestamp(0), :publicationDate, :startDate, :endDate, :isPermanent, :validityStatus)',
        );

        $nowTs = $now->getTimestamp();

        foreach ($rows as $row) {
            $publicationDate = $publicationDates[$row['regulation_order_uuid']] ?? null;
            $endDate = $row['overall_end_date'];
            $isPermanent = $row['is_permanent'];

            $validityStatus = self::VALIDITY_ACTIVE;
            if (!$isPermanent && $endDate !== null) {
                $endTs = strtotime($endDate);
                $validityStatus = $endTs < $nowTs ? self::VALIDITY_EXPIRED : self::VALIDITY_ACTIVE;
            }

            $stmt->bindValue('uploadedAt', $now->format(\DateTimeInterface::ATOM));
            $stmt->bindValue('recordUuid', $row['record_uuid']);
            $stmt->bindValue('organizationUuid', $row['organization_uuid']);
            $stmt->bindValue('status', $row['status']);
            $stmt->bindValue('category', $row['category']);
            $stmt->bindValue('subject', $row['subject']);
            $stmt->bindValue('source', $row['source']);
            $stmt->bindValue('createdAt', $row['created_at']);
            $stmt->bindValue('publicationDate', $publicationDate);
            $stmt->bindValue('startDate', $row['overall_start_date']);
            $stmt->bindValue('endDate', $endDate);
            $stmt->bindValue('isPermanent', (bool) $isPermanent, ParameterType::BOOLEAN);
            $stmt->bindValue('validityStatus', $validityStatus);
            $stmt->executeStatement();
        }
    }
}
