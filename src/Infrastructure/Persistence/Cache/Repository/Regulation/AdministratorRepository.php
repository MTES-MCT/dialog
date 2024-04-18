<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repository\Regulation;

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
            $administrators = [];

            foreach (explode('|', $content) as $value) {
                $value = trim($value); // ' Bas-\nRhin ' => 'Bas-\nRhin'
                $value = str_replace('-' . PHP_EOL, '-', $value); // => 'Bas-Rhin'
                $administrators[] = str_replace(PHP_EOL, ' ', $value); // 'Toulouse\nMétropole' => 'Toulouse Métropole'
            }

            return $administrators;
        });
    }
}
