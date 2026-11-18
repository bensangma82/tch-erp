<?php

namespace App\Services\Assistant;

use App\Models\{AdministrativeRequest,Employee,Encounter,PharmacyStockBatch,InvestigationCancellationRequest,InvestigationMsApprover,InvestigationRefundSettlementCheck,LeaveRequest,MhisPackageVarianceApproval,ServiceOrderItem,TallyExport,User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/** Server-owned, permission-scoped and read-only work queries. No model-generated query, route or action is accepted. */
final class PendingWorkService
{
    public const CATEGORIES=['all','administration','opd','nursing','pharmacy','refunds','mhis','cancellations','laboratory','imaging','leave','tally','system'];
    private array $queues=[];
    private array $unavailable=[];

    private function direct(User $user,string $permission): bool
    {
        return $user->permissions()->where('permissions.name',$permission)->exists();
    }

    private function provider(string $name,callable $operation): void
    {
        try {$operation();}catch(\Throwable){$this->unavailable[]=$name;}
    }

    private function add(string $key,string $category,string $title,string $next,Builder $query,string $route,array $params=[],bool $administrative=false): void
    {
        if(!Route::has($route))throw new \RuntimeException('Required work route is unavailable.');
        $count=(clone $query)->count();
        $overdue=$administrative?(clone $query)->overdue()->count():0;
        $samples=[];
        if($administrative && $count>0) {
            foreach((clone $query)->orderByRaw('required_by IS NULL')->orderBy('required_by')->orderBy('id')->limit(max(1,min(5,(int)config('erp_assistant.samples_per_queue',3))))
                ->get(['id','request_no','title','status','required_by']) as $item) {
                $samples[]=['reference'=>$item->request_no,'title'=>$item->title,'due'=>$item->required_by?->toDateString(),
                    'overdue'=>$item->is_overdue,'url'=>route('administration.requests.show',$item->id,false)];
            }
        }
        $this->queues[]=['key'=>$key,'category'=>$category,'title'=>$title,'count'=>$count,'overdue'=>$overdue,'next_action'=>$next,
            'url'=>route($route,$params,false),'samples'=>$samples];
    }

    public function snapshot(User $user): array
    {
        abort_unless($user->is_active,403);$this->queues=[];$this->unavailable=[];
        if($user->hasPermission('administration.view'))$this->provider('Administration',function()use($user){
            $base=AdministrativeRequest::query();
            $this->add('my_assigned','administration','Your assigned work', $user->hasPermission('administration.complete')?'Open My Work, review instructions and record completion through its normal workflow.':'Review My Work. Ask the administrator if completion access is required.',
                (clone $base)->where('assigned_to',$user->id)->where('status','execution_in_progress'),'administration.my-work.index',[],true);
            if($user->hasPermission('administration.create'))$this->add('my_drafts','administration','Your draft requests','Review and submit your draft request.',(clone $base)->where('created_by',$user->id)->where('status','draft'),'administration.requests.index',['status'=>'draft'],true);
            $stages=[
                ['administration.verify','admin_verification','Requests awaiting verification',['submitted'],'verification','Review submitted requests before forwarding for approval.'],
                ['administration.approve','ms_approval','Requests awaiting MS decision',['pending_ms_approval'],'ms','Review the request and record the authorized decision.'],
                ['administration.approve','higher_approval','Higher approval follow-up',['higher_approval_required'],'higher','Obtain and record actual higher-authority approval through the request screen.'],
                ['administration.assign','assignment','Approved requests awaiting assignment',['ms_approved','higher_approved'],'execution','Assign an eligible responsible person through the request screen.'],
                ['administration.close','closure','Verified work awaiting closure',['execution_verified'],'closure','Review completion verification and close through the request screen.'],
            ];
            foreach($stages as [$permission,$key,$title,$statuses,$queue,$next])if($user->hasPermission($permission))$this->add($key,'administration',$title,$next,(clone $base)->whereIn('status',$statuses),'administration.requests.index',['queue'=>$queue],true);
            if($user->hasPermission('administration.verify-completion')) {
                $query=(clone $base)->where('status','executed');
                if(!$user->isAdmin())$query->whereNotNull('executed_by')->where('executed_by','<>',$user->id);
                $this->add('completion_review','administration','Completed work awaiting independent verification','Check actual completion before recording verification.',$query,'administration.requests.index',['queue'=>'closure'],true);
            }
        });
        if(config('investigation_cancellation.enable_verified_refunds',false))$this->provider('Refunds',function()use($user){
            if($this->direct($user,'billing.investigation-refund.process'))$this->add('refund_payment','refunds','Refund requests awaiting payment recording','Open the refund desk, confirm actual payment and record its evidence. The assistant cannot pay a refund.',InvestigationRefundSettlementCheck::where('status','initiated'),'billing.investigation-refunds.verification',['stage'=>'initiated']);
            if($this->direct($user,'billing.investigation-refund.verify')) {
                $query=InvestigationRefundSettlementCheck::whereIn('status',['disbursed','submitted']);
                foreach(['submitted_by','initiated_by','disbursed_by'] as $column)$query->where(fn($q)=>$q->whereNull($column)->orWhere($column,'<>',$user->id));
                $query->whereHas('cancellation',fn($q)=>$q->where(fn($q)=>$q->whereNull('requested_by')->orWhere('requested_by','<>',$user->id)));
                $this->add('refund_verify','refunds','Refunds you can independently review','Examine actual payment evidence in the refund desk. Older records may need the actual voucher reference and payment date.',$query,'billing.investigation-refunds.verification');
            }
        });
        if($user->hasPermission('billing.investigation-cancellation.approve'))$this->provider('Cancellations',function()use($user){
            $query=InvestigationCancellationRequest::where('status','pending')->where('requested_by','<>',$user->id);
            $ms=$this->direct($user,'billing.investigation-cancellation.approve') && InvestigationMsApprover::where('user_id',$user->id)->where('active',true)->exists();
            if(!$ms)$query->where('requires_ms_approval',false)->whereHas('serviceOrderItem',fn($q)=>$q->where('status','ordered')->whereDoesntHave('diagnosticResult')->whereDoesntHave('diagnosticSample',fn($s)=>$s->where('status','collected')));
            $this->add('cancellation_review','cancellations','Cancellation requests to review','Review the clinical state and decision requirements. Your own requests and designated-MS-only cases are excluded when you cannot decide them.',$query,'billing.investigation-cancellations.queue',['status'=>'pending']);
        });
        if($user->hasPermission('finance.mhis-adjustment.approve') || $user->hasPermission('finance.mhis-adjustment.post'))$this->provider('MHIS',function()use($user){
            if($user->hasPermission('finance.mhis-adjustment.approve'))$this->add('mhis_checker','mhis','MHIS variances awaiting independent Finance review','Review supporting records and the current bill snapshot before approval.',MhisPackageVarianceApproval::where('status','submitted')->where('created_by','<>',$user->id),'finance.mhis-package-variance.index');
            if($user->hasPermission('finance.mhis-adjustment.post'))$this->add('mhis_returned','mhis','Your returned MHIS reviews','Open the returned review, correct the recorded details and resubmit for a different checker.',MhisPackageVarianceApproval::where('status','returned')->where('created_by',$user->id),'finance.mhis-package-variance.index');
        });
        foreach([['laboratory','laboratory.view','laboratory','laboratory.index','Laboratory investigations awaiting work'],['imaging','radiology.view','radiology','imaging.index','Imaging investigations awaiting work']] as [$category,$permission,$stored,$route,$title]) {
            if($user->hasPermission($permission))$this->provider(ucfirst($category),function()use($category,$stored,$route,$title){
                $this->add($category.'_work',$category,$title,'Open the released worklist. Sample, reporting and sign-off permissions are checked by the original screen.',ServiceOrderItem::where('category',$stored)->whereIn('status',['ordered','in_process'])->whereHas('serviceOrder',fn($q)=>$q->whereIn('status',['paid','authorized'])),$route);
            });
        }
        if($user->hasPermission('opd.view'))$this->provider('OPD',function()use($user){
            $query=Encounter::where('encounter_type','OPD')->whereDate('encounter_date',today())->whereIn('status',['waiting','waiting_for_doctor'])->whereNull('seen_at');
            $doctor=Employee::where('user_id',$user->id)->where('is_doctor',true)->where('is_active',true)->first(['id']);
            if(!$user->isAdmin() && $user->hasRole('doctor') && !$doctor)throw new \RuntimeException('Doctor link is unavailable.');
            if(!$user->isAdmin() && $doctor)$query->where('doctor_id',$doctor->id);
            $this->add('opd_waiting','opd',(!$user->isAdmin()&&$doctor)?'Your OPD encounters awaiting review today':"Today's OPD waiting queue",'Open OPD and review the waiting encounters. Record consultation completion only after it actually occurs.',$query,'opd.index');
        });
        if($user->hasPermission('nursing.view'))$this->provider('Nursing',fn()=> $this->add('nursing_review','nursing',"Today's nursing review queue",'Open the nursing station and follow its actual consultation and vitals workflow.',Encounter::where('encounter_type','OPD')->whereDate('encounter_date',today())->whereIn('status',['waiting','waiting_for_doctor'])->whereNull('seen_at'),'nursing.index'));
        if($user->hasPermission('pharmacy.view'))$this->provider('Pharmacy',function(){
            $stock=PharmacyStockBatch::where('is_active',true)->where('quantity_available','>',0);
            $this->add('stock_out','pharmacy','Out-of-stock active batches','Review current availability and reorder needs in Pharmacy. Chat does not create an order.',PharmacyStockBatch::where('is_active',true)->where('quantity_available','<=',0),'pharmacy.dashboard');
            $this->add('stock_expired','pharmacy','Expired stock batches requiring review','Review expiry controls in Pharmacy. No stock is disposed of or adjusted by chat.',(clone $stock)->whereDate('expiry_date','<',today()),'pharmacy.dashboard');
            $this->add('stock_near_expiry','pharmacy','Stock batches expiring within 90 days','Review expiry dates and stock movement through Pharmacy.',(clone $stock)->whereBetween('expiry_date',[today()->toDateString(),today()->addDays(90)->toDateString()]),'pharmacy.dashboard');
            $this->add('stock_low','pharmacy','Low stock batches requiring review','Review reorder needs using the Pharmacy dashboard; chat does not place a purchase order.',(clone $stock)->whereColumn('quantity_available','<=','reorder_level'),'pharmacy.dashboard');
        });
        if($user->isAdmin() || $user->hasRole('hr'))$this->provider('Leave',fn()=> $this->add('leave_review','leave','Pending leave requests this year','Review the employee leave request and its balance in HR.',LeaveRequest::where('status','pending')->whereYear('start_date',now()->year),'admin.hr.leave-requests.index',['status'=>'pending','year'=>now()->year]));
        if($user->isAdmin() || $user->hasAnyRole(['finance','finance_manager']))$this->provider('Tally',fn()=> $this->add('tally_reconciliation','tally','Journal exports needing reconciliation','Check the original Tally outcome and reconciliation evidence. Unknown exports must not be resent blindly.',TallyExport::whereNotNull('journal_entry_id')->whereIn('status',['unknown','failed']),'finance.tally.journals.index',['status'=>'needs_attention']));
        if($user->isAdmin() && class_exists(\App\Services\Diagnostics\DiagnosticActionService::class) && Route::has('admin.erp-diagnostics.index'))$this->provider('ERP Diagnostics',function(){
            $scan=app(\App\Services\Diagnostics\DiagnosticActionService::class)->dashboard();$open=collect($scan['incidents'])->where('resolved',false)->count();
            $this->queues[]=['key'=>'system_incidents','category'=>'system','title'=>'ERP incidents awaiting review','count'=>$open,'overdue'=>0,
                'next_action'=>'Review the incident guidance in ERP Diagnostics. A historical log entry is not proof of a current failure.','url'=>route('admin.erp-diagnostics.index',[],false),'samples'=>[]];
        });
        return ['as_of'=>now()->toIso8601String(),'queues'=>$this->queues,'unavailable'=>array_values(array_unique($this->unavailable)),
            'total'=>array_sum(array_column($this->queues,'count')),'overdue'=>array_sum(array_column($this->queues,'overdue')),
            'complete'=>count($this->unavailable)===0];
    }
}
