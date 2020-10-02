<?php

namespace App\Models;

use App\Helpers\SizeHelper;
use App\Services\Times\Time;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class File extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['size_format', 'path_hash', 'expired_time'];

    protected $with = [
        'storageServer'
    ];


    public function getSizeFormatAttribute($value)
    {
        return (new SizeHelper())->convert($this->size);
    }

    public function getPathHashAttribute($value)
    {
        return base64_encode(Crypt::encrypt($this->path));
    }

    public function getExpiredTimeAttribute($value)
    {
        return (new Time())->getExpiredTime();
    }

    public function storageServer()
    {
        return $this->belongsTo(StorageServer::class);
    }
}
