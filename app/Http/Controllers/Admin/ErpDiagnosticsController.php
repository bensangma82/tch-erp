<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Diagnostics\{DiagnosticActionService,DiagnosticCatalog,DiagnosticHealthService,DiagnosticStore,DiagnosticWorkflowChecks};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

final class ErpDiagnosticsController extends Controller
{
    public function index(
        Request $request,
        DiagnosticActionService $actions,
        DiagnosticHealthService $health,
        DiagnosticStore $store,
        DiagnosticWorkflowChecks $workflows,
        DiagnosticCatalog $catalog
    ): View
    {
        DiagnosticActionService::authorize($request->user());
        $filters=$request->validate(['status'=>['nullable','in:open,resolved,all'],'q'=>['nullable','string','max:80']]);
        $status=$filters['status']??'open';$q=trim($filters['q']??'');$unavailable=false;
        try {$scan=$actions->dashboard();$history=$store->history();}
        catch(\RuntimeException){$scan=['incidents'=>[],'warnings'=>['Diagnostic state or audit storage is unavailable. Ask the server administrator to review runtime directory access.'],'files'=>0,'bytes'=>0,'has_files'=>false];$history=[];$unavailable=true;}
        $all=collect($scan['incidents']);
        $stats=['open'=>$all->where('resolved',false)->count(),'safe'=>$all->where('resolved',false)->whereNotNull('action')->count(),
            'review'=>$all->where('resolved',false)->whereNull('action')->count(),'resolved'=>$all->where('resolved',true)->count()];
        $incidents=$all->filter(fn($i)=>($status==='all'||$i['resolved']===($status==='resolved')) && ($q===''||str_contains(strtolower($i['title'].' '.$i['module'].' '.$i['code']),strtolower($q))));
        $incidents = $incidents->map(fn ($incident) => $incident + ['workflow' => $catalog->workflow($incident)]);
        $checks=$health->checks();
        $workflowChecks = $workflows->scan();
        return view('admin.diagnostics.index',compact('scan','history','stats','incidents','checks','status','q','unavailable','workflowChecks'));
    }

    public function repair(Request $request,DiagnosticActionService $actions): RedirectResponse
    {
        DiagnosticActionService::authorize($request->user());$data=$this->identity($request)+$request->validate(['action'=>['required','in:views_clear,routes_clear']]);
        try {$result=$actions->repair($request->user(),$data['incident'],$data['revision'],$data['action'],$data['operation_id']);}
        catch(\RuntimeException){throw ValidationException::withMessages(['diagnostics'=>'The action could not be recorded or verified. Review audit history and storage access before trying again.']);}
        return redirect()->route('admin.erp-diagnostics.index')->with($result['ok']?'success':'diagnostic_warning',$result['message']);
    }

    public function resolve(Request $request,DiagnosticActionService $actions): RedirectResponse
    {
        DiagnosticActionService::authorize($request->user());$data=$this->identity($request);$request->validate(['screen_checked'=>['required','accepted']]);
        try {$actions->resolve($request->user(),$data['incident'],$data['revision'],$data['operation_id']);}
        catch(\RuntimeException){throw ValidationException::withMessages(['diagnostics'=>'The confirmation could not be saved. Review diagnostic audit storage.']);}
        return redirect()->route('admin.erp-diagnostics.index')->with('success','Your verification is recorded. If this error appears again, it will reopen automatically.');
    }

    public function tally(Request $request,DiagnosticHealthService $health,DiagnosticStore $store): RedirectResponse
    {
        DiagnosticActionService::authorize($request->user());
        try {
            $result=$store->exclusive(function() use($request,$health,$store) {
                $store->audit(['actor_id'=>$request->user()->id,'action'=>'tally_connection_check','result'=>'started']);
                $result=$health->tally();$store->audit(['actor_id'=>$request->user()->id,'action'=>'tally_connection_check','result'=>$result['status']]);return $result;
            });
        }catch(\RuntimeException){throw ValidationException::withMessages(['diagnostics'=>'Could not audit the connection check. Review diagnostic storage access.']);}
        return redirect()->route('admin.erp-diagnostics.index')->with('tally_check',$result);
    }

    private function identity(Request $request): array
    {
        return $request->validate(['incident'=>['required','regex:/^[a-f0-9]{64}$/D'],'revision'=>['required','regex:/^[a-f0-9]{64}$/D'],'operation_id'=>['required','uuid']]);
    }
}
