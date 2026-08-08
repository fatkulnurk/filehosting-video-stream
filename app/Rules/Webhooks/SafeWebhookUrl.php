<?php

declare(strict_types=1);

namespace App\Rules\Webhooks;

use App\Rules\Downloads\SafeDownloadUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeWebhookUrl implements ValidationRule
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

        if (! SafeDownloadUrl::isHostSafe($value)) {
            $fail('The :attribute points to a private or blocked address.');
        }
    }

    public static function isHostSafe(string $url): bool
    {
        return SafeDownloadUrl::isHostSafe($url);
    }
}
