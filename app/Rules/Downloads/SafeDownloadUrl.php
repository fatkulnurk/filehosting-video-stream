<?php

declare(strict_types=1);

namespace App\Rules\Downloads;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class SafeDownloadUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid HTTP(S) URL.');

            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        if (! $this->isHostSafe($value)) {
            $fail('The :attribute points to a private or blocked address.');

            return;
        }

        $resolved = $this->resolveRedirects($value);

        if ($resolved && ! $this->isHostSafe($resolved)) {
            $fail('The :attribute redirects to a private or blocked address.');
        }
    }

    private function isHostSafe(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ?: gethostbynamel($host)[0] ?? null;

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }

    private function resolveRedirects(string $url): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            return (string) $response->effectiveUri();
        } catch (\Throwable) {
            return null;
        }
    }
}
