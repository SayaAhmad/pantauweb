<?php

namespace App\Monitoring;

use Illuminate\Support\Carbon;

final class CheckOutcome
{
    public function __construct(
        public readonly bool $up,
        public readonly ?int $httpStatus = null,
        public readonly ?int $responseTimeMs = null,
        public readonly ?string $error = null,
        public readonly ?Carbon $checkedAt = null,
        public readonly ?Carbon $sslExpiresAt = null,
        public readonly ?string $sslIssuer = null,
    ) {}

    public static function up(
        ?int $httpStatus = null,
        ?int $responseTimeMs = null,
        ?Carbon $sslExpiresAt = null,
        ?string $sslIssuer = null,
        ?Carbon $checkedAt = null,
    ): self {
        return new self(
            up: true,
            httpStatus: $httpStatus,
            responseTimeMs: $responseTimeMs,
            checkedAt: $checkedAt,
            sslExpiresAt: $sslExpiresAt,
            sslIssuer: $sslIssuer,
        );
    }

    public static function down(
        string $error,
        ?int $httpStatus = null,
        ?int $responseTimeMs = null,
        ?Carbon $sslExpiresAt = null,
        ?string $sslIssuer = null,
        ?Carbon $checkedAt = null,
    ): self {
        return new self(
            up: false,
            httpStatus: $httpStatus,
            responseTimeMs: $responseTimeMs,
            error: $error,
            checkedAt: $checkedAt,
            sslExpiresAt: $sslExpiresAt,
            sslIssuer: $sslIssuer,
        );
    }
}
