<?php

namespace App\Monitoring\Checkers;

use App\Models\Monitor;
use App\Monitoring\CheckOutcome;

final class PortChecker
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $timeout = max(1, $monitor->timeout_seconds);
        $host = trim((string) $monitor->host);
        $port = (int) $monitor->port;

        if ($host === '' || $port < 1 || $port > 65535) {
            return CheckOutcome::down('Host atau port tidak valid');
        }

        $errno = 0;
        $errstr = '';

        $startedAt = microtime(true);

        $connection = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
        );

        $elapsed = max(1, (int) round((microtime(true) - $startedAt) * 1000));

        if (is_resource($connection)) {
            fclose($connection);

            return CheckOutcome::up(responseTimeMs: $elapsed);
        }

        $message = trim($errstr) !== ''
            ? $errstr
            : 'Tidak dapat membuka koneksi TCP';

        if ($errno === 110 || str_contains(strtolower($message), 'timed out')) {
            $message = 'Pemeriksaan melewati batas waktu';
        }

        return CheckOutcome::down(
            error: "Port {$port} tidak dapat dijangkau: {$message}",
            responseTimeMs: $elapsed > 0 ? $elapsed : null,
        );
    }
}
