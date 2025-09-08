<?php

declare(strict_types=1);

namespace App\Application\Integration\Litteralis\DTO;

final class LitteralisCredentials
{
    private array $credentials;

    public function __construct()
    {
        $this->credentials = [];
    }

    public function add(string $name, string $orgId, string $credentials, ?string $baseUrl): self
    {
        $this->credentials[$name] = ['orgId' => $orgId, 'credentials' => $credentials, 'baseUrl' => $baseUrl];

        return $this;
    }

    public function getOrgId(string $name): ?string
    {
        if (\array_key_exists($name, $this->credentials)) {
            return $this->credentials[$name]['orgId'];
        }

        return null;
    }

    public function getCredentials(string $name): ?string
    {
        if (\array_key_exists($name, $this->credentials)) {
            return $this->credentials[$name]['credentials'];
        }

        return null;
    }

    public function getBaseUrl(string $name): ?string
    {
        if (\array_key_exists($name, $this->credentials)) {
            return $this->credentials[$name]['baseUrl'];
        }

        return null;
    }
}
