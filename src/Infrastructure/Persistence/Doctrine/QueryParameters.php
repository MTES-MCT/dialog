<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

final class QueryParameters
{
    /**
     * Doctrine ORM 3 no longer accepts a plain array in setParameters().
     *
     * @param array<string, mixed> $parameters
     *
     * @return ArrayCollection<int, Parameter>
     */
    public static function fromArray(array $parameters): ArrayCollection
    {
        $collection = new ArrayCollection();

        foreach ($parameters as $name => $value) {
            $collection->add(new Parameter($name, $value));
        }

        return $collection;
    }
}
