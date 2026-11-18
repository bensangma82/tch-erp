<?php

namespace App\Services\Diagnostics;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

final class DiagnosticActionService
{
    public function __construct(private readonly DiagnosticLogReader $logs,private readonly DiagnosticStore $store) {}

    public static function authorize(User $user): void {abort_unless($user->is_active && $user->isAdmin(),403);}

    public function dashboard(): array
    {
        $scan=$this->logs->scan();$state=$this->store->state();
        foreach($scan['incidents'] as &$incident) $incident['resolved']=($state['resolved'][$incident['id']]['revision']??null)===$incident['revision'];
        unset($incident);return $scan;
    }

    private function incident(string $id,string $revision): array
    {
        foreach($this->logs->scan()['incidents'] as $incident) {
            if($incident['id']===$id) {
                if(!hash_equals($incident['revision'],$revision))$this->fail('New error evidence appeared. Refresh the page before taking action.');
                return $incident;
            }
        }
        $this->fail('This incident is outside the current log window. Refresh before taking action.');
    }

    private function fail(string $message): never {throw ValidationException::withMessages(['diagnostics'=>$message]);}

    public function repair(User $actor,string $id,string $revision,string $action,string $operation): array
    {
        self::authorize($actor);
        return $this->store->exclusive(function() use($actor,$id,$revision,$action,$operation) {
            $incident=$this->incident($id,$revision);
            if(!isset(DiagnosticCatalog::COMMANDS[$action]) || $incident['action']!==$action)$this->fail('No automatic repair is allowed for this incident.');
            $state=$this->store->state();
            if(isset($state['operations'][$operation]))$this->fail('This action was already submitted. Review its audit result.');
            $key=$id.':'.$action;
            if(($state['cooldowns'][$key]??0)>now()->timestamp-60)$this->fail('This repair was just attempted. Check the result before trying again.');
            $state['operations'][$operation]=['at'=>now()->timestamp,'status'=>'claimed'];$state['cooldowns'][$key]=now()->timestamp;
            $this->store->save($state);
            $base=['actor_id'=>$actor->id,'incident_id'=>$id,'title'=>$incident['title'],'action'=>$action,'operation_id'=>$operation];
            $this->store->audit($base+['result'=>'started']); // Durable audit must succeed before maintenance runs.
            try {
                $exit=Artisan::call(DiagnosticCatalog::COMMANDS[$action]);
                $compiled=(string)(config('view.compiled') ?: storage_path('framework/views'));
                $remainingViews=glob($compiled.'/*.php');
                $verified=$exit===0 && ($action==='routes_clear' ? !app()->routesAreCached()
                    : is_dir($compiled) && is_readable($compiled) && is_array($remainingViews) && count($remainingViews)===0);
                $result=$verified?'maintenance_complete':'maintenance_failed';
            } catch(\Throwable) {$result='maintenance_failed';}
            $state['operations'][$operation]['status']=$result;$this->store->save($state);$this->store->audit($base+['result'=>$result]);
            return ['ok'=>$result==='maintenance_complete','message'=>$result==='maintenance_complete'
                ?'Cache maintenance completed and checked. Reopen the affected ERP screen; mark resolved only after it works. A source-code error may still need a correction.'
                :'Maintenance could not be verified. The incident remains open; technical review is required.'];
        });
    }

    public function resolve(User $actor,string $id,string $revision,string $operation): void
    {
        self::authorize($actor);
        $this->store->exclusive(function() use($actor,$id,$revision,$operation) {
            $incident=$this->incident($id,$revision);$state=$this->store->state();
            if(isset($state['operations'][$operation]))$this->fail('This confirmation was already submitted.');
            $this->store->audit(['actor_id'=>$actor->id,'incident_id'=>$id,'title'=>$incident['title'],'action'=>'mark_resolved','operation_id'=>$operation,'result'=>'user_verified']);
            $state['resolved'][$id]=['revision'=>$revision,'actor_id'=>$actor->id,'at'=>now()->toIso8601String()];
            $state['operations'][$operation]=['at'=>now()->timestamp,'status'=>'user_verified'];$this->store->save($state);
        });
    }
}
