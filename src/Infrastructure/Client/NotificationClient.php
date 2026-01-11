<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;
use App\Infrastructure\Dto\NotificationResultRecord;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class NotificationClient
{
    private Client $client;
    private int $retries;

    public function __construct(
        array $config,
        private LoggerInterface $logger
    ) {
        $this->client = new Client([
            'base_uri' => $config['base_uri'],
            'timeout'  => $config['timeout'],
        ]);

        $this->retries = $config['retries'] ?? 3;
    }

    public function notifyReservationStatus(
        int $reservationId,
        string $status
    ): NotificationResultRecord {
        $attempt = 0;

        do {
            try {
                $attempt++;

                $response = $this->client->post('/notify', [
                    'json' => [
                        'reservation_id' => $reservationId,
                        'status'         => $status,
                    ]
                ]);

                $data = json_decode((string)$response->getBody(), true);

                if (($data[0]['sent'] ?? false) === true) {
                    $this->logger->info('Notification sent', [
                        'reservation_id' => $reservationId,
                        'status' => $status,
                        'attempt' => $attempt
                    ]);

                    return new NotificationResultRecord(true);
                }

                throw new \RuntimeException('Notification not acknowledged');

            } catch (\Throwable $e) {
                $this->logger->warning('Notification attempt failed', [
                    'reservation_id' => $reservationId,
                    'status' => $status,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                sleep($attempt); // backoff simple
            }

        } while ($attempt < $this->retries);

        $this->logger->error('Notification failed after retries', [
            'reservation_id' => $reservationId,
            'status' => $status
        ]);

        return new NotificationResultRecord(false);
    }

}
