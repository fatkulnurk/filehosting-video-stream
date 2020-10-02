<?php


namespace App\Services\Times;


use App\Models\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class Time implements TimeInterface
{

    /**
     * @return string
     */
    public function getExpiredTime(): string
    {
        $time = Carbon::now()->addHours(24)->toDateTimeString();

        return base64_encode(Crypt::encrypt($time));
    }

    public function checkExpiredTime(string $encodeDateTimeString): bool
    {
        $decryptTime = Crypt::decrypt($encodeDateTimeString);

        // if decrypt false
        if (blank($decryptTime)) {
            return false;
        }

        $expiredTime = Carbon::parse($decryptTime);
        $now = Carbon::now();

        return $now->lte($expiredTime);
    }
}
