<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations\Gacs\Client;

use App\Infrastructure\Integrations\Gacs\Models\GacsArrete;
use App\Infrastructure\Integrations\Gacs\Models\GacsLocalisation;
use App\Infrastructure\Integrations\Gacs\Models\GacsMesure;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GacsClient
{
    // See: https://eudonet.apps.paris.fr/eudoapi/eudoapidoc/lexique_FR.html
    private const EQUALS = 0;
    private const GREATER_THAN = 3;
    private const AND = 1;
    private const TEMPORAIRE = 8;

    private string|null $token;
    private \DateTimeInterface|null $tokenExpiryDate;

    public function __construct(
        private HttpClientInterface $gacsHttpClient,
        private string $credentials,
    ) {
        $this->token = null;
        $this->tokenExpiryDate = null;
    }

    private function ensureAuthenticated(): void
    {
        if (null !== $this->token && $this->tokenExpiryDate > new \DateTimeImmutable('now')) {
            return;
        }

        $this->token = null;
        $this->tokenExpiryDate = null;

        $response = $this->gacsHttpClient->request('POST', '/EudoAPI/Authenticate/Token', [
            'headers' => ['Content-Type: application/json'],
            'body' => $this->credentials,
        ]);

        $data = $response->toArray();
        $this->token = $data['ResultData']['Token'];
        $this->tokenExpiryDate = \DateTimeImmutable::createFromFormat('Y/m/d H:i:s', $data['ResultData']['ExpirationDate'], new \DateTimeZone('Europe/Paris'));
    }

    private function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->ensureAuthenticated();
        $headers = array_merge($options['headers'], ["X-Auth: $this->token"]);
        $options = array_merge($options, ['headers' => $headers]);

        return $this->gacsHttpClient->request($method, $url, $options);
    }

    private function search(int $tabId, array $listCols, array $whereCustom = [], int $maxPages = 1): array
    {
        $pageNumber = 1;
        $rows = [];

        for ($pageNumber = 1; $pageNumber <= $maxPages; ++$pageNumber) {
            $response = $this->request('POST', sprintf('/EudoAPI/Search/%s', $tabId), [
                'headers' => ['Content-Type: application/json'],
                'body' => json_encode([
                    'ShowMetadata' => true,
                    'RowsPerPage' => 50,
                    'NumPage' => $pageNumber,
                    'ListCols' => $listCols,
                    'WhereCustom' => $whereCustom,
                ]),
            ]);

            $data = $response->toArray();

            foreach ($data['ResultData']['Rows'] as $row) {
                $fields = [];

                foreach ($row['Fields'] as $field) {
                    $fields[$field['DescId']] = $field['Value'];
                }

                $rows[] = ['fileId' => $row['FileId'], 'fields' => $fields];
            }
        }

        return $rows;
    }

    public function findActiveTemporaryArreteRows(): array
    {
        return $this->search(
            tabId: GacsArrete::TAB_ID,
            listCols: GacsArrete::listCols(),
            whereCustom: [
                'WhereCustoms' => [
                    [
                        'Criteria' => [
                            'Field' => GacsArrete::TYPE,
                            'Operator' => self::EQUALS,
                            'Value' => self::TEMPORAIRE,
                        ],
                    ],
                    [
                        'Criteria' => [
                            'Field' => GacsArrete::DATE_FIN,
                            'Operator' => self::GREATER_THAN,
                            'Value' => (new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y/m/d H:i:s'),
                        ],
                        'InterOperator' => self::AND,
                    ],
                ],
            ],
            maxPages: 5,
        );
    }

    public function findMesureRowsByArreteFileId(int $fileId): array
    {
        return $this->search(
            tabId: GacsMesure::TAB_ID,
            listCols: GacsMesure::listCols(),
            whereCustom: [
                'Criteria' => [
                    'Field' => GacsArrete::TAB_ID,
                    'Operator' => self::EQUALS,
                    'Value' => $fileId,
                ],
            ],
        );
    }

    public function findLocalisationRowsByMesureFileId(int $fileId): array
    {
        return $this->search(
            tabId: GacsLocalisation::TAB_ID,
            listCols: GacsLocalisation::listCols(),
            whereCustom: [
                'Criteria' => [
                    'Field' => GacsMesure::TAB_ID,
                    'Operator' => self::EQUALS,
                    'Value' => $fileId,
                ],
            ],
        );
    }
}
