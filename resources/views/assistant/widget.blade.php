@include('assistant.styles')
<div class="erp-assistant ea-floating" data-erp-widget>
    <button class="ea-launch" type="button" aria-expanded="false" aria-controls="ea-widget-panel" data-ea-launch><span aria-hidden="true">✦</span><span>My work</span><b data-ea-badge hidden></b></button>
    <div id="ea-widget-panel" class="ea-widget-panel" hidden>
        @include('assistant.panel', ['widget'=>true])
    </div>
</div>
@include('assistant.script')
