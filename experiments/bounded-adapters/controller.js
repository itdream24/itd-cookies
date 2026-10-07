/* Original GPL-2.0-or-later. No interception of unowned DOM or network APIs. */
(function(window,document){
    'use strict';
    const manifest=document.getElementById('itd-bounded-manifest');
    if(!manifest)return;
    const config=JSON.parse(manifest.textContent);
    const execute=node=>new Promise((resolve,reject)=>{
        if(node.virtual){resolve();return;}
        const old=document.querySelector('[data-itd-bounded-node="'+node.id+'"]');
        if(!old){reject(new Error('MISSING_DOM_NODE'));return;}
        const script=document.createElement('script');
        for(const attr of old.attributes) {
            if(attr.name==='type'||attr.name==='data-itd-bounded-src'||attr.name==='data-itd-bounded-type')continue;
            script.setAttribute(attr.name,attr.value);
        }
        if(old.nonce)script.nonce=old.nonce;
        const type=old.getAttribute('data-itd-bounded-type');if(type)script.type=type;
        script.textContent=old.textContent;
        const src=old.getAttribute('data-itd-bounded-src');
        if(!src){old.replaceWith(script);resolve();return;}
        // Dependencies are sequenced by the group graph. A slow group must not
        // occupy the browser's shared ordered dynamic-script execution queue.
        script.async=true;
        script.setAttribute('async','');
        const timer=window.setTimeout(()=>{script.remove();reject(new Error('LOAD_TIMEOUT'));},config.timeoutMs||3000);
        script.onload=()=>{window.clearTimeout(timer);resolve();};
        script.onerror=()=>{window.clearTimeout(timer);reject(new Error('LOAD_ERROR'));};
        script.src=src; // Original URL, never the classifier's comparison representation.
        old.replaceWith(script);
    });
    const runner=new window.ITDBoundedRunner(config.groups,execute);
    window.ITDBoundedResearch=runner;
    const apply=()=>runner.grant({analytics:!!window.ITDCookies?.allowed('analytics'),marketing:!!window.ITDCookies?.allowed('marketing')});
    window.addEventListener('itd_cookies_consent_changed',apply);
    apply();
})(window,document);
