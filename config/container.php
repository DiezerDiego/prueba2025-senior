<?php
declare(strict_types=1);

use App\Application\UseCase\CreateReservation;
use App\Application\UseCase\ConfirmReservation;
use App\Application\UseCase\ExpireReservations;
use App\Application\UseCase\GetItem;
use App\Application\UseCase\GetReservation;
use App\Infrastructure\Client\PaymentClient;
use App\Infrastructure\Client\NotificationClient;
use App\Infrastructure\Logger\LoggerFactory;
use App\Infrastructure\Persistence\TransactionManager;
use App\Infrastructure\Persistence\Mysql\MysqlItemRepository;
use App\Infrastructure\Persistence\Mysql\MysqlReservationRepository;
use App\Infrastructure\Persistence\Mysql\MysqlIdempotencyRepository;
use App\Infrastructure\Persistence\Mysql\MysqlOutboxRepository;
use App\Infrastructure\Http\Validation\ItemValidator;
use App\Infrastructure\Http\Validation\ReservationValidator;
use App\Infrastructure\Http\Controller\ReservationController;
use App\Infrastructure\Http\Controller\ItemController;
use App\Infrastructure\Worker\ExpireReservationsWorker;
use App\Infrastructure\Worker\ConfirmReservationWorker;

require __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/database.php';
$servicesConfig = require __DIR__ . '/services.php';
$logger = LoggerFactory::create('app');


$pdo = new PDO(
    $dbConfig['mysql']['dsn'],
    $dbConfig['mysql']['user'],
    $dbConfig['mysql']['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$transactionManager = new TransactionManager($pdo);


$itemRepository        = new MysqlItemRepository($pdo);
$reservationRepository = new MysqlReservationRepository($pdo);
$idempotencyRepository = new MysqlIdempotencyRepository($pdo);
$outboxRepository      = new MysqlOutboxRepository($pdo);

$paymentClient      = new PaymentClient($servicesConfig["payment"],$logger);
$notificationClient = new NotificationClient($servicesConfig["notification"],$logger);

// Use Cases
$getItem=new GetItem(
    $itemRepository,
    $logger
);
$getReservation=new GetReservation(
    $reservationRepository,
    $logger
);
$createReservation = new CreateReservation(
    $itemRepository,
    $reservationRepository,
    $idempotencyRepository,
    $transactionManager,
    $logger
);

$confirmReservation = new ConfirmReservation(
    $reservationRepository,
    $itemRepository,
    $outboxRepository,
    $transactionManager,
    $logger
);

$expireReservations = new ExpireReservations(
    $reservationRepository,
    $itemRepository,
    $transactionManager,
    $logger
);

$itemValidator = new ItemValidator();
$reservationValidator= new ReservationValidator();

$reservationController = new ReservationController(
    $createReservation,
    $confirmReservation,
    $getReservation,
    $logger,
    $reservationValidator
);

$itemController = new ItemController(
    $getItem,
    $logger,
    $itemValidator
);
//workers
$confirmReservationWorker=new ConfirmReservationWorker(
    $outboxRepository,
    $reservationRepository,
    $transactionManager,
    $paymentClient,
    $notificationClient,
    $logger
);
$expireReservationsWorker=new ExpireReservationsWorker(
    $reservationRepository,
    $itemRepository,
    $transactionManager,
    $logger
);
return [
    ConfirmReservationWorker::class=> $confirmReservationWorker,
    ExpireReservationsWorker::class=> $expireReservationsWorker,
    CreateReservation::class   => $createReservation,
    ConfirmReservation::class  => $confirmReservation,
    ExpireReservations::class  => $expireReservations,
    GetItem::class     => $getItem,
    GetReservation::class => $getReservation,
    ReservationController::class => $reservationController,
    ItemController::class        => $itemController,
    'logger'                   => $logger,
    'transactionManager'       => $transactionManager,
    'itemRepository'           => $itemRepository,
    'reservationRepository'    => $reservationRepository,
    'idempotencyRepository'    => $idempotencyRepository,
    'outboxRepository'         => $outboxRepository,
    'paymentClient'            => $paymentClient,
    'notificationClient'       => $notificationClient,
];
