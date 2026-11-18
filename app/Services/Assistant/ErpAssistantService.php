<?php

namespace App\Services\Assistant;

use App\Models\User;

final class ErpAssistantService
{
    public function __construct(private readonly PendingWorkService $work,private readonly LocalIntentRouter $router) {}

    public function briefing(User $user): array
    {
        $snapshot=$this->work->snapshot($user);
        return $this->answer($snapshot,['intent'=>'summary','category'=>'all','mode'=>'erp_briefing',
            'notice'=>'Live ERP work briefing. A local model is used only when enabled and you ask a question.']);
    }

    public function ask(User $user,string $question): array
    {
        // Every question rebuilds the work scope for this current authenticated user. No client-supplied actor or context is trusted.
        $snapshot=$this->work->snapshot($user);$intent=$this->router->interpret($question);
        return $this->answer($snapshot,$intent);
    }

    private function answer(array $snapshot,array $intent): array
    {
        if(in_array($intent['intent'],['assigned','overdue'],true))$intent['category']='administration';
        $queues=collect($snapshot['queues'])->filter(fn($q)=>$intent['category']==='all'||$q['category']===$intent['category']);
        if($intent['intent']==='assigned')$queues=$queues->where('key','my_assigned');
        if($intent['intent']==='overdue')$queues=$queues->where('category','administration')->where('overdue','>',0)->map(function($q){$q['count']=$q['overdue'];$q['samples']=array_values(array_filter($q['samples'],fn($s)=>$s['overdue']));return $q;});
        $visible=($intent['intent']==='help'?$queues:$queues->where('count','>',0))->values();
        $count=$intent['intent']==='overdue'?$visible->sum('overdue'):$visible->sum('count');
        if($intent['intent']==='unsupported') {
            $visible=collect();$count=0;
            $message='I can show current pending work, recorded overdue deadlines and links to your authorized ERP screens. I cannot execute transactions, reveal patient records, change access or run commands. Try “What work is pending?”';
        }elseif($count>0) {
            $message=$intent['intent']==='overdue'?'You have '.$count.' Administration item(s) past their recorded deadline.':'There are '.$count.' pending queue entry/entries across the work queues you can access.';
            if($intent['intent']==='help')$message.=' Follow the next step on each card; the original screen rechecks permissions and eligibility.';
        }else $message=$intent['intent']==='help' && $visible->isNotEmpty()?'These are the next steps for the work queues available to you. No pending entries were found in these queues.':'No pending items were found for this question in the work queues available to you.';
        if(!$snapshot['complete'])$message.=' Some queues could not be checked; this is a partial briefing.';
        return ['message'=>$message,'mode'=>$intent['mode'],'notice'=>$intent['notice'],'intent'=>$intent['intent'],'category'=>$intent['category'],
            'as_of'=>$snapshot['as_of'],'briefing_total'=>$snapshot['total'],'count'=>$count,'overdue'=>$visible->sum('overdue'),'queues'=>$visible->all(),
            'complete'=>$snapshot['complete'],'unavailable'=>$snapshot['unavailable'],
            'scope'=>'Personal assignments and authorized review queues. Counts are queue entries; one record can need attention in more than one queue. They are not patient counts or verified transaction eligibility. No action is performed by chat.'];
    }
}
