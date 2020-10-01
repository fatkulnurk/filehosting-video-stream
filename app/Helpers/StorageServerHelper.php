<?php

namespace App\Helpers;

use App\Models\StorageServer;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class StorageServerHelper
{
    /**
     * @return StorageServer[]|Collection
     */
    public static function getAll(): Collection
    {
        return StorageServer::all();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public static function getActive()
    {
        $storageServer = StorageServer::where('is_default', true)
            ->first();

        if (!blank($storageServer)) {
            return $storageServer;
        }

        throw new Exception('Server Default Not Found.');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public static function getActiveID()
    {
        try {
            return self::getActive()->id;
        } catch (Exception $e) {
            throw new Exception('Server Default Not Found.');
        }
    }

    public static function getActiveKey()
    {
        try {
            return self::getActive()->name;
        } catch (Exception $e) {
            throw new Exception('Server Default Not Found.');
        }
    }
    public static function getActiveName()
    {
        try {
            return self::getActive()->name;
        } catch (Exception $e) {
            throw new Exception('Server Default Not Found.');
        }
    }
}
