/* Original local research code, GPL-2.0-or-later. Not a browser sandbox. */
(function () {
    'use strict';
    const owner = document.currentScript;
    const config = JSON.parse(owner.getAttribute('data-config'));
    const nativeAppend = Node.prototype.appendChild;
    const nativeInsert = Node.prototype.insertBefore;
    let choice = { analytics: false, marketing: false };
    let chain = Promise.resolve();
    const scheduled = new WeakSet();
    const audit = { queued: 0, replayed: 0, missed: 0, errors: [], attributes: [], mode: config.mode };
    window.ITDFirewallPocAudit = audit;
    function classify(url) {
        let parsed;
        try { parsed = new URL(url, location.href); } catch { return null; }
        if (parsed.username || parsed.password) return null;
        const host = parsed.hostname.toLowerCase();
        if (host === 'itd-cookies.local' && parsed.pathname === '/') {
            const asset = parsed.searchParams.get('fw_asset');
            if (['analytics','library','dependent','module','module-import','dynamic','late-src','hint'].includes(asset)) return 'analytics';
            if (['marketing','pixel'].includes(asset)) return 'marketing';
        }
        const match = config.rules.find(rule => host === rule.host && parsed.pathname.startsWith(rule.path));
        return match ? match.category : null;
    }
    function executable(el) { return ['', 'module', 'text/javascript', 'application/javascript'].includes((el.getAttribute('type') || '').toLowerCase()); }
    function block(el) {
        if (el.nodeType !== 1 || el.tagName !== 'SCRIPT' || !executable(el) || el.hasAttribute('data-itd-poc-active')) return;
        const explicit = el.getAttribute('data-itd-cookies-category');
        const category = ['analytics','marketing'].includes(explicit) ? explicit : classify(el.getAttribute('src') || '');
        if (!category || choice[category]) return;
        if (el.hasAttribute('src')) { el.setAttribute('data-itd-poc-src',el.getAttribute('src')); el.removeAttribute('src'); }
        if (el.hasAttribute('type')) el.setAttribute('data-itd-poc-type',el.getAttribute('type'));
        el.setAttribute('type','application/x-itd-poc-blocked');
        el.setAttribute('data-itd-poc-category',category); audit.queued++;
    }
    function guard(node) {
        if (node.nodeType === 11) node.querySelectorAll('script').forEach(block);
        else block(node);
    }
    if (config.mode === 'C' || config.mode === 'D') {
        Node.prototype.appendChild = function (node) { guard(node); return nativeAppend.call(this,node); };
        Node.prototype.insertBefore = function (node,reference) { guard(node); return nativeInsert.call(this,node,reference); };
    }
    // Observer is diagnostic only; it runs after insertion and cannot promise zero requests.
    new MutationObserver(records => {
        for (const record of records) for (const node of record.addedNodes) {
            if (node.nodeType === 1 && node.tagName === 'SCRIPT' && !node.hasAttribute('data-itd-poc-active') && node.src && classify(node.src) && !choice[classify(node.src)]) {
                audit.missed++;
                if (config.mode === 'observer') block(node);
            }
        }
        activate();
    }).observe(document.documentElement,{childList:true,subtree:true});
    function replay(old) {
        return new Promise(resolve => {
            const category = old.getAttribute('data-itd-poc-category');
            if (!choice[category] || !old.parentNode) { scheduled.delete(old); resolve(); return; }
            const next = document.createElement('script');
            for (const attr of old.attributes) {
                if (attr.name === 'type' || attr.name.startsWith('data-itd-poc-')) continue;
                next.setAttribute(attr.name,attr.value);
            }
            if (old.nonce) next.nonce = old.nonce;
            const type = old.getAttribute('data-itd-poc-type');
            if (type !== null) next.setAttribute('type',type);
            const source = old.getAttribute('data-itd-poc-src');
            const skip = next.noModule && 'noModule' in HTMLScriptElement.prototype;
            if (!next.hasAttribute('async') && type !== 'module') next.async = false;
            next.setAttribute('data-itd-poc-active',category);
            next.textContent = old.textContent;
            if (source !== null) next.src = source;
            audit.attributes.push({ id: old.id, type: next.type, nonce: next.nonce, async: next.hasAttribute('async'), defer: next.defer, nomodule: next.noModule, crossorigin: next.crossOrigin, integrity: next.integrity, referrerpolicy: next.referrerPolicy, className: next.className, custom: next.getAttribute('data-custom') });
            if ((source !== null || type === 'module') && !skip) {
                const timeout = setTimeout(() => { audit.errors.push('replay-timeout:'+old.id); resolve(); },3000);
                next.addEventListener('load',() => { clearTimeout(timeout); resolve(); },{once:true});
                next.addEventListener('error',() => { clearTimeout(timeout); audit.errors.push('replay-error:'+old.id); resolve(); },{once:true});
            }
            old.parentNode.replaceChild(next,old); audit.replayed++;
            if ((source === null && type !== 'module') || skip) resolve();
        });
    }
    function activate() {
        for (const el of document.querySelectorAll('script[type="application/x-itd-poc-blocked"]')) {
            if (!choice[el.getAttribute('data-itd-poc-category')] || scheduled.has(el) || el.closest('template')) continue;
            scheduled.add(el); chain = chain.then(() => replay(el));
        }
    }
    function readConsent() {
        try {
            const cookie = document.cookie.split(';').map(x => x.trim()).find(x => x.startsWith('itd_cookies_consent='));
            const state = cookie ? JSON.parse(decodeURIComponent(cookie.slice('itd_cookies_consent='.length))) : null;
            const keys = (value,allowed) => value && typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length === allowed.length && Object.keys(value).every(k => allowed.includes(k));
            if (config.enabled && keys(state,['schema','version','expiresAt','categories']) && state.schema===1 && state.version===config.version && Number.isSafeInteger(state.expiresAt) && state.expiresAt>Date.now() && keys(state.categories,['necessary','functional','analytics','marketing']) && state.categories.necessary===true && ['functional','analytics','marketing'].every(k=>typeof state.categories[k]==='boolean')) choice=state.categories;
        } catch { /* Malformed/missing consent denies optional categories. */ }
    }
    readConsent();
    window.addEventListener('itd_cookies_consent_changed',event => {
        if (!event.detail || !event.detail.categories) return;
        choice={analytics:event.detail.categories.analytics===true,marketing:event.detail.categories.marketing===true};
        activate();
    });
    document.addEventListener('DOMContentLoaded',activate);
    activate();
})();
