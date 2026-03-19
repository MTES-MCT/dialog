<?php

declare(strict_types=1);

namespace App\Application;

interface ApiClientSecretHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainSecret): string;
}
