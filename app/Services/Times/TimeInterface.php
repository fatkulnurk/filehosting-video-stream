<?php

namespace App\Services\Times;

interface TimeInterface
{
    public function getExpiredTime(): string;

    public function checkExpiredTime(string $encodeDateTimeString): bool;
}
