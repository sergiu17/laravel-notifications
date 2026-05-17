# Notifications API

---

## Architecture

```
                                    ┌────────────────────────────┐
   HTTP request                     │  /api/notifications        │
   ──────────────────────────────▶  │  /api/notifications/batch  │ ── validate ──▶ persist (status=pending)
                                    └────────────────────────────┘                       │
                                                                                         ▼
                                                                                ┌─────────────────┐
                                                                                │  notifications  │  (MySQL)
                                                                                └─────────────────┘
                                                                                          ▲
                                                                                          │ SELECT due rows
                                                                                          │
                                              every minute   ┌──────────────────────────────┐
                                              ─────────────▶ │ app:process-notifications    │
                                              schedule:run   │ scheduler-driven dispatcher  │
                                                             └──────────────┬───────────────┘
                                                                            │ dispatch onto Redis
                                                                            ▼
                                                              ┌──────────────────────────┐
                                                              │  Redis queues by priority│
                                                              │   • high                 │
                                                              │   • default              │
                                                              │   • low                  │
                                                              └─────────────┬────────────┘
                                                                            │
                                                                            ▼
                                                              ┌──────────────────────────┐
                                                              │  ProcessNotification job │
                                                              │  • rate limit 100/sec/ch │
                                                              │  • POST webhook.site     │
                                                              │  • retry / backoff       │
                                                              │  • status → processed    │
                                                              │              or failed   │
                                                              └──────────────────────────┘
```

**One dispatch path.** Both the single and batch endpoints simply persist rows. The Artisan command `app:process-notifications` is the single source of dispatch — it scans due `pending` rows and pushes `ProcessNotification` jobs onto Redis queues keyed by priority.

---

## Quick start

### 1. Clone and configure

```bash
git clone https://github.com/sergiu17/laravel-notifications
cd laravel-notifications
cp .env.example .env
```

Edit `.env` and set:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
WEBHOOK_PROVIDER_URL=https://webhook.site/your-uuid-here
WEBHOOK_TIMEOUT=5
```

Visit [webhook.site](https://webhook.site) to grab your unique URL and configure the response to return:

```json
{ "messageId": "uuid-here", "status": "accepted", "timestamp": "2026-05-17T10:00:00Z" }
```

with status `202 Accepted`.

### 2. Boot the stack

```bash
docker compose up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### 3. Run the worker and scheduler

In two more terminals (or via supervisord inside the container):

```bash
# Worker — drains Redis queues
./vendor/bin/sail artisan queue:work --queue=high,default,low

# Scheduler — fires app:process-notifications every minute
./vendor/bin/sail artisan schedule:work

# Reverb
./vendor/bin/sail artisan reverb:start --host=0.0.0.0 --port=8080
```

### 4. Send a test notification

```bash
curl -X POST http://localhost/api/notifications \
  -H 'Content-Type: application/json' \
  -d '{
    "recipient": "+905551234567",
    "channel": "sms",
    "content": "Your code is 123456",
    "priority": "high"
  }'
```

### 5. Run tests

```php
./vendor/bin/sail test
```

---

## API reference

Full OpenAPI specification is in [`openapi.yaml`](openapi.yaml). The surface is:

| Method | Path | Purpose |
|--------|------|---------|
| `POST` | `/api/notifications`        | Create one notification |
| `POST` | `/api/notifications/batch`  | Create up to 1000 |
| `GET`  | `/api/notifications`        | List with filters + cursor pagination |
| `DELETE` | `/api/notifications/{id}` | Cancel (soft-delete) a `pending` notification |
| `GET`  | `/up`                       | Health probe (Laravel built-in) |

### Examples

#### Create a single notification

```bash
curl -X POST http://localhost/api/notifications \
  -H 'Content-Type: application/json' \
  -d '{
    "recipient": "alice@example.com",
    "channel": "email",
    "content": "Welcome aboard",
    "priority": "default"
  }'
```

#### Create a batch

```bash
curl -X POST http://localhost/api/notifications/batch \
  -H 'Content-Type: application/json' \
  -d '{
    "notifications": [
      { "recipient": "+905551111111", "channel": "sms",   "content": "Hi from sms",   "priority": "high" },
      { "recipient": "bob@example.com", "channel": "email", "content": "Hi from email" }
    ]
  }'
```

## Configuration

### Environment variables

| Variable | Purpose | Default |
|---|---|---|
| `WEBHOOK_PROVIDER_URL` | webhook.site URL the worker POSTs to | — (required) |
| `WEBHOOK_TIMEOUT` | HTTP timeout in seconds | `5` |
| `QUEUE_CONNECTION` | Must be `redis` for atomic rate limiting | `redis` |
| `CACHE_STORE` | Must be `redis` for atomic rate limiting | `redis` |
| `BROADCAST_CONNECTION` | Reverb for WebSocket updates (scaffolded) | `reverb` |
| `RATE_LIMIT_SMS` | Max sms per second across the fleet | `100` |
| `RATE_LIMIT_EMAIL` | Max email per second | `100` |
| `RATE_LIMIT_PUSH` | Max push per second | `100` |


## Design decisions

### Single dispatch path via scheduler

Both endpoints persist rows and return. The `app:process-notifications` Artisan command — run every minute by the Laravel scheduler — is the sole source of `ProcessNotification` job dispatch. This gives:

- One place that knows about queues, priorities, and dispatch policy.
- A natural home for scheduled notifications (just persist with future `scheduled_at`).
- Easier observability — every dispatched job comes from a known source.

Trade-off: up to ~60 seconds of latency on immediate sends, accepted in exchange for the simplification.

### Rate limiting via Redis token bucket

`Illuminate\Support\Facades\RateLimiter::attempt()` provides a per-key counter with TTL. Workers across the entire fleet share the counter through Redis. When tokens are exhausted, the job uses `$this->release(1)` — re-queues with a 1-second delay **without** burning a retry attempt. Failure ≠ rate-limited; this separation is important so flow-control doesn't burn through `$tries`.

### ULID batch IDs, bigint notification IDs

Batches use ULIDs (sortable, externally safe, no need for a parent table since `batch_id` is just a label). Individual notifications use bigint auto-increment for fastest joins and minimal index size. The two identifier types coexist intentionally.

### Filtering via a trait + scope

`App\Traits\HasFilters` exposes a `filter(array)` scope on the model. `NotificationController::index` does `Notification::filter($request->validated())->cursorPaginate(25)` — controller stays two lines, filter logic stays in the model layer, and the same scope is unit-testable in isolation.

### Validation lives in form requests, not the controller

`StoreNotificationRequest`, `StoreNotificationBatchRequest`, and `NotificationRequest` each declare their own `rules()` and are auto-resolved by Laravel before the controller method runs. `RecipientMatchesChannel` is a custom `DataAwareRule` that picks recipient-format validation based on the sibling `channel` field — e.g., `email` channel triggers Laravel's `email` rule, `sms` triggers an E.164 regex.

---

