Here’s your updated README with **Sanctum authentication clearly marked as required** (full file, ready to copy–paste):

````markdown
# 📩 SMS Scheduler API

RESTful API for sending **instant** and **scheduled** SMS messages.  
Built with Laravel, queues, and a clean JSON-based workflow.

---

## ✨ Features

- ✅ Send SMS instantly to one or multiple recipients
- 🕒 Schedule SMS messages for a future date/time
- 📦 Store each SMS batch with status (`pending`, `queued`, `sent`, `failed`)
- 🔄 Queue-powered processing (no blocking HTTP requests)
- 🧾 View SMS history and filter by status

---

## 🧰 Tech Stack

- **Framework:** Laravel (PHP)
- **Database:** MySQL / PostgreSQL
- **Queue:** Database / Redis
- **SMS Gateway:** nigeriabulksms.com
- **Auth:** Laravel Sanctum (all endpoints are protected)

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/uhweka13/sms-scheduler-api.git
cd sms-scheduler-api
````

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

Copy the example environment file and generate an app key:

```bash
cp .env.example .env
php artisan key:generate
```

Update the following keys in `.env`:

```env
APP_NAME="SMS Scheduler API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Africa/Lagos

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_scheduler
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database   # or redis

# Example SMS gateway config – update to match your nigeriabulksms credentials
SMS_PROVIDER_URL="https://portal.nigeriabulksms.com/api"
SMS_USERNAME=nigeriabulksms username
SMS_PASSWORD=nigeriabulksms password
```

### 4. Run migrations

```bash
php artisan migrate
```

If you are using the **database queue**, also run:

```bash
php artisan queue:table
php artisan migrate
```

### 5. Start the development server

```bash
php artisan serve
```

Your API will be available at:

```text
http://127.0.0.1:8000
```

### 6. Start the queue worker

The queue worker processes the SMS jobs (both instant and scheduled):

```bash
php artisan queue:work
```

> If the queue worker is not running, scheduled and queued SMS will not be sent.

---

## 🔐 Authentication (Required)

All API endpoints are **protected** and **require authentication** using **Laravel Sanctum**.

To access any endpoint:

1. Authenticate and obtain a Sanctum token (e.g. via your login route).
2. Include the token in the `Authorization` header as a Bearer token.

Example:

```http
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
```

> Requests without a valid Sanctum token will be rejected.

---

## 📡 API Endpoints

Base URL (example):

```text
/api/v1
```

> All endpoints below require a valid Sanctum token in the `Authorization` header.

---

### 1. Send SMS (Instant or Scheduled)

**Endpoint**

```http
POST /api/v1/sms/send
```

**Request Body**

```json
{
  "recipients": ["081xxxxxx", "080xxxxxxx"],
  "sender": "MyBrand",
  "message": "Hello from SMS Scheduler API!",
  "send_at": "2025-11-21 21:47:00"
}
```

* `recipients` – array of recipient phone numbers (strings)
* `sender` – sender ID, max **11 characters**
* `message` – SMS message body
* `send_at`:

  * `null` → send immediately
  * datetime string (`Y-m-d H:i:s`) → schedule for later (uses `APP_TIMEZONE`)

**Example: Send Immediately**

```json
{
  "recipients": ["081xxxxxx"],
  "sender": "MyBrand",
  "message": "This SMS will be sent right now.",
  "send_at": null
}
```

**Success Response (example)**

```json
{
  "status": "success",
  "message": "SMS submitted successfully",
  "data": ""
}
```

---

### 2. List SMS History

**Endpoint**

```http
GET /api/v1/sms
```

**Query Parameters (optional)**

* `status` – `pending`, `queued`, `sent`, `failed`

**Example**

```http
GET /api/v1/sms?status=queued
```

**Response (example)**

```json
{
    "data": {
        "data": [
            {
                "id": 4,
                "recipients": [
                    "081xxxxxx",
                    "081xxxxxx"
                ],
                "message": "Message",
                "send_at": "2025-11-21T21:20:00.000000Z",
                "status": "sent",
                "provider_response": "{\"status\":\"OK\",\"count\":2,\"price\":16}",
                "sender": "senderval",
                "created_at": "2025-11-21T21:19:26.000000Z",
                "updated_at": "2025-11-21T21:20:04.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 15,
            "total": 1,
            "from": 1,
            "to": 1
        }
    },
    "message": "Sms fetched",
    "code": 200
}
```

---

### 3. Get a Single SMS Batch

**Endpoint**

```http
GET /api/v1/sms/{id}
```

**Response (example)**

```json
{
    "data": {
        "id": 1,
        "recipients": [
            "081xxxxxx",
            "081xxxxx"
        ],
        "message": "Test message",
        "send_at": null,
        "status": "pending",
        "provider_response": null,
        "sender": "MyApp",
        "created_at": "2025-11-22T06:43:21.000000Z",
        "updated_at": "2025-11-22T06:43:21.000000Z"
    },
    "message": "Sms fetched",
    "code": 200
}
```

---

## 🗄 Database Schema

Main SMS table (example name: `sms_models`):

* `id` – primary key
* `recipients` – `json`
* `message` – `text`
* `sender` – `string`
* `send_at` – `timestamp` (nullable)
* `status` – `enum('pending', 'queued', 'sent', 'failed')`
* `created_at` / `updated_at` – timestamps

Example migration snippet:

```php
Schema::create('sms_models', function (Blueprint $table) {
    $table->id();
    $table->json('recipients');
    $table->text('message');
    $table->string('sender');
    $table->timestamp('send_at')->nullable();
    $table->enum('status', ['pending', 'queued', 'sent', 'failed'])->default('pending');
    $table->timestamps();
});
```

---

## ⚙️ How Scheduling Works (Internally)

1. Client calls `POST /api/v1/sms/send` with the request data.

2. The API creates a new record in `sms_models` with:

   * `recipients`
   * `sender`
   * `message`
   * `send_at`
   * `status`:

     * `pending` if `send_at` is `null`
     * `queued` if `send_at` is a future datetime

3. A job (e.g. `SendScheduledSms`) is dispatched:

   * If `send_at` is provided → job is delayed until that time:

     ```php
     SendScheduledSms::dispatch($batch)->delay($sendAt);
     ```

   * Else → job runs immediately:

     ```php
     SendScheduledSms::dispatch($batch);
     ```

4. The job:

   * Sends the SMS via the configured gateway.
   * Updates `status` to `sent` or `failed`.

---

## 🧪 Tests

```bash
php artisan test
```

---

## 👤 Author

* **Name:** Uhweka Danjuma
* **Email:** [uhweka@gmail.com](mailto:uhweka@gmail.com)
* **GitHub:** [@uhweka13](https://github.com/uhweka13)

