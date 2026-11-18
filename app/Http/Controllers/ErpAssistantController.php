<?php

namespace App\Http\Controllers;

use App\Services\Assistant\ErpAssistantService;
use Illuminate\Http\{JsonResponse,Request,Response};

final class ErpAssistantController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->is_active,403);
        return response()->view('assistant.index')->header('Cache-Control','private, no-store');
    }

    public function briefing(Request $request,ErpAssistantService $assistant): JsonResponse
    {
        abort_unless($request->user()?->is_active,403);
        return response()->json($assistant->briefing($request->user()))->header('Cache-Control','private, no-store');
    }

    public function ask(Request $request,ErpAssistantService $assistant): JsonResponse
    {
        abort_unless($request->user()?->is_active,403);
        $data=$request->validate(['question'=>['required','string','min:2','max:500']]);
        $question=preg_replace('/[\x00-\x1F\x7F]/',' ',mb_convert_encoding($data['question'],'UTF-8','UTF-8'));
        return response()->json($assistant->ask($request->user(),trim($question)))->header('Cache-Control','private, no-store');
    }
}
