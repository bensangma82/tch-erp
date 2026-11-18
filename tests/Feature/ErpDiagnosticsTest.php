<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Diagnostics\{DiagnosticActionService,DiagnosticCatalog,DiagnosticHealthService,DiagnosticLogReader,DiagnosticStore};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Artisan,DB,File,Http};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ErpDiagnosticsTest extends TestCase
{
    use DatabaseTransactions;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(app()->environment('testing'),'STOP: testing environment required.');
        $this->assertSame('testing',DB::connection()->getDatabaseName(),'STOP: dedicated testing database required.');
        $this->root=storage_path('framework/testing/erp-diagnostics-'.Str::uuid());
        File::makeDirectory($this->root.'/logs',0770,true);
        config(['erp_diagnostics.log_directory'=>$this->root.'/logs','erp_diagnostics.state_directory'=>$this->root.'/audit']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if(isset($this->root))File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function admin(): User {return User::factory()->create(['role'=>'admin','is_active'=>true]);}
    private function log(string $message,string $level='ERROR',?string $time=null,string $name='laravel.log'): void
    {
        file_put_contents($this->root.'/logs/'.$name,'['.($time??now()->format('Y-m-d H:i:s')).'] production.'.$level.': '.$message."\n",FILE_APPEND);
    }
    private function incident(string $message='Illuminate\View\ViewException at /var/www/tch-erp/app/Services/Example.php:42'): array
    {
        $this->log($message);return app(DiagnosticLogReader::class)->scan()['incidents'][0];
    }
    private function payload(array $incident,string $action='views_clear'): array
    {
        return ['incident'=>$incident['id'],'revision'=>$incident['revision'],'action'=>$action,'operation_id'=>(string)Str::uuid()];
    }
    private function reject(callable $action): void
    {
        try {$action();$this->fail('Expected validation rejection.');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
    }

    public function test_guest_and_nonadministrator_cannot_access_or_repair(): void
    {
        $this->get(route('admin.erp-diagnostics.index'))->assertRedirect(route('login'));
        $user=User::factory()->create(['role'=>'billing','is_active'=>true]);
        $this->actingAs($user)->get(route('admin.erp-diagnostics.index'))->assertForbidden();
        foreach(['repair','resolve','tally'] as $action)$this->actingAs($user)->post(route('admin.erp-diagnostics.'.$action),[])->assertForbidden();
        $this->assertDirectoryDoesNotExist($this->root.'/audit');
    }

    public function test_administrator_sees_dashboard_and_sidebar_link(): void
    {
        $this->incident();
        $this->actingAs($this->admin())->get(route('admin.erp-diagnostics.index'))->assertOk()->assertSee('ERP Diagnostics')->assertSee('Clear compiled views')->assertSee('Action history')->assertSee(route('admin.erp-diagnostics.index'),false);
    }

    public function test_raw_patient_sql_and_credentials_never_reach_dashboard_or_audit(): void
    {
        $this->log('Unclassified failure PATIENT-SECRET-987 SQL select secret="PASSWORD-SECRET-123" token=TOKEN-SECRET-456 at /var/www/tch-erp/app/Services/Example.php:42');
        $scan=app(DiagnosticLogReader::class)->scan();$encoded=json_encode($scan);
        foreach(['PATIENT-SECRET','PASSWORD-SECRET','TOKEN-SECRET','select secret'] as $value)$this->assertStringNotContainsString($value,$encoded);
        $response=$this->actingAs($this->admin())->get(route('admin.erp-diagnostics.index'))->assertOk();
        foreach(['PATIENT-SECRET','PASSWORD-SECRET','TOKEN-SECRET'] as $value)$response->assertDontSee($value);
        $this->assertSame('app/Services/Example.php',$scan['incidents'][0]['file']);
    }

    public function test_repeated_errors_are_grouped_and_warnings_do_not_pollute_diagnosis(): void
    {
        $this->log('Illuminate\Routing\Exceptions\RouteNotFoundException Route [example] not defined.');
        $this->log('ViewException unrelated informational content','INFO');
        $this->log('Illuminate\Routing\Exceptions\RouteNotFoundException Route [example] not defined.');
        $this->log('Warning only','WARNING');
        $scan=app(DiagnosticLogReader::class)->scan();$this->assertCount(1,$scan['incidents']);
        $this->assertSame(2,$scan['incidents'][0]['count']);$this->assertSame('routes_clear',$scan['incidents'][0]['action']);
    }

    public function test_old_entries_and_unrelated_files_are_not_scanned_as_current_errors(): void
    {
        $this->log('ViewException','ERROR',now()->subDays(20)->format('Y-m-d H:i:s'));
        $this->log('ViewException','ERROR',null,'unrelated.log');
        $this->assertEmpty(app(DiagnosticLogReader::class)->scan()['incidents']);
    }

    public function test_scan_is_bounded_for_large_logs_and_does_not_read_symlinks(): void
    {
        config(['erp_diagnostics.max_scan_bytes'=>2048,'erp_diagnostics.max_file_bytes'=>2048]);
        file_put_contents($this->root.'/logs/laravel.log',str_repeat('x',10000)."\n");
        $this->log('ViewException');
        file_put_contents($this->root.'/private.log','['.now()->format('Y-m-d H:i:s').'] production.ERROR: RouteNotFoundException');
        symlink($this->root.'/private.log',$this->root.'/logs/laravel-2026-01-01.log');
        $scan=app(DiagnosticLogReader::class)->scan();$this->assertLessThanOrEqual(2048,$scan['bytes']);$this->assertCount(1,$scan['incidents']);
    }

    public function test_safe_view_repair_is_audited_without_resolving_the_incident(): void
    {
        $i=$this->incident();$admin=$this->admin();$data=$this->payload($i);
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(1);
        $this->actingAs($admin)->post(route('admin.erp-diagnostics.repair'),$data)->assertRedirect()->assertSessionHas('diagnostic_warning');
        $history=app(DiagnosticStore::class)->history();$this->assertCount(2,$history);
        $this->assertContains('started',array_column($history,'result'));$this->assertContains('maintenance_failed',array_column($history,'result'));
        $this->assertFalse(app(DiagnosticActionService::class)->dashboard()['incidents'][0]['resolved']);
    }

    public function test_successful_route_cache_maintenance_requires_separate_user_verification(): void
    {
        $i=$this->incident('Illuminate\Routing\Exceptions\RouteNotFoundException Route [missing] not defined.');
        $this->assertFalse(app()->routesAreCached());
        Artisan::shouldReceive('call')->once()->with('route:clear')->andReturn(0);
        $r=app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'routes_clear',(string)Str::uuid());
        $this->assertTrue($r['ok']);$this->assertFalse(app(DiagnosticActionService::class)->dashboard()['incidents'][0]['resolved']);
    }

    public function test_arbitrary_commands_and_financial_repairs_are_rejected(): void
    {
        $admin=$this->admin();$i=$this->incident('Approved maker-checker Finance authorisation is required for MHIS package variance.');
        Artisan::shouldReceive('call')->never();
        $this->reject(fn()=>app(DiagnosticActionService::class)->repair($admin,$i['id'],$i['revision'],'views_clear',(string)Str::uuid()));
        $this->actingAs($admin)->post(route('admin.erp-diagnostics.repair'),$this->payload($i,'migrate --force'))->assertSessionHasErrors('action');
        $this->assertSame('finance.mhis-package-variance.index',$i['route']);$this->assertNull($i['action']);
    }

    public function test_stale_evidence_blocks_repair_before_command_execution(): void
    {
        $i=$this->incident();$this->log('Illuminate\View\ViewException at /var/www/tch-erp/app/Services/Example.php:42','ERROR',now()->addSecond()->format('Y-m-d H:i:s'));
        Artisan::shouldReceive('call')->never();
        $this->reject(fn()=>app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'views_clear',(string)Str::uuid()));
    }

    public function test_duplicate_clicks_do_not_repeat_maintenance(): void
    {
        $i=$this->incident();$admin=$this->admin();$operation=(string)Str::uuid();
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(1);
        app(DiagnosticActionService::class)->repair($admin,$i['id'],$i['revision'],'views_clear',$operation);
        $this->reject(fn()=>app(DiagnosticActionService::class)->repair($admin,$i['id'],$i['revision'],'views_clear',$operation));
        $this->assertCount(2,app(DiagnosticStore::class)->history());
    }

    public function test_resolution_requires_confirmation_and_new_occurrence_reopens_it(): void
    {
        $i=$this->incident();$admin=$this->admin();
        $this->actingAs($admin)->post(route('admin.erp-diagnostics.resolve'),$this->payload($i))->assertSessionHasErrors('screen_checked');
        $this->actingAs($admin)->post(route('admin.erp-diagnostics.resolve'),$this->payload($i)+['screen_checked'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(app(DiagnosticActionService::class)->dashboard()['incidents'][0]['resolved']);
        $this->log('Illuminate\View\ViewException at /var/www/tch-erp/app/Services/Example.php:42','ERROR',now()->addSecond()->format('Y-m-d H:i:s'));
        $this->assertFalse(app(DiagnosticActionService::class)->dashboard()['incidents'][0]['resolved']);
    }

    public function test_uncertain_tally_and_posted_journal_errors_have_no_automatic_fix(): void
    {
        $catalog=app(DiagnosticCatalog::class);
        foreach(['Transmission outcome uncertain for Tally','Tally cURL error 28 timed out','Posted or reversed journal entries cannot be modified','SQLSTATE[42P01] Undefined table','SQLSTATE[23505] duplicate key value'] as $message) {
            $this->assertNull($catalog->classify($message)['action']);
        }
        $this->assertCount(2,DiagnosticCatalog::COMMANDS);
    }

    public function test_tally_check_uses_only_configured_endpoint_and_never_transmits_vouchers(): void
    {
        config(['tally.url'=>'http://tally.test:9000']);Http::fake(['http://tally.test:9000'=>Http::response('<RESPONSE>TallyPrime Server is Running</RESPONSE>',200)]);
        $this->actingAs($this->admin())->post(route('admin.erp-diagnostics.tally'),['url'=>'http://evil.test/import'])->assertRedirect()->assertSessionHas('tally_check.status','ok');
        Http::assertSentCount(1);Http::assertSent(fn($r)=>$r->method()==='GET' && $r->url()==='http://tally.test:9000' && $r->body()==='');
        $this->assertCount(2,app(DiagnosticStore::class)->history());
    }

    public function test_tally_redirects_and_http_failures_are_not_claimed_as_success(): void
    {
        config(['tally.url'=>'http://tally.test:9000']);Http::fake(['http://tally.test:9000'=>Http::response('',302,['Location'=>'http://other.test'])]);
        $r=app(DiagnosticHealthService::class)->tally();$this->assertSame('warning',$r['status']);Http::assertSentCount(1);
    }

    public function test_invalid_tally_endpoint_sends_no_request(): void
    {
        config(['tally.url'=>'file:///etc/passwd']);Http::fake();
        $this->assertSame('warning',app(DiagnosticHealthService::class)->tally()['status']);Http::assertNothingSent();
    }

    public function test_health_checks_do_not_change_accounting_or_replay_jobs(): void
    {
        $before=DB::table('journal_entries')->count();Artisan::shouldReceive('call')->never();
        $checks=app(DiagnosticHealthService::class)->checks();
        $this->assertSame($before,DB::table('journal_entries')->count());
        $this->assertContains('Database',array_column($checks,'name'));$this->assertContains('Migrations',array_column($checks,'name'));Http::assertNothingSent();
    }

    public function test_corrupt_audit_state_blocks_maintenance(): void
    {
        $i=$this->incident();$store=app(DiagnosticStore::class);$store->state();file_put_contents($this->root.'/audit/state.json','broken');
        Artisan::shouldReceive('call')->never();
        $this->actingAs($this->admin())->post(route('admin.erp-diagnostics.repair'),$this->payload($i))->assertSessionHasErrors('diagnostics');
    }

    public function test_repair_endpoint_never_runs_on_get_requests(): void
    {
        Artisan::shouldReceive('call')->never();$this->actingAs($this->admin())->get(route('admin.erp-diagnostics.repair'))->assertStatus(405);
    }
    public function test_superadministrator_is_supported_and_inactive_users_are_blocked(): void
    {
        $super=User::factory()->create(['role'=>'super_admin','is_active'=>true]);
        $this->actingAs($super)->get(route('admin.erp-diagnostics.index'))->assertOk();
        $inactive=User::factory()->create(['role'=>'admin','is_active'=>false]);
        $this->actingAs($inactive)->get(route('admin.erp-diagnostics.index'))->assertRedirect(route('login'));
    }

    public function test_missing_or_forged_incident_cannot_trigger_a_command(): void
    {
        Artisan::shouldReceive('call')->never();
        $this->reject(fn()=>app(DiagnosticActionService::class)->repair($this->admin(),str_repeat('a',64),str_repeat('b',64),'views_clear',(string)Str::uuid()));
    }

    public function test_command_exception_is_audited_as_failure_without_exposing_its_message(): void
    {
        $i=$this->incident();Artisan::shouldReceive('call')->once()->with('view:clear')->andThrow(new \RuntimeException('PRIVATE-TOKEN-DO-NOT-DISPLAY'));
        $result=app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'views_clear',(string)Str::uuid());
        $this->assertFalse($result['ok']);$audit=json_encode(app(DiagnosticStore::class)->history());
        $this->assertStringContainsString('maintenance_failed',$audit);$this->assertStringNotContainsString('PRIVATE-TOKEN',$audit);
    }

    public function test_audit_unavailable_blocks_command_before_it_starts(): void
    {
        $i=$this->incident();File::makeDirectory($this->root.'/audit');
        File::makeDirectory($this->root.'/audit/audit-'.now()->format('Y-m-d').'.jsonl');
        Artisan::shouldReceive('call')->never();
        $this->actingAs($this->admin())->post(route('admin.erp-diagnostics.repair'),$this->payload($i))->assertSessionHasErrors('diagnostics');
    }

    public function test_displayed_filters_do_not_search_or_expose_raw_log_messages(): void
    {
        $this->incident();$this->log('SQLSTATE[42P01] Undefined table private_patient_name');
        $this->actingAs($this->admin())->get(route('admin.erp-diagnostics.index',['q'=>'database']))->assertOk()->assertSee('Database schema is missing')->assertDontSee('Clear compiled views')->assertDontSee('private_patient_name');
    }

    public function test_tally_connection_exception_is_sanitized(): void
    {
        config(['tally.url'=>'http://tally.test:9000']);
        Http::fake(fn()=>throw new \Illuminate\Http\Client\ConnectionException('SECRET-CONNECTION-DETAIL'));
        $result=app(DiagnosticHealthService::class)->tally();
        $this->assertSame('error',$result['status']);$this->assertStringNotContainsString('SECRET',json_encode($result));
    }

    public function test_concurrent_maintenance_lock_blocks_a_second_action(): void
    {
        $i=$this->incident();$store=app(DiagnosticStore::class);$store->state();
        $handle=fopen($this->root.'/audit/actions.lock','c+');$this->assertTrue(flock($handle,LOCK_EX|LOCK_NB));
        Artisan::shouldReceive('call')->never();
        try {
            try {app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'views_clear',(string)Str::uuid());$this->fail('Concurrent action accepted.');}
            catch(\RuntimeException $e){$this->assertStringContainsString('Another diagnostic action',$e->getMessage());}
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }

    public function test_view_clear_success_is_verified_against_the_configured_compiled_directory(): void
    {
        $i=$this->incident();File::makeDirectory($this->root.'/compiled');config(['view.compiled'=>$this->root.'/compiled']);
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(0);
        $result=app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'views_clear',(string)Str::uuid());
        $this->assertTrue($result['ok']);$this->assertFalse(app(DiagnosticActionService::class)->dashboard()['incidents'][0]['resolved']);
    }

    public function test_success_exit_code_alone_does_not_claim_views_were_cleared(): void
    {
        $i=$this->incident();File::makeDirectory($this->root.'/compiled');config(['view.compiled'=>$this->root.'/compiled']);
        file_put_contents($this->root.'/compiled/example.php','<?php /* synthetic compiled view */');
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(0);
        $result=app(DiagnosticActionService::class)->repair($this->admin(),$i['id'],$i['revision'],'views_clear',(string)Str::uuid());
        $this->assertFalse($result['ok']);
    }

}
