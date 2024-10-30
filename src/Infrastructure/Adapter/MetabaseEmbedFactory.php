<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;

final class MetabaseEmbedFactory
{
    private const DASHBOARD_ID_STATS = 2;

    public function __construct(
        private string $metabaseSiteUrl,
        private string $metabaseSecretKey,
        private JWTEncoder $jwtEncoder,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function makeDashboardUrl(): string
    {
        // Ce code a été adapté du code d'intégration fourni par Metabase dans les options de partage d'une visualisation.
        // Plus d'infos sur le "static embedding" : https://www.metabase.com/docs/latest/embedding/static-embedding

        $payload = [
            'resource' => ['dashboard' => self::DASHBOARD_ID_STATS],
            'params' => (object) [], // sic: L'array doit être converti en 'object', voir : https://github.com/metabase/metabase/issues/6487#issuecomment-1535278810
            'exp' => round($this->dateUtils->getNow()->getTimestamp()) + 10 * 60, // 10 minute expiration
        ];

        $token = $this->jwtEncoder->encode($payload, $this->metabaseSecretKey);

        return \sprintf('%s/embed/dashboard/%s#bordered=true&titled=true', $this->metabaseSiteUrl, $token);
    }
}
