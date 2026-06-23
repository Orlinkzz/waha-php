# orlinkzz/waha-php

PHP client for [WAHA](https://waha.devlike.pro) (WhatsApp HTTP API) with built-in **anti-banned flow** — otomatis handle `sendSeen → startTyping → delay → stopTyping → sendText`.

> ⚠️ **Disclaimer**: This is an independent PHP client library for WAHA API, developed and maintained separately.

## 🎉 Good News: WAHA is Free and Open Source!

The WAHA project that this client connects to is now completely **FREE and OPEN SOURCE**!

> 👉 **Support WAHA**: If you find value in the WAHA project, consider supporting it with the new [Community tier ($5/mo)](https://waha.devlike.pro/support-us/) on Patreon, Boosty, or Crypto to help keep the project sustainable.

## Requirements

- PHP 8.1+
- Guzzle 7+
- WAHA instance running (Docker)

## Installation

```bash
composer require orlinkzz/waha-php
```

## Setup

### Tanpa Framework (Pure PHP)

```php
use Orlinkzz\Waha\WahaClient;
use Orlinkzz\Waha\WahaConfig;

$config = new WahaConfig(
    baseUrl: 'http://localhost:3000',
    apiKey:  'your-api-key',
    session: 'default',
);

$waha = new WahaClient($config);
```

### Laravel

Publish config:

```bash
php artisan vendor:publish --tag=waha-config
```

Tambah ke `.env`:

```env
WAHA_BASE_URL=https://waha.yourdomain.com
WAHA_API_KEY=your-secret-key
WAHA_SESSION=default
```

Gunakan via Facade:

```php
use Orlinkzz\Waha\Laravel\WahaFacade as Waha;
```

---

## Usage

### Reply ke Pesan Masuk (Anti-Banned Flow Otomatis)

```php
// Otomatis: sendSeen → startTyping → delay → stopTyping → sendText
$waha->reply('628123456789@c.us', 'Halo! Ada yang bisa kami bantu?');
```

### Kirim via OutgoingMessage DTO

```php
use Orlinkzz\Waha\Message\OutgoingMessage;

$message = OutgoingMessage::to('+628123456789', 'Halo {name}!')
    ->personalize('Budi');  // replace {name} + variasi spasi

$waha->send($message);
```

### Kirim Gambar

```php
$waha->sendImage('628123456789@c.us', 'https://example.com/banner.jpg', 'Promo spesial!');
```

### Kirim File

```php
$waha->sendFile('628123456789@c.us', 'https://example.com/invoice.pdf', 'invoice.pdf');
```

---

### Bulk / Broadcast — Aman dengan Delay Otomatis

Package ini otomatis handle:
- Delay acak 30–60 detik antar pesan
- Pause antar batch (default: 60–120 detik setiap 4 pesan)
- Typing simulation per pesan
- Error per-pesan tidak stop keseluruhan

```php
use Orlinkzz\Waha\Message\OutgoingMessage;

$contacts = [
    ['phone' => '628111111111', 'name' => 'Budi'],
    ['phone' => '628222222222', 'name' => 'Siti'],
    ['phone' => '628333333333', 'name' => 'Andi'],
    // ...
];

$messages = array_map(fn($c) =>
    OutgoingMessage::to($c['phone'], 'Halo {name}, ada promo spesial untuk Anda!')
        ->personalize($c['name']),
    $contacts
);

$result = $waha->sendBulk(
    messages: $messages,

    onEach: function ($message, $response, $index) {
        echo "✅ Sent to {$message->chatId} ({$index})\n";
    },

    onBatchPause: function ($batchNumber, $pauseSeconds) {
        echo "⏸️  Batch {$batchNumber} selesai, pause {$pauseSeconds} detik...\n";
    }
);

echo "Sukses: {$result->successCount()} / {$result->total}\n";

if ($result->hasFailures()) {
    foreach ($result->failures() as $fail) {
        echo "❌ {$fail['chatId']}: {$fail['error']}\n";
    }
}
```

### Di Laravel — Pakai Queue untuk Bulk

Untuk blast yang sangat besar, wrap `sendBulk` dalam Laravel Job agar tidak timeout di request:

```php
// app/Jobs/SendWhatsappBlast.php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Orlinkzz\Waha\Laravel\WahaFacade as Waha;
use Orlinkzz\Waha\Message\OutgoingMessage;

class SendWhatsappBlast implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 jam

    public function __construct(private array $contacts, private string $template) {}

    public function handle(): void
    {
        $messages = array_map(fn($c) =>
            OutgoingMessage::to($c['phone'], $this->template)
                ->personalize($c['name']),
            $this->contacts
        );

        $result = Waha::sendBulk($messages);

        // Log result...
    }
}

// Dispatch:
SendWhatsappBlast::dispatch($contacts, 'Halo {name}, ada info penting!');
```

---

### Session Management

```php
// Start session (scan QR di dashboard WAHA)
$waha->startSession();

// Cek status
$status = $waha->getSessionStatus();

// List semua session
$sessions = $waha->listSessions();

// Stop session
$waha->stopSession();
```

---

## Configuration

| Key | Default | Keterangan |
|-----|---------|------------|
| `base_url` | `http://localhost:3000` | URL WAHA instance |
| `api_key` | — | API key WAHA |
| `session` | `default` | Nama session WhatsApp |
| `typing_delay_min` | `1000` ms | Min delay simulate typing |
| `typing_delay_max` | `3000` ms | Max delay simulate typing |
| `bulk_delay_min` | `30000` ms | Min delay antar pesan bulk |
| `bulk_delay_max` | `60000` ms | Max delay antar pesan bulk |
| `bulk_batch_size` | `4` | Jumlah pesan per batch |
| `bulk_batch_pause_min` | `60` detik | Min pause antar batch |
| `bulk_batch_pause_max` | `120` detik | Max pause antar batch |

---

## Publish ke Packagist

1. Push ke GitHub: `github.com/orlinkzz/waha-php`
2. Daftar di [packagist.org](https://packagist.org) → Submit Package
3. Aktifkan GitHub webhook untuk auto-update

---

## Database Migration Support

This package includes database migration support for both MySQL and PostgreSQL databases. The migration system allows you to set up tables for storing session data, messages, contacts, and message logs.

### Supported Tables

- `waha_sessions`: Stores WhatsApp session information
- `waha_contacts`: Stores contact information
- `waha_messages`: Stores message history
- `waha_message_logs`: Stores message sending logs

### Manual SQL Import

SQL files are provided for manual database setup:

- **MySQL**: `database/sql/mysql_schema.sql`
- **PostgreSQL**: `database/sql/postgres_schema.sql`

### Laravel Integration

For Laravel applications, you can run the migrations using the Artisan command:

```bash
# Run migrations
php artisan waha:migrate --force

# Rollback migrations
php artisan waha:migrate --rollback --force

# Specify database driver (if auto-detection fails)
php artisan waha:migrate --driver=mysql --force
```

### Configuration

Add these environment variables to your `.env` file:

```env
# Database configuration for WAHA
WAHA_DB_DRIVER=mysql        # mysql or pgsql
WAHA_DB_HOST=127.0.0.1
WAHA_DB_PORT=3306          # 3306 for MySQL, 5432 for PostgreSQL
WAHA_DB_DATABASE=waha
WAHA_DB_USERNAME=root
WAHA_DB_PASSWORD=
WAHA_DB_PREFIX=
```

### Programmatic Usage

You can also run migrations programmatically:

```php
use Orlinkzz\Waha\Database\MigrationManager;
use PDO;

// Create PDO connection
$pdo = new PDO($dsn, $username, $password, $options);

// Create migration manager
$manager = new MigrationManager($pdo);

// Run migrations
$results = $manager->migrate();

// Or get SQL for manual execution
$sql = $manager->getMigrationSql();
```

## License

MIT License

Copyright (c) 2026 Orlinkzz

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

This package is part of the open source WAHA ecosystem. Contributions and improvements are welcome from the community.
