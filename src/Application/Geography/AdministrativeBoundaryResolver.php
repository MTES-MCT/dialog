<?php

declare(strict_types=1);

namespace App\Application\Geography;

use App\Application\AdministrativeBoundaryFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;

/**
 * Garantit que le contour d'une collectivité est disponible en base avant de filtrer
 * les restrictions dessus : il est téléchargé à la première demande, puis conservé.
 */
final class AdministrativeBoundaryResolver
{
    // Format des codes du COG par type de collectivité. Un code mal formé est forcément
    // inconnu : inutile d'interroger la source de données.
    private const CODE_PATTERNS = [
        OrganizationCodeTypeEnum::INSEE->value => '/^(?:\d{5}|2[AB]\d{3})$/',
        OrganizationCodeTypeEnum::EPCI->value => '/^\d{9}$/',
        OrganizationCodeTypeEnum::DEPARTMENT->value => '/^(?:\d{2,3}|2[AB])$/',
        OrganizationCodeTypeEnum::REGION->value => '/^\d{2}$/',
    ];

    public function __construct(
        private AdministrativeBoundaryRepositoryInterface $administrativeBoundaryRepository,
        private AdministrativeBoundaryFetcherInterface $administrativeBoundaryFetcher,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    /**
     * @throws AdministrativeBoundaryNotFoundException    si le code est inconnu du COG
     * @throws AdministrativeBoundaryUnavailableException si le contour n'est pas encore en base
     *                                                    et que la source est indisponible
     */
    public function resolve(OrganizationCodeTypeEnum $codeType, string $code): AdministrativeBoundaryView
    {
        $code = self::normalizeCode($code);

        if (!preg_match(self::CODE_PATTERNS[$codeType->value], $code)) {
            throw new AdministrativeBoundaryNotFoundException($codeType->value, $code);
        }

        $boundary = $this->administrativeBoundaryRepository->findOneByCode($codeType->value, $code);

        return $boundary ?? $this->refresh($codeType, $code);
    }

    /**
     * Résout plusieurs collectivités d'un coup.
     *
     * @param array<string, string|null> $codes Codes indexés par type (valeur de OrganizationCodeTypeEnum).
     *                                          Les codes null ou vides sont ignorés.
     *
     * @throws AdministrativeBoundaryNotFoundException
     * @throws AdministrativeBoundaryUnavailableException
     *
     * @return array<string, string> Codes normalisés des collectivités résolues, indexés par type
     */
    public function resolveCodes(array $codes): array
    {
        $resolvedCodes = [];

        foreach ($codes as $codeType => $code) {
            if ($code === null || trim($code) === '') {
                continue;
            }

            $resolvedCodes[$codeType] = $this->resolve(OrganizationCodeTypeEnum::from($codeType), $code)->code;
        }

        return $resolvedCodes;
    }

    /**
     * (Re)télécharge le contour d'une collectivité et le met à jour en base.
     *
     * @throws AdministrativeBoundaryNotFoundException
     * @throws AdministrativeBoundaryUnavailableException
     */
    public function refresh(OrganizationCodeTypeEnum $codeType, string $code): AdministrativeBoundaryView
    {
        $fetched = $this->administrativeBoundaryFetcher->fetch($codeType, $code);

        $this->administrativeBoundaryRepository->save(
            $codeType->value,
            $code,
            $fetched->name,
            $fetched->geometry,
            $this->dateUtils->getNow(),
        );

        return new AdministrativeBoundaryView($codeType->value, $code, $fetched->name);
    }

    public static function normalizeCode(string $code): string
    {
        // Les codes de la Corse (2A, 2B) sont parfois saisis en minuscules.
        return strtoupper(trim($code));
    }
}
