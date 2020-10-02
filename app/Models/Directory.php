<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Directory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $guarded = [];

    public function directory()
    {
        return $this->hasMany(Directory::class, 'directory_parrent_id', 'id');
    }

    public function childrenDirectory()
    {
        return $this->hasMany(Directory::class, 'directory_parrent_id', 'id')
            ->with(['childrenDirectory']);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }
}
