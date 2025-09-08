<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\DependencyInjection;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class LitteralisCredentialsEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private readonly string $dialogOrgId,
        private readonly ?array $litteralisEnabledOrgs,
    ) {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): LitteralisCredentials
    {
        $credentials = new LitteralisCredentials();

        $orgEnvPrefix = $name;

        foreach (($this->litteralisEnabledOrgs ?? []) as $orgName) {
            $orgEnvName = \sprintf('%s%s_ID', $orgEnvPrefix, strtoupper($orgName));
            $orgIdEnv = $getEnv($orgEnvName);

            if (!$orgIdEnv) {
                throw new \RuntimeException(\sprintf('Environment variable %s must not be empty', $orgEnvName));
            }

            if ($orgIdEnv === '__dialog__') {
                $orgIdEnv = $this->dialogOrgId;
            }

            $credentialsEnvName = \sprintf('%s%s_CREDENTIALS', $orgEnvPrefix, strtoupper($orgName));
            $credentialsEnv = $getEnv($credentialsEnvName);

            if (!$credentialsEnv) {
                throw new \RuntimeException(\sprintf('Environment variable %s must not be empty', $credentialsEnvName));
            }

            $baseUrlEnvName = \sprintf('%s%s_BASE_URL', $orgEnvPrefix, strtoupper($orgName));
            $baseUrlEnv = $getEnv($baseUrlEnvName);

            $credentials->add($orgName, $orgIdEnv, $credentialsEnv, $baseUrlEnv);
        }

        return $credentials;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'litteralis_credentials' => 'string',
        ];
    }
}
