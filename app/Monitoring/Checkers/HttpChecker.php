<?php

namespace App\Monitoring\Checkers;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Monitoring\CheckOutcome;
use Illuminate\Support\Carbon;

final class HttpChecker
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $timeout = max(1, $monitor->timeout_seconds);
        $verified = $this->request($monitor, $timeout, verify: true);

        // cURL menolak handshake saat sertifikat bermasalah. Ulangi tanpa verifikasi
        // hanya untuk mengambil detail sertifikat, bukan untuk menyatakan monitor up.
        if ($verified['errno'] !== 0 && $this->isTlsError($verified['error'])) {
            $probe = $this->request($monitor, $timeout, verify: false);
            $verified['cert'] = $probe['cert'] ?? $verified['cert'];
        }

        $expiresAt = $this->certificateExpiry($verified['cert']);
        $issuer = $this->certificateIssuer($verified['cert']);

        if ($verified['errno'] !== 0) {
            $message = $this->describeCurlError($verified['errno'], $verified['error']);

            if ($expiresAt !== null && $expiresAt->isPast()) {
                $message = 'Sertifikat SSL kedaluwarsa sejak '.$expiresAt->translatedFormat('d M Y');
            }

            return CheckOutcome::down(
                error: $message,
                httpStatus: $verified['status'] ?: null,
                responseTimeMs: $verified['time_ms'],
                sslExpiresAt: $expiresAt,
                sslIssuer: $issuer,
            );
        }

        if ($expiresAt !== null && $expiresAt->isPast()) {
            return CheckOutcome::down(
                error: 'Sertifikat SSL kedaluwarsa sejak '.$expiresAt->translatedFormat('d M Y'),
                httpStatus: $verified['status'],
                responseTimeMs: $verified['time_ms'],
                sslExpiresAt: $expiresAt,
                sslIssuer: $issuer,
            );
        }

        $status = (int) $verified['status'];
        $expected = $monitor->expected_status;

        $statusMatches = $expected !== null
            ? $status === $expected
            : ($status >= 200 && $status < 400);

        if (! $statusMatches) {
            return CheckOutcome::down(
                error: $expected !== null
                    ? "Kode status HTTP {$status} tidak sesuai harapan {$expected}"
                    : "Kode status HTTP {$status} di luar rentang 200–399",
                httpStatus: $status,
                responseTimeMs: $verified['time_ms'],
                sslExpiresAt: $expiresAt,
                sslIssuer: $issuer,
            );
        }

        $keyword = trim((string) $monitor->expected_keyword);

        if ($keyword !== '' && $monitor->method !== 'HEAD') {
            $body = (string) $verified['body'];

            if (mb_stripos($body, $keyword) === false) {
                return CheckOutcome::down(
                    error: "Kata kunci \"{$keyword}\" tidak ditemukan pada respons",
                    httpStatus: $status,
                    responseTimeMs: $verified['time_ms'],
                    sslExpiresAt: $expiresAt,
                    sslIssuer: $issuer,
                );
            }
        }

        return CheckOutcome::up(
            httpStatus: $status,
            responseTimeMs: $verified['time_ms'],
            sslExpiresAt: $expiresAt,
            sslIssuer: $issuer,
        );
    }

    /**
     * @return array{errno: int, error: string, status: int, time_ms: int|null, body: string|false, cert: array<int, array<string, string>>}
     */
    private function request(Monitor $monitor, int $timeout, bool $verify): array
    {
        $method = strtoupper($monitor->method ?: 'GET');

        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $monitor->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            CURLOPT_CERTINFO => true,
            CURLOPT_USERAGENT => 'Pantau/1.0 (+monitoring)',
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_NOBODY => $method === 'HEAD',
            CURLOPT_ENCODING => '',
        ]);

        $body = curl_exec($handle);
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $timeMs = (int) round(((float) curl_getinfo($handle, CURLINFO_TOTAL_TIME)) * 1000);

        $cert = [];
        $rawCert = curl_getinfo($handle, CURLINFO_CERTINFO);

        if (is_array($rawCert) && isset($rawCert[0]) && is_array($rawCert[0])) {
            $cert = $rawCert[0];
        }

        return [
            'errno' => $errno,
            'error' => $error,
            'status' => $status,
            'time_ms' => $timeMs > 0 ? $timeMs : null,
            'body' => $body,
            'cert' => $cert,
        ];
    }

    private function isTlsError(string $error): bool
    {
        $error = strtolower($error);

        return str_contains($error, 'ssl')
            || str_contains($error, 'certificate')
            || str_contains($error, 'tls');
    }

    private function describeCurlError(int $errno, string $error): string
    {
        return match ($errno) {
            28 => 'Pemeriksaan melewati batas waktu',
            6 => 'Nama host tidak dapat diselesaikan (DNS)',
            7 => 'Koneksi ke server tidak dapat dibuat',
            35, 51, 60 => 'Verifikasi sertifikat SSL gagal: '.$error,
            default => trim($error) !== ''
                ? 'Pemeriksaan gagal: '.$error
                : 'Pemeriksaan gagal (kode cURL '.$errno.')',
        };
    }

    /**
     * @param  array<string, string>|array<int, array<string, string>>  $cert
     */
    private function certificateExpiry(array $cert): ?Carbon
    {
        $value = $this->certificateValue($cert, ['Expire date', 'Expire Date', 'expire date']);

        if ($value === null) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : Carbon::createFromTimestampUTC($timestamp);
    }

    /**
     * @param  array<string, string>|array<int, array<string, string>>  $cert
     */
    private function certificateIssuer(array $cert): ?string
    {
        $value = $this->certificateValue($cert, ['Issuer', 'issuer']);

        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, 255);
    }

    /**
     * @param  array<string, string>|array<int, array<string, string>>  $cert
     * @param  array<int, string>  $keys
     */
    private function certificateValue(array $cert, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($cert[$key]) && is_string($cert[$key]) && $cert[$key] !== '') {
                return $cert[$key];
            }
        }

        return null;
    }
}
