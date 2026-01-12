# Architecture - Reservations Land Gorilla

## Key Design Decisions

### Concurrency and Locks
- To prevent overbooking, we use `SELECT ... FOR UPDATE` when reserving stock.
- This ensures that concurrent requests for the same `item_id` do not allow the stock to be oversold.
- All reservation creation and stock decrement operations occur within a transaction.

### Idempotency
- We maintain a table `idempotency_requests` to store `idempotency_key` and `payload_hash`.
- When a request with the same `idempotency_key` arrives, the system:
    - Returns the same response if the payload matches.
    - Returns `409 Conflict` if the payload differs.
- This ensures safe retries without creating duplicate reservations.

### Outbox Pattern and Worker/Cron
- Events that require external calls (e.g., payment confirmation, notifications) are written to an `outbox` table.
- The worker or cron job periodically reads unprocessed events from the outbox.
- The SQL query for fetching events uses a `LIMIT` to avoid overloading the system.
- The worker executes retries in case of transient failures.
- This approach allows eventual consistency with external services and makes the system resilient to slow or unreliable APIs.

### Retries
- Retries are applied to external calls for confirmation or notifications.
- Current implementation uses simple backoff.
- Failures are logged and retried by the worker until successful.

## Database Schema

### `items`
| Column             | Type      | Notes                  |
|-------------------|-----------|-----------------------|
| id                | int       | Primary key           |
| sku               | string(64)| Unique                |
| name              | string(255)|                     |
| available_quantity| integer   | Default 0             |
| created_at        | datetime  |                       |
| updated_at        | datetime  |                       |

- `sku` is unique to prevent duplicate items.
- `available_quantity` is used to control stock.

### `reservations`
| Column           | Type       | Notes                              |
|-----------------|-----------|-----------------------------------|
| id               | int       | Primary key                        |
| item_id          | int       | FK to `items.id`                   |
| idempotency_key  | string(64)| Unique, used for idempotent POST   |
| quantity         | int       | Number of items reserved           |
| status           | string(20)| PENDING, CONFIRMED, CANCELLED, EXPIRED |
| expires_at       | datetime  | TTL for reservation                 |
| created_at       | datetime  |                                     |
| updated_at       | datetime  |                                     |

- Index on `(status, expires_at)` for efficient querying of expired reservations.

### `idempotency_requests`
| Column           | Type       | Notes                          |
|-----------------|-----------|-------------------------------|
| idempotency_key  | string(64)| Unique, to detect repeated requests |
| payload_hash     | string(64)| Hash of request payload         |
| reservation_id   | int       | FK to `reservations.id`        |
| created_at       | datetime  |                                 |

- Ensures safe retries and prevents duplicate reservation creation.

### `outbox`
| Column           | Type       | Notes                            |
|-----------------|-----------|---------------------------------|
| type             | string(50)| Event type (e.g., reservation_confirm) |
| payload          | text       | Event data                       |
| processed_at     | datetime   | Null if not yet processed        |
| created_at       | datetime   | Defaults to current timestamp    |

- Stores events to be processed asynchronously by a worker.

## Worker / Cron

- The worker script or cron job is responsible for:
    - Processing the outbox events (calling external services).
    - Expiring reservations that have passed their TTL.
- Example command:

```bash
# Process outbox events
php bin/process-outbox

# Expire reservations
php bin/expire-reservations
```

- The SQL query used by the worker includes a `LIMIT` to avoid overloading the system.
- Both processes are safe for concurrent execution.

## Trade-offs / Production Considerations

- **Current Approach:**
    - Simple MySQL-based locks and outbox table.
    - Worker/cron for asynchronous processing.
    - Simple backoff for retries.
- **Production Improvements:**
    - Use a dedicated message queue such as RabbitMQ or Azure Service Bus instead of DB polling.
    - Implement distributed locks if scaling to multiple instances.
    - Add monitoring and observability with tools like Grafana, Prometheus, and Elasticsearch for full traceability.
    - Implement exponential backoff, jitter, and retry policies with dead-letter queues for robust retry handling instead of simple retries.
    - Consider caching `available_quantity` to reduce DB contention under high load.
    - Use database partitioning or sharding if the data grows significantly.

---

This architecture ensures:
- Safe handling of concurrent reservations.
- Idempotent operations for clients.
- Resilient asynchronous confirmation and notifications.
- Clear separation of domain, application, and infr