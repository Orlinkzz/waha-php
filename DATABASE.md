# Database Logging — Panduan Lengkap

Package ini mendukung logging otomatis ke database untuk setiap request ke WAHA API.

**Support:** MySQL / MariaDB · PostgreSQL

---

## Quick Start

### 1. Buat Database

```bash
# MySQL
mysql -u root -p waha < database/sql/mysql_schema.sql

# PostgreSQL
psql -U postgres -d waha -f database/sql/postgres_schema.sql
```

### 2. Konfigurasi

**Laravel** — tambahkan di `.env`:

```env
WAHA_DB_DRIVER=mysql
WAHA_DB_HOST=127.0.0.1
WAHA_DB_PORT=3306
WAHA_DB_DATABASE=waha
WAHA_DB_USERNAME=root
WAHA_DB_PASSWORD=
```

> 💡 Jika tidak diset, otomatis mengikuti database default Laravel.

**Pure PHP:**

```php
$config = new WahaConfig(
    baseUrl: 'http://localhost:3000',
    apiKey:  'your-api-key',
    database: [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'database' => 'waha',
        'username' => 'root',
        'password' => '',
    ]
);
```

### 3. Jalankan Migration (Laravel)

```bash
php artisan waha:migrate --force
php artisan waha:migrate --rollback --force   # rollback
```

### 4. Selesai!

Setiap request WAHA sekarang otomatis tercatat. Tidak perlu kode tambahan.

```php
$waha->sendText('628123456789@c.us', 'Halo!'); // otomatis tercatat di DB
```

---

## Apa yang Tercatat

| Method | Tercatat di |
|--------|-------------|
| `startSession()` / `stopSession()` | `waha_sessions` |
| `sendText()`, `sendImage()`, `sendFile()` | `waha_message_logs` + `waha_contacts` |
| `reply()` | `waha_message_logs` + `waha_contacts` |
| `sendBulk()` | `waha_message_logs` (per pesan) |
| `getMessages()` | `waha_messages` |
| `handleIncomingMessage()` | `waha_messages` + `waha_contacts` |

---

## Tabel Database

| Tabel | Fungsi |
|-------|--------|
| `waha_sessions` | Info session (nama, status, QR, waktu connect/disconnect) |
| `waha_contacts` | Data kontak (phone, nama, blocked, business) |
| `waha_messages` | Riwayat pesan masuk & keluar |
| `waha_message_logs` | Log pengiriman (status: pending → sent/failed) |

---

## Query Data

```php
// Log pesan
$pending = $waha->getMessageLogs('pending');
$sent    = $waha->getMessageLogs('sent');
$failed  = $waha->getMessageLogs('failed');

// Sessions
$sessions = $waha->getSessions();

// Contacts
$contacts = $waha->getContacts();
$contacts = $waha->getContacts('default'); // per session
```

### Akses Repository Langsung

```php
use Orlinkzz\Waha\Database\DatabaseConnection;
use Orlinkzz\Waha\Database\Repositories\MessageLogRepository;

$connection = new DatabaseConnection($dbConfig);
$repo = new MessageLogRepository($connection->getConnection());

$pending = $repo->findByStatus('pending', 100);
```

---

## Webhook

```php
public function handleWebhook(Request $request)
{
    app('waha')->handleIncomingMessage($request->all());
    return response()->json(['status' => 'ok']);
}
```

---

## Error Handling

- Database error **tidak menghentikan** fungsi utama WAHA
- Error dicatat di `error_log` PHP
- Jika database tidak dikonfigurasi, WAHA tetap berjalan normal (tanpa logging)

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Connection refused` | Cek kredensial DB dan pastikan server berjalan |
| `Table doesn't exist` | Jalankan migration atau import SQL manual |
| Migration tidak jalan | `php artisan config:clear` lalu ulang |

---

## Struktur File

```
src/Database/
├── DatabaseConnection.php
├── Migration.php
├── MigrationManager.php
├── Migrations/
│   ├── BaseMigration.php
│   ├── MySqlMigration.php
│   ├── PostgreSqlMigration.php
│   ├── CreateWahaSessionsTable.php
│   ├── CreateWahaContactsTable.php
│   ├── CreateWahaMessagesTable.php
│   └── CreateWahaMessageLogsTable.php
└── Repositories/
    ├── SessionRepository.php
    ├── ContactRepository.php
    ├── MessageRepository.php
    └── MessageLogRepository.php

database/sql/
├── mysql_schema.sql
└── postgres_schema.sql
```
