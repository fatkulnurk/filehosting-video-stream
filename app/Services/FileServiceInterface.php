<?php

namespace App\Services;

use Illuminate\Http\Request;

interface FileServiceInterface
{
    public function store(Request $request, R$directoryID);
}
