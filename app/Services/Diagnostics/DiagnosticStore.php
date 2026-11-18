<?php

namespace App\Services\Diagnostics;

use RuntimeException;

final class DiagnosticStore
{
    private function directory(): string
    {
        $path=(string)config('erp_diagnostics.state_directory',storage_path('app/erp-diagnostics'));
        if (!is_dir($path) && !@mkdir($path,0770,true) && !is_dir($path)) throw new RuntimeException('Diagnostic audit storage is unavailable.');
        if (is_link($path) || !is_writable($path)) throw new RuntimeException('Diagnostic audit storage is unavailable.');
        return $path;
    }

    /** A file lock remains usable when the application cache is unhealthy. Never clear cache-backed workflow locks. */
    public function exclusive(callable $operation): mixed
    {
        $path=$this->directory().'/actions.lock';
        if(is_link($path))throw new RuntimeException('Unsafe diagnostic lock path.');
        $lock=@fopen($path,'c+');
        if(!$lock)throw new RuntimeException('Diagnostic action lock is unavailable.');
        try {
            if(!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Another diagnostic action is running. Try again shortly.');
            return $operation();
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }

    public function state(): array
    {
        $path=$this->directory().'/state.json';
        if(!file_exists($path))return ['resolved'=>[],'operations'=>[],'cooldowns'=>[]];
        if(is_link($path) || filesize($path)>2*1024*1024)throw new RuntimeException('Diagnostic state requires technical review.');
        $text=@file_get_contents($path);$state=$text===false?null:json_decode($text,true);
        if(!is_array($state) || !isset($state['resolved'],$state['operations'],$state['cooldowns']) || !is_array($state['resolved']) || !is_array($state['operations']) || !is_array($state['cooldowns']))throw new RuntimeException('Diagnostic state requires technical review.');
        return $state;
    }

    public function save(array $state): void
    {
        foreach(['resolved'=>500,'operations'=>2000,'cooldowns'=>500] as $key=>$limit) $state[$key]=array_slice($state[$key],-$limit,null,true);
        $directory=$this->directory();$temp=tempnam($directory,'.state-');
        if(!$temp)throw new RuntimeException('Cannot prepare diagnostic audit state.');
        try {
            if(file_put_contents($temp,json_encode($state,JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Cannot save diagnostic audit state.');
            @chmod($temp,0660);
            if(!rename($temp,$directory.'/state.json'))throw new RuntimeException('Cannot save diagnostic audit state.');
        } finally {if(file_exists($temp))@unlink($temp);}
    }

    public function audit(array $event): void
    {
        $path=$this->directory().'/audit-'.now()->format('Y-m-d').'.jsonl';
        if(is_link($path))throw new RuntimeException('Unsafe diagnostic audit path.');
        $stream=@fopen($path,'ab');if(!$stream)throw new RuntimeException('Cannot open diagnostic audit history.');
        try {
            if(!flock($stream,LOCK_EX))throw new RuntimeException('Cannot lock diagnostic audit history.');
            $line=json_encode(['time'=>now()->format('Y-m-d\TH:i:s.uP')]+$event,JSON_THROW_ON_ERROR)."\n";
            if(fwrite($stream,$line)!==strlen($line) || !fflush($stream))throw new RuntimeException('Cannot write diagnostic audit history.');
        } finally {flock($stream,LOCK_UN);fclose($stream);}
    }

    public function history(): array
    {
        $files=glob($this->directory().'/audit-*.jsonl')?:[];rsort($files);$events=[];
        foreach(array_slice($files,0,3) as $path) {
            if(is_link($path) || !is_file($path))continue;$stream=@fopen($path,'rb');if(!$stream)continue;
            try {$size=(int)(fstat($stream)['size']??0);$start=max(0,$size-65536);fseek($stream,$start);if($start>0)fgets($stream);$text=stream_get_contents($stream,65536)?:'';}
            finally {fclose($stream);}
            foreach(explode("\n",$text) as $line) { $row=json_decode($line,true);if(is_array($row))$events[]=$row; }
        }
        usort($events,fn($a,$b)=>strcmp($b['time']??'',$a['time']??''));return array_slice($events,0,30);
    }
}
