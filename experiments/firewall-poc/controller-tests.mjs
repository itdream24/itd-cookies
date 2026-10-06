import fs from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
import jsdom from 'jsdom';
const { JSDOM, requestInterceptor, VirtualConsole } = jsdom;
const source=fs.readFileSync(new URL('./controller.js',import.meta.url),'utf8');
const rules=JSON.parse(fs.readFileSync(new URL('./rules.json',import.meta.url),'utf8'));
const pause=ms=>new Promise(resolve=>setTimeout(resolve,ms));
test('classified scripts make zero mock requests before grant; library/inline order and repeat grant',async()=>{
  const requests=[];
  const resources = { interceptors: [requestInterceptor(async ({url}) => {
      requests.push(url);
      if(url.includes('library')) { await pause(20); return new Response('window.library={ready:true};window.order.push("A");'); }
      return new Response('window.unknownWorks=true;');
  })] };
  const config=JSON.stringify({mode:'D',version:'1',enabled:true,rules}).replaceAll('"','&quot;');
  const dom=new JSDOM(`<html><head><script data-config="${config}">${source}</script><script>window.order=[];</script></head><body><script src="https://cdn.example.test/application.js"></script><script type="application/x-itd-poc-blocked" data-itd-poc-src="/?fw_asset=library" data-itd-poc-category="analytics"></script><script type="application/x-itd-poc-blocked" data-itd-poc-category="analytics">if(!window.library.ready)throw Error('order');window.order.push('B');</script></body></html>`,{url:'http://itd-cookies.local/',runScripts:'dangerously',resources,virtualConsole:new VirtualConsole()});
  await pause(70);
  assert.equal(dom.window.unknownWorks,true);
  assert.equal(requests.filter(x=>x.includes('fw_asset')).length,0);
  dom.window.dispatchEvent(new dom.window.CustomEvent('itd_cookies_consent_changed',{detail:{categories:{analytics:true,marketing:false}}}));
  await pause(70);
  assert.deepEqual([...dom.window.order],['A','B']);
  assert.equal(requests.filter(x=>x.includes('fw_asset')).length,1);
  dom.window.dispatchEvent(new dom.window.CustomEvent('itd_cookies_consent_changed',{detail:{categories:{analytics:true,marketing:false}}}));
  await pause(30);
  assert.equal(requests.filter(x=>x.includes('fw_asset')).length,1);
  dom.window.close();
});
test('dynamic append/insert known denied, unknown allowed; non-JS types retained',async()=>{
  const requests=[];
  const resources = {interceptors:[requestInterceptor(({url})=>{requests.push(url);return new Response('window.functional=true;');})]};
  const config=JSON.stringify({mode:'D',version:'1',enabled:true,rules}).replaceAll('"','&quot;');
  const dom=new JSDOM(`<html><head><script data-config="${config}">${source}</script></head><body></body></html>`,{url:'http://itd-cookies.local/',runScripts:'dangerously',resources,virtualConsole:new VirtualConsole()});
  const d=dom.window.document;
  for(const asset of ['analytics','marketing']){const s=d.createElement('script');s.src='/?fw_asset='+asset;d.head.appendChild(s);}
  const functional=d.createElement('script');functional.src='/?fw_asset=unknown';d.head.insertBefore(functional,d.head.firstChild);
  const json=d.createElement('script');json.type='application/ld+json';json.setAttribute('data-itd-cookies-category','analytics');json.textContent='{"provider":"clarity.ms"}';d.body.appendChild(json);
  await pause(50);
  assert.equal(requests.filter(x=>!x.includes('unknown')).length,0);
  assert.equal(dom.window.functional,true);assert.equal(json.type,'application/ld+json');
  dom.window.close();
});
