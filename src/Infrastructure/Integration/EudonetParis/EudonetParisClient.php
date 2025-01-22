<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\EudonetParis;

use App\Application\DateUtilsInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EudonetParisClient
{
    private ?string $token;
    private ?\DateTimeInterface $tokenExpiryDate;

    public function __construct(
        private HttpClientInterface $eudonetParisHttpClient,
        private string $credentials,
        private DateUtilsInterface $dateUtils,
        private LoggerInterface $eudonetParisImportLogger,
    ) {
        $this->token = null;
        $this->tokenExpiryDate = null;
    }

    private function ensureAuthenticated(): void
    {
        if (null !== $this->token && $this->tokenExpiryDate > $this->dateUtils->getNow()) {
            return;
        }

        $this->token = null;
        $this->tokenExpiryDate = null;

        $response = $this->eudonetParisHttpClient->request('POST', '/EudoAPI/Authenticate/Token', [
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

        $this->eudonetParisImportLogger->debug('request', ['method' => $method, 'url' => $url, 'body' => json_decode($options['body'])]);
        $response = $this->eudonetParisHttpClient->request($method, $url, $options);

        $body = $response->getContent(throw: false);
        $jsonDecodeError = null;

        try {
            $body = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exc) {
            $jsonDecodeError = $exc->getMessage();
        }

        $context = ['body' => $body];

        if ($jsonDecodeError) {
            $context['json_decode_error'] = $jsonDecodeError;
        }

        $this->eudonetParisImportLogger->debug('response', $context);

        return $response;
    }

    public function search(int $tabId, array $listCols, array $whereCustom = []): array
    {
        $rows = [];

        $pageNumber = 1;

        while (true) {
            $response = $this->request('POST', \sprintf('/EudoAPI/Search/%s', $tabId), [
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

                // Transform field data into something easier to work with:
                // Before: [['DescId' => 0, 'Value' => 'value0'], ...]
                // After:  [0 => 'value0', ...]
                foreach ($row['Fields'] as $field) {
                    $fields[$field['DescId']] = $field['Value'];
                }

                $rows[] = ['fileId' => $row['FileId'], 'fields' => $fields];
            }

            $totalPages = $data['ResultMetaData']['TotalPages'];

            if ($pageNumber >= $totalPages) {
                break;
            }

            ++$pageNumber;
        }

        return $rows;
    }

    public function count(int $tabId, array $listCols, array $whereCustom): int
    {
        $response = $this->request('POST', \sprintf('/EudoAPI/Search/%s', $tabId), [
            'headers' => ['Content-Type: application/json'],
            'body' => json_encode([
                'ShowMetadata' => true,
                'RowsPerPage' => 1,
                'NumPage' => 1,
                'ListCols' => $listCols,
                'WhereCustom' => $whereCustom,
            ]),
        ]);
        $data = $response->toArray();
        $totalRows = $data['ResultMetaData']['TotalRows']; // number of rows without pagination

        return $totalRows;
    }
}
