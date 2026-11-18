<?php

namespace App\Services\Diagnostics;

use DateTimeImmutable;
use DateTimeZone;

final class DiagnosticLogReader
{
    public function __construct(private readonly DiagnosticCatalog $catalog) {}

    /** Bounded local Laravel log scan. Raw log messages, SQL, payloads and traces never leave this method. */
    public function scan(): array
    {
        $directory = (string) config('erp_diagnostics.log_directory',storage_path('logs'));
        $files = array_filter(glob($directory.'/laravel*.log') ?: [], fn($p) =>
            !is_link($p) && is_file($p) && preg_match('/^laravel(?:-\d{4}-\d{2}(?:-\d{2})?)?\.log$/D',basename($p)));
        usort($files, fn($a,$b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
        $files = array_slice($files,0,max(1,min(12,(int)config('erp_diagnostics.max_files',6))));
        $remaining = max(1024,min(8*1024*1024,(int)config('erp_diagnostics.max_scan_bytes',4*1024*1024)));
        $perFile = max(1024,min(2*1024*1024,(int)config('erp_diagnostics.max_file_bytes',1024*1024)));
        $groups=[]; $warnings=[]; $bytes=0; $scanned=0;
        foreach ($files as $path) {
            if ($remaining <= 0) break;
            $stream = @fopen($path,'rb');
            if (!$stream) { $warnings[]='One Laravel log file could not be read.'; continue; }
            try {
                $size=(int)(fstat($stream)['size'] ?? 0); $length=min($size,$perFile,$remaining); $start=max(0,$size-$length);
                fseek($stream,$start); $text=stream_get_contents($stream,$length);
                if ($text===false) { $warnings[]='A log read failed.'; continue; }
            } finally { fclose($stream); }
            $bytes+=strlen($text); $remaining-=strlen($text); $scanned++;
            preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+[^\s.]+\.(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY):[^\r\n]*/m',$text,$matches,PREG_OFFSET_CAPTURE);
            foreach (array_reverse(array_keys($matches[0])) as $i) {
                [$header,$offset]=$matches[0][$i];
                if ($start>0 && $offset===0) continue; // A tail can begin inside a previous entry.
                if(!in_array($matches[2][$i][0],['ERROR','CRITICAL','ALERT','EMERGENCY'],true))continue;
                $timestamp=$matches[1][$i][0];
                $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$timestamp,new DateTimeZone(config('app.timezone','UTC')));
                if (!$date || $date->format('Y-m-d H:i:s')!==$timestamp || $date->getTimestamp()<now()->subDays(max(1,min(30,(int)config('erp_diagnostics.lookback_days',7))))->timestamp || $date->getTimestamp()>now()->addMinutes(5)->timestamp) continue;
                $next=$matches[0][$i+1][1] ?? strlen($text);
                $entry=substr($text,$offset,min(8192,$next-$offset)); $diagnosis=$this->catalog->classify($entry);
                preg_match('~\b(app/[A-Za-z0-9_/.-]+\.php)(?::|\()?(\d{1,7})?~',$entry,$source);
                $file=$source[1] ?? null; $line=isset($source[2]) ? (int)$source[2] : null;
                $id=hash('sha256',$diagnosis['code'].'|'.($file ?? 'unknown').'|'.($line ?? 0));
                $cursor=hash('sha256',basename($path).'|'.($start+$offset).'|'.$header);
                if (!isset($groups[$id]) && count($groups)>=500) { $warnings[]='The scan contains more than 500 distinct errors; only the most recent groups encountered are shown.'; continue; }
                if (!isset($groups[$id])) $groups[$id]=array_merge($diagnosis,['id'=>$id,'count'=>0,'first_seen'=>$timestamp,'last_seen'=>$timestamp,'revision'=>$cursor,'file'=>$file,'line'=>$line,'_order'=>'']);
                $groups[$id]['count']++;
                if ($timestamp < $groups[$id]['first_seen']) $groups[$id]['first_seen']=$timestamp;
                $order=$timestamp.'|'.sprintf('%020d:%020d',(int)(@filemtime($path) ?: 0),$start+$offset);
                if ($order > $groups[$id]['_order']) { $groups[$id]['last_seen']=$timestamp; $groups[$id]['revision']=$cursor;$groups[$id]['_order']=$order; }
            }
        }
        foreach($groups as &$group)unset($group['_order']);unset($group);
        $groups=array_values($groups); usort($groups,fn($a,$b)=>strcmp($b['last_seen'],$a['last_seen']));
        return ['incidents'=>array_slice($groups,0,max(1,min(200,(int)config('erp_diagnostics.max_incidents',100)))),
            'warnings'=>array_values(array_unique($warnings)),'files'=>$scanned,'bytes'=>$bytes,'has_files'=>count($files)>0];
    }
}
