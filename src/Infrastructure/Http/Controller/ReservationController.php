<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Nyholm\Psr7\Response;
use App\Infrastructure\Http\Validation\ReservationValidator;
use App\Application\UseCase\CreateReservation;
use App\Application\UseCase\ConfirmReservation;
use App\Application\UseCase\GetReservation;
use DomainException;

final class ReservationController
{
    public function __construct(
        private CreateReservation $createReservation,
        private ConfirmReservation $confirmReservation,
        private GetReservation $getReservation,
        private LoggerInterface $logger,
        private ReservationValidator $validator

    ) {}

    // POST /reservations
    public function create(ServerRequestInterface $request, array $vars): Response
    {
        try {
            $data = $this->validator->validateCreate($request);

            $reservation = $this->createReservation->execute(
                idempotencyKey: $data['idempotency_key'],
                sku: $data['sku'],
                quantity: $data['quantity'],
                ttlSeconds: $data['ttl_seconds']
            );

            $this->logger->info('Reservation created', [
                'reservation_id' => $reservation->id(),
                'item_id' => $reservation->itemId(),
                'quantity' => $reservation->quantity()
            ]);
            return new Response(
                201,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'reservation_id' => $reservation->id(),
                    'status' => $reservation->status()->value,
                    'expires_at' => $reservation->expiresAt()->format(DATE_ATOM)
                ])
            );
        } catch (DomainException $e) {
            $this->logger->warning('Reservation creation failed', ['error' => $e->getMessage()]);
            return new Response(
                409,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        } catch (\Throwable $e) {

            $this->logger->error('Reservation creation unexpected error', ['error' => $e->getMessage()]);
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Internal Server Error'])
            );
        }
    }

    // GET /reservations/{id}
    public function show(ServerRequestInterface $request, array $vars): Response
    {

        try {
            $id = $this->validator->validateId($vars);

            $reservation = $this->getReservation->execute($id);

            $this->logger->info('Fetched reservation', ['reservation_id' => $id]);

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($reservation)
            );
        } catch (DomainException $e) {
            $this->logger->warning('Reservation not found', ['reservation_id' => $id]);
            return new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        }
    }

    // POST /reservations/{id}/confirm
    public function confirm(ServerRequestInterface $request, array $vars): Response
    {

        try {
            $id = $this->validator->validateId($vars);

            $status=$this->confirmReservation->execute($id);

            $this->logger->info('Reservation marked for confirmation', ['reservation_id' => $id]);

            return new Response(
                202,
                ['Content-Type' => 'application/json'],
                json_encode(['reservation_id' => $id, 'status' => $status])
            );
        } catch (DomainException $e) {
            $this->logger->warning('Reservation confirmation failed', ['reservation_id' => $id, 'error' => $e->getMessage()]);
            return new Response(
                409,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        } catch (\Throwable $e) {
            $this->logger->error('Reservation confirmation unexpected error', ['reservation_id' => $id, 'error' => $e->getMessage()]);
            return new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Internal Server Error'])
            );
        }
    }
}
