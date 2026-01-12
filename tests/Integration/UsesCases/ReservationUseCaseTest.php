<?php
declare(strict_types=1);

use App\Infrastructure\Worker\ConfirmReservationWorker;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use TestContainers\Container\GenericContainer;
use Testcontainers\Modules\MySQLContainer;

final class ReservationUseCaseTest extends TestCase
{
    private static  $mysql;
    private static  $appContainer;
    private static string $baseUri;

    public static function setUpBeforeClass(): void
    {
        $mysqlName = 'mysql_test_' . uniqid();

        self::$mysql = (new MySQLContainer())
            ->withMySQLDatabase('app_test')
            ->withMySQLUser('app','secret')
            ->withName($mysqlName)
            ->withNetwork('test_network')
            ->withHealthCheckCommand(
                'mysqladmin ping -h localhost -u app -psecret',
                1000,
                3000,
                10,
                0
            )
            ->start();
        $mysqlHost = self::$mysql->getHost();
        $mysqlPort =  self::$mysql->getMappedPort(3306);

        $envString = sprintf(
            'DB_HOST=%s DB_PORT=%s DB_NAME=%s DB_USER=%s DB_PASSWORD=%s',
            $mysqlHost, $mysqlPort, 'app_test', 'app', 'secret'
        );
        exec("$envString vendor/bin/phinx migrate -c db/phinx.php", $output1, $return1);
        exec("$envString vendor/bin/phinx seed:run -c db/phinx.php", $output2, $return2);
        echo "Phinx migrate return: $return1\nOutput:\n"; print_r($output1);
        echo "Phinx seed return: $return2\nOutput:\n"; print_r($output2);

        self::$appContainer = (new GenericContainer('reservations_land_gorilla-app'))
            ->withExposedPorts(80)
            ->withEnvironment([
                'DB_HOST' => $mysqlName,
                'DB_PORT' => 3306,
                'DB_NAME' => 'app_test',
                'DB_USER' => 'app',
                'DB_PASSWORD' => 'secret',
                'PAYMENT_BASE_URI' => 'https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io',
                'NOTIFICATION_BASE_URI' => 'https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io'
            ])
            ->withNetwork('test_network')
            ->withName('app_test_' . uniqid())
            ->start();
        $mappedPort = self::$appContainer->getMappedPort(80);
        $host = self::$appContainer->getHost();
        self::$baseUri = "http://$host:$mappedPort";

    }

    public static function tearDownAfterClass(): void
    {
        self::$appContainer->stop();
        self::$mysql->stop();
    }

    public function test_create_and_confirm_reservation(): void
    {
        $client = new Client(['base_uri' => self::$baseUri]);


        $response = $client->post('/reservations', [
            'json' => [
                'sku' => 'ITEM-001',
                'quantity' => 2,
                'ttl_seconds' => 300
            ],
            "headers" => ['Idempotency-Key' => uniqid('key_', true)]
        ]);
        $reservationPending =json_decode($client->get('/reservations/1')->getBody()->getContents(), true);
        $statusPending = $reservationPending["status"];
        $this->assertSame('pending', $statusPending);
        $this->assertSame(201, $response->getStatusCode());


        $client->post('/reservations/1/confirm');
        $reservationNeedsConfirm = json_decode($client->get('/reservations/1')->getBody()->getContents(), true);
        $statusNeedsConfirm = $reservationNeedsConfirm["status"];
        $this->assertSame('needs_confirmation', $statusNeedsConfirm);
        $mysqlHost = self::$mysql->getHost();
        $mysqlPort =  self::$mysql->getMappedPort(3306);
        $envString = sprintf(
            'DB_HOST=%s DB_PORT=%s DB_NAME=%s DB_USER=%s DB_PASSWORD=%s PAYMENT_BASE_URI=%s  NOTIFICATION_BASE_URI=%s ',
            $mysqlHost,
            $mysqlPort,
            'app_test',
            'app',
            'secret','
            https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io',
            'https://55b73bf4-c233-4ab2-b2fa-b8a67d60e2c8.mock.pstmn.io'
        );
        exec("$envString php bin/process-outbox.php",$output, $returnVar);
        echo "Process-outbox return code: $returnVar\nOutput:\n"; print_r($output);

        $reservationConfirmed = json_decode($client->get('/reservations/1')->getBody()->getContents(), true);
        $statusConfirmed = $reservationConfirmed["status"];
        $this->assertSame('confirmed', $statusConfirmed);
    }
}
