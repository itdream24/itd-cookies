/* Functional controls and readable instrumentation; only localhost mock endpoints. */
(function () {
    'use strict';
    window.FWFixture = window.FWFixture || { executions: {}, order: [], errors: [] };
    const probe = document.getElementById('fw-probe');
    if (!probe) return;
    const config = JSON.parse(probe.getAttribute('data-config'));
    // jQuery-like ordinary dependency, no tracker classification.
    window.FWJQuery = selector => document.querySelector(selector);
    const evidence = document.getElementById('fw-evidence');
    async function refresh() {
        evidence.setAttribute('aria-busy','true');
        const response = await fetch('/?fw_stats='+config.doc,{cache:'no-store'});
        const counts = await response.json();
        evidence.textContent = JSON.stringify({ case: config.case, doc: config.doc, requests: counts, executions: window.FWFixture.executions, order: window.FWFixture.order, errors: window.FWFixture.errors, firewall: window.ITDFirewallPocAudit || null, width: document.documentElement.scrollWidth, viewport: innerWidth },null,2);
        evidence.setAttribute('aria-busy','false');
    }
    FWJQuery('#fw-refresh').addEventListener('click',refresh);
    if (FWJQuery('#fw-clear')) FWJQuery('#fw-clear').addEventListener('click',() => {
        for (const name of ['itd_cookies_consent','itd_modubricks_consent','itd_cookies_legacy_migrated']) document.cookie=name+'=; Max-Age=0; Path=/; SameSite=Lax';
        location.reload();
    });
    if (FWJQuery('#fw-menu')) FWJQuery('#fw-menu').addEventListener('click',() => { FWJQuery('#fw-menu-result').hidden=!FWJQuery('#fw-menu-result').hidden; });
    if (FWJQuery('#fw-form')) FWJQuery('#fw-form').addEventListener('submit',event => { event.preventDefault(); FWJQuery('#fw-form-result').textContent='Validation works'; });
    if (FWJQuery('#fw-grant')) FWJQuery('#fw-grant').addEventListener('click',() => { dispatchEvent(new CustomEvent('itd_cookies_consent_changed',{detail:{categories:{analytics:true,marketing:false}}})); });
    function ready() {
        if (['full','dynamic','observer','append-bypass','late-src'].includes(config.case)) {
            const script = document.createElement('script');
            if (config.case==='late-src') {
                document.head.appendChild(script);script.src=config.lateSrc;
            } else {
                script.src=config.dynamic;
                if(config.case==='append-bypass') document.head.append(script);
                else document.head.appendChild(script);
            }
        }
        document.getElementById('fw-ready').textContent='Fixtures ready';
        refresh();
    }
    if (document.readyState==='loading')document.addEventListener('DOMContentLoaded',() => setTimeout(ready,100));
    else setTimeout(ready,100);
})();
