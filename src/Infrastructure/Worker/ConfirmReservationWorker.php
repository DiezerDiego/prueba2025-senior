<?php

declare(strict_types=1);

namespace App\Infrastructure\Worker;

use App\Infrastructure\Client\PaymentClient;
use App\Infrastructure\Client\NotificationClient;
use App\Infrastructure\Persistence\TransactionManager;
use App\Infrastructure\Persistence\MysqlReservationRepository;
use App\Infrastructure\Persistence\MysqlOutboxRepository;
use Exception;
use Psr\Log\LoggerInterface;

final class ConfirmReservationWorker
{
    public function __construct(
        private MysqlOutboxRepository $outboxRepository,
        private MysqlReservationRepository $reservationRepository,
        private TransactionManager $transactionManager,
        private PaymentClient $paymentClient,
        private NotificationClient $notificationClient,
        private LoggerInterface $logger
    ) {}

    public function run(): void
    {
        $events = $this->outboxRepository->getPendingEvents('reservation_confirmation');

        foreach ($events as $event) {
            try {
                $this->transactionManager->transactional(function() use ($event) {
                    $reservation = $this->reservationRepository->getByIdForUpdate($event->reservationId);

                    if ($reservation->status() !== 'NEEDS_CONFIRMATION') {
                        $this->logger->info("Skipping reservation not in NEEDS_CONFIRMATION", [
                            'reservation_id' => $reservation->id(),
                            'status' => $reservation->status()
                        ]);
                        $this->outboxRepository->markProcessed($event->id);
                        return;
                    }

                    $result = $this->paymentClient->confirmReservation($reservation->id());

                    if ($result->approved) {
                        $reservation->markConfirmed();
                        $this->logger->info("Reservation confirmed", ['reservation_id' => $reservation->id()]);
                    } else {
                        $reservation->markCancelled();
                        $this->logger->warning("Reservation cancelled due to payment failure", ['reservation_id' => $reservation->id()]);
                    }

                    $this->reservationRepository->save($reservation);

                    try {
                        $result = $this->notificationClient->notifyReservationStatus(
                            $reservation->id(),
                            $reservation->status()->value
                        );

                        if (!$result->sent) {
                            $this->logger->warning('Notification not delivered (will not block flow)', [
                                'reservation_id' => $reservation->id()
                            ]);
                        }
                    } catch (Exception $e) {
                        $this->logger->warning("Notification failed: " . $e->getMessage(), ['reservation_id' => $reservation->id()]);
                    }

                    $this->outboxRepository->markProcessed($event->id);
                });

            } catch (Exception $e) {
                $this->logger->error("Failed to process reservation {$event->reservationId}: {$e->getMessage()}");
            }
        }
    }
}
