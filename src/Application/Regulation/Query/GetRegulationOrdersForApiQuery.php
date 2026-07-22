<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\User\Organization;

/**
 * Requête de recherche des arrêtés (regulation orders) exposés en lecture par l'API publique.
 *
 * Un arrêté = plusieurs mesures = plusieurs emprises (locations).
 * Seuls les arrêtés publiés de l'organisation authentifiée sont retournés.
 */
final readonly class GetRegulationOrdersForApiQuery implements QueryInterface
{
    public const STATUS_CURRENT = 'current';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ALL = 'all';

    /**
     * @param string      $vigueurStatus            Statut de vigueur : current (en vigueur, défaut),
     *                                              expired (expiré), upcoming (à venir) ou all (tous).
     * @param string|null $inseeCode                Code INSEE exact de commune : ne retourne que les arrêtés
     *                                              dont au moins une emprise concerne cette commune.
     * @param string|null $category                 permanentRegulation ou temporaryRegulation.
     * @param string|null $measureType              Type de restriction (mesure) recherché.
     * @param bool        $includeHeavyGoodsVehicle Si false, exclut les arrêtés dont au moins une mesure
     *                                              restreint les poids lourds.
     */
    public function __construct(
        public Organization $organization,
        public string $vigueurStatus = self::STATUS_CURRENT,
        public ?string $inseeCode = null,
        public ?\DateTimeInterface $dateStart = null,
        public ?\DateTimeInterface $dateEnd = null,
        public ?string $category = null,
        public ?string $measureType = null,
        public bool $includeHeavyGoodsVehicle = true,
        public int $page = 1,
        public int $pageSize = 20,
    ) {
    }
}
