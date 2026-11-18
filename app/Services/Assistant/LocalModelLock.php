<?php

namespace App\Services\Assistant;

use RuntimeException;

/** One inference per local ERP server. Independent of cache drivers and financial/database transactions. */
final class LocalModelLock
{
    public static function acquire(): mixed
    {
        $directory=(string)config('erp_assistant.lock_directory',storage_path('framework/erp-assistant'));
        if(is_link($directory) || (!is_dir($directory) && !@mkdir($directory,0770,true) && !is_dir($directory))) {
            throw new RuntimeException('Local model lock storage is unavailable.');
        }
        $path=$directory.'/inference.lock';
        if(is_link($path))throw new RuntimeException('Unsafe model lock path.');
        $handle=@fopen($path,'c+');
        if(!$handle)throw new RuntimeException('Local model lock is unavailable.');
        if(!flock($handle,LOCK_EX|LOCK_NB)){fclose($handle);return null;}
        return $handle;
    }

    public static function release(mixed $handle): void
    {
        if(is_resource($handle)){try{flock($handle,LOCK_UN);}finally{fclose($handle);}}
    }
}
