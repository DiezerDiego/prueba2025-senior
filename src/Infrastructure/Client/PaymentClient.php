<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Infrastructure\Dto\PaymentResult;
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

    public function confirmReservation(int $reservationId): PaymentResult
    {
        $attempt = 0;

        do {
            try {
                $response = $this->client->post("/confirm", [
                        'json' => ['reservation_id' => $reservationId]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return new PaymentResult(
                        $data[0]['approved'] ?? false,
                        $data[0]['provider_ref'] ?? null
                );

            } catch (RequestException $e) {
                $this->logger->warning('Payment attempt failed', [
                    'reservation_id' => $reservationId,
                    'status' => $status,
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
