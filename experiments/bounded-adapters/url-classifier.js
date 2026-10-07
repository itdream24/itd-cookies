/* Original GPL-2.0-or-later. Comparison only, not a URL rewriter. */
(function(root) {
    'use strict';
    root.ITDBoundedURL = function(url,base,rules) {
        if(typeof url!=='string' || [...url].some(c=>c.charCodeAt(0)<=32||c.charCodeAt(0)===127||c==='\\') || /%(?![a-f0-9]{2})/i.test(url)) return null;
        const authority=url.match(/^(?:https?:)?\/\/([^/?#]*)/i);
        if(authority && (authority[1].includes('%')||[...authority[1]].some(c=>c.charCodeAt(0)>127))) return null;
        if(/^[a-z][a-z0-9+.-]*:/i.test(url) && !/^https?:\/\//i.test(url)) return null;
        let parsed;
        try {parsed=new URL(url,base);} catch {return null;}
        if(!['http:','https:'].includes(parsed.protocol)||parsed.username||parsed.password||parsed.port) return null;
        const found=rules.find(r=>parsed.hostname===r.host && (r.path.endsWith('/') ? parsed.pathname.startsWith(r.path) : parsed.pathname===r.path));
        return found ? found.category : null;
    };
})(typeof window==='undefined' ? globalThis : window);
