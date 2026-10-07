/* Original GPL-2.0-or-later. Local fixture UI and evidence, not production code. */
(function(window,document){
    'use strict';
    window.R2Fixture=window.R2Fixture||{order:[],inits:{analytics:0,marketing:0},errors:[]};
    window.addEventListener('securitypolicyviolation',event=>window.R2Fixture.errors.push('CSP:'+event.violatedDirective));
    function setup(){
        const probe=document.getElementById('r2-probe');if(!probe)return;
        const config=JSON.parse(probe.dataset.config);
        const refresh=async()=>{
            const output=document.getElementById('r2-evidence');output.setAttribute('aria-busy','true');
            const counts=await (await window.fetch('/?r2_stats='+config.doc,{cache:'no-store'})).json();
            output.textContent=JSON.stringify({counts,order:window.R2Fixture.order,inits:window.R2Fixture.inits,errors:window.R2Fixture.errors,groups:window.ITDBoundedResearch?.snapshot()||[]},null,2);
            output.setAttribute('aria-busy','false');
        };
        document.getElementById('r2-refresh').addEventListener('click',refresh);
        document.getElementById('r2-clear').addEventListener('click',()=>{document.cookie='itd_cookies_consent=; Max-Age=0; Path=/; SameSite=Lax';window.location.reload();});
        document.getElementById('r2-grant').addEventListener('click',()=>{if(!config.standalone)return;window.ITDCookies={allowed:c=>c==='analytics'};window.dispatchEvent(new window.Event('itd_cookies_consent_changed'));});
        document.getElementById('r2-menu').addEventListener('click',()=>{const menu=document.getElementById('r2-menu-result');menu.hidden=!menu.hidden;});
        document.getElementById('r2-form').addEventListener('submit',event=>{event.preventDefault();document.getElementById('r2-form-result').textContent='Form works';});
        refresh();
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',setup);else setup();
})(window,document);
