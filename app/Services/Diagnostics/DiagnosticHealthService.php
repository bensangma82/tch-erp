<?php

namespace App\Services\Diagnostics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

final class DiagnosticHealthService
{
    public function checks(): array
    {
        $checks=[];
        try {DB::select('select 1');$checks[]=$this->row('Database','ok','Connected. Read-only query passed.');}
        catch(\Throwable){$checks[]=$this->row('Database','error','Connection check failed. Review the database service and deployment configuration.');}
        foreach(['Compiled views'=>(config('view.compiled') ?: storage_path('framework/views')),'Runtime cache'=>app()->bootstrapPath('cache'),'Diagnostic audit'=>config('erp_diagnostics.state_directory',storage_path('app/erp-diagnostics'))] as $label=>$path) {
            $checks[]=$this->row($label,is_dir($path)&&is_writable($path)?'ok':'warning',is_dir($path)&&is_writable($path)?'Directory is writable.':'Directory is missing or not writable. Ask the server administrator to review ownership and permissions.');
        }
        try {
            $migrator=app('migrator');
            if(!$migrator->repositoryExists())$checks[]=$this->row('Migrations','warning','Migration history is unavailable. Review deployment before applying changes.');
            else {
                $ran=$migrator->getRepository()->getRan();$files=$migrator->getMigrationFiles(database_path('migrations'));
                $count=count(array_diff(array_keys($files),$ran));
                $checks[]=$this->row('Migrations',$count?'warning':'ok',$count?$count.' pending migration(s). Apply only through a reviewed deployment.':'No pending migration files found.');
            }
        }catch(\Throwable){$checks[]=$this->row('Migrations','warning','Could not check migration history. No migration was executed.');}
        try {
            if(Schema::hasTable('failed_jobs')) { $count=DB::table('failed_jobs')->count();$checks[]=$this->row('Failed jobs',$count?'warning':'ok',$count?$count.' failed job(s). Review each original operation before retrying; no jobs are replayed here.':'No failed jobs recorded.'); }
            else $checks[]=$this->row('Failed jobs','info','No database failed-jobs table is configured.');
        }catch(\Throwable){$checks[]=$this->row('Failed jobs','warning','Failed-jobs status could not be checked.');}
        $free=@disk_free_space(base_path());
        $checks[]=$this->row('Free disk space',$free===false?'warning':($free<250*1024*1024?'error':($free<1024*1024*1024?'warning':'ok')),$free===false?'Could not read available disk space.':number_format($free/(1024*1024*1024),2).' GiB available on the application volume.');
        $debug=app()->environment('production')&&(bool)config('app.debug');
        $checks[]=$this->row('Production debug',$debug?'warning':'ok',$debug?'Debug output is enabled in production. Review deployment configuration.':'No production debug exposure detected.');
        return $checks;
    }

    private function row(string $name,string $status,string $detail): array {return compact('name','status','detail');}

    /** GET against the configured Tally endpoint only; never import, export or change a voucher. */
    public function tally(): array
    {
        $url=trim((string)config('tally.url'));$parts=parse_url($url);
        if(!$parts || !in_array(strtolower($parts['scheme']??''),['http','https'],true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path']??'',['','/'],true)) {
            return $this->row('Tally connection','warning','The configured Tally endpoint is invalid. No request was sent.');
        }
        try {
            $response=Http::connectTimeout(2)->timeout(4)->withoutRedirecting()->get($url);
            return $this->row('Tally connection',$response->successful()?'ok':'warning',$response->successful()
                ?'HTTP endpoint responded. This does not verify the loaded company, voucher presence or transmission outcome.'
                :'The endpoint returned HTTP '.$response->status().'. No voucher was transmitted.');
        }catch(\Throwable){return $this->row('Tally connection','error','The endpoint could not be reached within the check timeout. Check TallyPrime and network access. No voucher was transmitted.');}
    }
}
