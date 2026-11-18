@once
<script>
(function(){
    'use strict';
    const element=(tag,cls,text)=>{const e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=String(text);return e;};
    const localLink=(path)=>{if(typeof path!=='string'||!path.startsWith('/')||path.startsWith('//'))return null;try{const u=new URL(path,location.origin);return u.origin===location.origin?u.href:null;}catch(e){return null;}};
    document.querySelectorAll('[data-erp-assistant]').forEach(root=>{
        if(root.dataset.initialized)return;root.dataset.initialized='1';
        const messages=root.querySelector('[data-ea-messages]'),status=root.querySelector('[data-ea-status]'),input=root.querySelector('[data-ea-input]'),send=root.querySelector('[data-ea-send]'),mode=root.querySelector('[data-ea-mode]');
        const floating=root.closest('[data-erp-widget]'),launch=floating?.querySelector('[data-ea-launch]'),container=floating?.querySelector('.ea-widget-panel'),badge=floating?.querySelector('[data-ea-badge]');
        let busy=false;
        function setBusy(value){busy=value;send.disabled=value;root.querySelectorAll('[data-ea-prompt],[data-ea-refresh]').forEach(b=>b.disabled=value);status.textContent=value?'Checking your current work…':'';}
        function append(node){const welcome=messages.querySelector('.ea-welcome');if(welcome)welcome.remove();messages.appendChild(node);while(messages.children.length>12)messages.firstElementChild.remove();messages.scrollTop=messages.scrollHeight;}
        function render(data){
            if(!data||typeof data.message!=='string'||!Array.isArray(data.queues))throw new Error('Invalid briefing.');
            const box=element('article','ea-reply');box.append(element('div','ea-speaker','ERP Assistant'),element('p','ea-answer',data.message));
            box.append(element('p','ea-asof','Checked '+new Date(data.as_of).toLocaleString()));
            data.queues.forEach(q=>{
                const card=element('section','ea-task'),head=element('div','ea-task-head');head.append(element('strong','',q.title),element('b','ea-count',q.count));card.append(head);
                if(q.overdue>0)card.append(element('span','ea-overdue',q.overdue+' overdue by recorded deadline'));
                card.append(element('p','',q.next_action));
                (Array.isArray(q.samples)?q.samples:[]).forEach(sample=>{
                    const row=element('div','ea-sample'),url=localLink(sample.url),title=sample.reference+' · '+sample.title;
                    if(url){const a=element('a','',title);a.href=url;row.append(a);}else row.append(element('span','',title));
                    if(sample.due)row.append(element('small',sample.overdue?'ea-late':'','Due '+sample.due));card.append(row);
                });
                const url=localLink(q.url);if(url){const a=element('a','ea-review','Open work screen →');a.href=url;card.append(a);}box.append(card);
            });
            if(!data.complete)box.append(element('p','ea-partial','Queues not checked: '+(data.unavailable||[]).join(', ')+'. Counts are incomplete.'));
            box.append(element('small','ea-scope',data.scope));append(box);
            mode.textContent=data.mode==='local_ai'?'Local AI + live ERP data':data.mode==='erp_briefing'?'Live ERP briefing':'Guided matching + live ERP data';
            mode.title=data.notice||'';
            if(data.notice)box.append(element('small','ea-engine',data.notice));
            if(badge){const total=Number.isFinite(data.briefing_total)?data.briefing_total:data.count;badge.textContent=total>99?'99+':String(total);badge.hidden=total===0;launch.setAttribute('aria-label','My work: '+total+' pending queue entries');}
        }
        async function request(question){
            if(busy)return;setBusy(true);const abort=new AbortController(),timer=setTimeout(()=>abort.abort(),25000);
            try{
                const options={credentials:'same-origin',headers:{'Accept':'application/json'},signal:abort.signal,cache:'no-store'};
                let url=root.dataset.briefUrl;
                if(question){url=root.dataset.askUrl;options.method='POST';options.headers['Content-Type']='application/json';options.headers['X-CSRF-TOKEN']=document.querySelector('meta[name="csrf-token"]')?.content||'';options.body=JSON.stringify({question});append(element('div','ea-user',question));}
                const response=await fetch(url,options);
                if(!response.ok)throw new Error(response.status===429?'Please wait a moment before asking again.':response.status===401||response.status===419?'Your session expired. Refresh the page and sign in again.':'The assistant could not check your work. Try again or use the normal ERP screens.');
                render(await response.json());input.value='';
            }catch(e){status.textContent=e.name==='AbortError'?'The check took too long. Your ERP workflows were not changed. Try again.':e.message||'Assistant unavailable.';}
            finally{clearTimeout(timer);busy=false;send.disabled=false;root.querySelectorAll('[data-ea-prompt],[data-ea-refresh]').forEach(b=>b.disabled=false);if(status.textContent==='Checking your current work…')status.textContent='';}
        }
        root.querySelector('[data-ea-form]').addEventListener('submit',event=>{event.preventDefault();const question=input.value.trim();if(question.length>=2&&question.length<=500)request(question);});
        root.querySelector('[data-ea-refresh]').addEventListener('click',()=>request());
        root.querySelectorAll('[data-ea-prompt]').forEach(b=>b.addEventListener('click',()=>request(b.dataset.eaPrompt)));
        if(launch){launch.addEventListener('click',()=>{const open=container.hidden;container.hidden=!open;launch.setAttribute('aria-expanded',String(open));if(open){request();input.focus();}});root.querySelector('[data-ea-close]').addEventListener('click',()=>{container.hidden=true;launch.setAttribute('aria-expanded','false');launch.focus();});root.addEventListener('keydown',event=>{if(event.key==='Escape'){container.hidden=true;launch.setAttribute('aria-expanded','false');launch.focus();}});}
        if(root.dataset.auto==='1')request();
    });
})();
</script>
@endonce
