<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\FormInterface;

/**
 * Les échecs de géocodage portent l'index de la localisation mais pas le sous-formulaire
 * d'origine : le champ fautif peut appartenir à la localisation elle-même ou à l'une de ses
 * exceptions (« Sauf... »), dont les sous-formulaires réutilisent les mêmes types. Attacher
 * l'erreur au sous-formulaire de la localisation quand le type ne correspond pas l'afficherait
 * dans une section masquée : l'utilisateur ne verrait rien.
 */
final class LocationErrorTargetResolver
{
    /**
     * Cible un champ VISIBLE : celui de la localisation si son type correspond, sinon celui
     * de la première exception du type concerné, et en dernier recours le sélecteur de type
     * de la localisation.
     *
     * @param string[] $path chemin du champ dans le sous-formulaire du type concerné, ex. ['roadNumber']
     */
    public static function resolve(FormInterface $locationForm, string $roadType, array $path): FormInterface
    {
        $childName = self::childName($roadType);
        $locationRoadType = $locationForm->get('roadType')->getData();

        if ($locationRoadType === $roadType) {
            return self::descend($locationForm->get($childName), $path);
        }

        if ($locationRoadType && $locationForm->has($locationRoadType) && $locationForm->get($locationRoadType)->has('exceptions')) {
            foreach ($locationForm->get($locationRoadType)->get('exceptions') as $exceptionForm) {
                if ($exceptionForm->get('roadType')->getData() === $roadType && $exceptionForm->has($childName)) {
                    return self::descend($exceptionForm->get($childName), $path);
                }
            }
        }

        return $locationForm->get('roadType');
    }

    private static function childName(string $roadType): string
    {
        return $roadType === RoadTypeEnum::LANE->value ? 'namedStreet' : $roadType;
    }

    /**
     * @param string[] $path
     */
    private static function descend(FormInterface $form, array $path): FormInterface
    {
        foreach ($path as $segment) {
            $form = $form->get($segment);
        }

        return $form;
    }
}
