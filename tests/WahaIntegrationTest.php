#!/usr/bin/env php
<?php

/**
 * WAHA Integration Test Script
 * Jalankan setelah WAHA running dan session sudah scan QR.
 *
 * Usage:
 *   php tests\WahaIntegrationTest.php --url=http://localhost:3000 --key=YOUR_API_KEY --phone=628123456789
 *
 * Optional:
 *   --session=default   (default: default)
 *   --skip-send         (skip kirim pesan, hanya test koneksi & session)
 */

// ─── Parse args ──────────────────────────────────────────────────────────────
$opts = getopt('', ['url:', 'key:', 'phone:', 'session:', 'skip-send']);

$baseUrl = $opts['url']     ?? getenv('WAHA_BASE_URL') ?: 'http://localhost:3000';
$apiKey  = $opts['key']     ?? getenv('WAHA_API_KEY')  ?: '';
$phone   = $opts['phone']   ?? getenv('TEST_PHONE')    ?: '';
$session = $opts['session'] ?? 'default';
$skipSend = isset($opts['skip-send']);

if (empty($apiKey)) {
    echo "❌ --key wajib diisi (atau set env WAHA_API_KEY)\n";
    exit(1);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function apiGet(string $url, string $apiKey): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["X-Api-Key: $apiKey", "Content-Type: application/json"],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body ?: '{}', true) ?? [], 'error' => $err];
}

function apiPost(string $url, string $apiKey, array $data): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ["X-Api-Key: $apiKey", "Content-Type: application/json"],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body ?: '{}', true) ?? [], 'error' => $err];
}

function pass(string $label): void { echo "✅ $label\n"; }
function fail(string $label, string $reason = ''): void { echo "❌ $label" . ($reason ? " → $reason" : '') . "\n"; }
function info(string $msg): void { echo "   ℹ️  $msg\n"; }
function section(string $title): void { echo "\n── $title " . str_repeat('─', max(0, 50 - strlen($title))) . "\n"; }

$passed = 0;
$failed = 0;

function check(bool $ok, string $label, string $reason = ''): void {
    global $passed, $failed;
    if ($ok) { pass($label); $passed++; }
    else      { fail($label, $reason); $failed++; }
}

// ─── Tests ───────────────────────────────────────────────────────────────────

echo "\n🧪 WAHA Integration Test\n";
echo "   URL    : $baseUrl\n";
echo "   Session : $session\n";
echo "   Phone   : " . ($phone ?: '(skip send tests)') . "\n";

// 1. Connectivity
section('1. Connectivity');
$r = apiGet("$baseUrl/api/version", $apiKey);
check($r['code'] === 200, 'API reachable', $r['error'] ?: "HTTP {$r['code']}");
if (!empty($r['body']['version'])) {
    info("WAHA version: " . $r['body']['version']);
}

// 2. Auth
section('2. Auth (API Key)');
$rBad = apiGet("$baseUrl/api/sessions", 'wrong-key-xxx');
check(
    in_array($rBad['code'], [401, 403]),
    'Invalid API key rejected',
    "Expected 401/403, got {$rBad['code']}"
);
$rGood = apiGet("$baseUrl/api/sessions", $apiKey);
check($rGood['code'] === 200, 'Valid API key accepted', "HTTP {$rGood['code']}");

// 3. Session status
section('3. Session');
$rSession = apiGet("$baseUrl/api/sessions/$session", $apiKey);
check(in_array($rSession['code'], [200, 404]), 'Session endpoint reachable');

$status = $rSession['body']['status'] ?? 'UNKNOWN';
info("Session status: $status");

if ($status === 'WORKING') {
    pass('Session is WORKING (QR sudah scan)');
    $passed++;
} elseif ($status === 'SCAN_QR_CODE') {
    fail('Session perlu scan QR dulu', 'Buka dashboard WAHA dan scan QR');
    $failed++;
} else {
    fail("Session status tidak expected: $status");
    $failed++;
}

// 4. Send flow (hanya jika session WORKING dan phone diisi)
if (!$skipSend && !empty($phone) && $status === 'WORKING') {
    $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';

    section('4. Anti-Banned Send Flow');

    // sendSeen
    $r = apiPost("$baseUrl/api/sendSeen", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'sendSeen', "HTTP {$r['code']}");

    // startTyping
    $r = apiPost("$baseUrl/api/startTyping", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'startTyping', "HTTP {$r['code']}");

    // Wait
    info('Simulating typing delay (5s)...');
    sleep(5);

    // stopTyping
    $r = apiPost("$baseUrl/api/stopTyping", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'stopTyping', "HTTP {$r['code']}");

    // sendText
    $testMsg = '[TEST] waha-php integration test - ' . date('H:i:s');
    $r = apiPost("$baseUrl/api/sendText", $apiKey, [
        'session' => $session,
        'chatId'  => $chatId,
        'text'    => $testMsg,
    ]);
    check(in_array($r['code'], [200, 201]), 'sendText', "HTTP {$r['code']} " . json_encode($r['body']));

    if (in_array($r['code'], [200, 201])) {
        info('Message ID: ' . ($r['body']['id'] ?? 'n/a'));
    }

    // sendSeen
    $r = apiPost("$baseUrl/api/sendSeen", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'sendSeen', "HTTP {$r['code']}");

    // startTyping
    $r = apiPost("$baseUrl/api/startTyping", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'startTyping', "HTTP {$r['code']}");

    // Wait
    info('Simulating typing delay (5s)...');
    sleep(5);

    // stopTyping
    $r = apiPost("$baseUrl/api/stopTyping", $apiKey, ['session' => $session, 'chatId' => $chatId]);
    check(in_array($r['code'], [200, 201, 204]), 'stopTyping', "HTTP {$r['code']}");

    // sendImage (opsional, pakai public URL)
    section('5. sendImage');
    $r = apiPost("$baseUrl/api/sendImage", $apiKey, [
        'session' => $session,
        'chatId'  => $chatId,
        'file'    => ['url' => 'https://waha.devlike.pro/images/logo.svg'],
        'caption' => '[TEST] Image dari waha-php '. date('H:i:s'),
    ]);
    check(in_array($r['code'], [200, 201]), 'sendImage', "HTTP {$r['code']}");

} else {
    section('4. Send Flow');
    if ($skipSend) {
        info('Skipped (--skip-send)');
    } elseif (empty($phone)) {
        info('Skipped (--phone tidak diisi)');
    } else {
        info('Skipped (session belum WORKING)');
    }
}

// ─── Summary ─────────────────────────────────────────────────────────────────
section('Result');
$total = $passed + $failed;
echo "   Total  : $total\n";
echo "   Passed : $passed ✅\n";
echo "   Failed : $failed ❌\n\n";

if ($failed === 0) {
    echo "🎉 Semua test passed! Package siap dipakai.\n\n";
    exit(0);
} else {
    echo "⚠️  Ada $failed test yang gagal. Cek log di atas.\n\n";
    exit(1);
}
