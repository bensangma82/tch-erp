<x-app-layout>
@include('assistant.styles')
<div class="erp-assistant ea-page"><header class="ea-hero"><div><small>YOUR WORK · YOUR NEXT ACTION</small><h1>ERP Assistant</h1><p>A local work companion, grounded in your permitted ERP queues.</p></div><span class="ea-privacy">ERP data stays in the hospital system</span></header>
@include('assistant.panel', ['widget'=>false])
</div>
@include('assistant.script')
</x-app-layout>
