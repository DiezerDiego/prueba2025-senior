<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Application\Dto\PaymentResultRecord;
use Psr\Log\LoggerInterface;

final class PaymentClient
{
    private Client $client;
    private int $retries;

    public function __construct(
        array $config,
        private LoggerInterface $logger
    )
    {
        $this->client = new Client([
                'base_uri' => $config['base_uri'],
                'timeout' => $config['timeout']
        ]);
        $this->retries = $config['retries'] ?? 3;
    }

    public function confirmReservation(int $reservationId): PaymentResultRecord
    {
        $attempt = 0;

        do {
            try {
                $response = $this->client->post("/v3/payment", [
                        'json' => ['reservation_id' => $reservationId]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return new PaymentResultRecord(
                        $data['approved'],
                        $data['provider_ref']
                );

            } catch (RequestException $e) {
                $httpStatus = $e->hasResponse()
                    ? $e->getResponse()->getStatusCode()
                    : 'no_response';
                $this->logger->warning('Payment attempt failed', [
                    'reservation_id' => $reservationId,
                    'status' => $httpStatus,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                $attempt++;
                if ($attempt >= $this->retries) {
                    throw new \Exception('Payment provider failed after retries: ' . $e->getMessage());
                }
                // Simple backoff
                sleep(1 * $attempt);
            }
        } while ($attempt < $this->retries);
    }
}
