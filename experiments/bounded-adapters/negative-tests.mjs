// Historical R1 bypasses are expected negatives, never adapter coverage PASS.
import fs from 'node:fs';
import test from 'node:test';
import assert from 'node:assert/strict';
import jsdom from 'jsdom';
const {JSDOM,requestInterceptor,VirtualConsole}=jsdom;
const source=fs.readFileSync(new URL('../firewall-poc/controller.js',import.meta.url),'utf8');
const rules=JSON.parse(fs.readFileSync(new URL('../firewall-poc/rules.json',import.meta.url)));
for(const method of ['Element.append','connected.src'])test('R1 BYPASSED '+method+' remains reproducible',async()=>{
  const requests=[];
  const resources={interceptors:[requestInterceptor(({url})=>{requests.push(url);return new Response('window.bypassExecuted=true;');})]};
  const config=JSON.stringify({mode:'D',version:'1',enabled:true,rules}).replaceAll('"','&quot;');
  const dom=new JSDOM(`<html><head><script data-config="${config}">${source}</script></head><body></body></html>`,{url:'http://itd-cookies.local/',runScripts:'dangerously',resources,virtualConsole:new VirtualConsole()});
  const script=dom.window.document.createElement('script');
  if(method==='Element.append'){script.src='/?fw_asset=analytics';dom.window.document.head.append(script);}
  else{dom.window.document.head.appendChild(script);script.src='/?fw_asset=analytics';}
  await new Promise(resolve=>setTimeout(resolve,40));
  assert.equal(requests.length,1);assert.equal(dom.window.bypassExecuted,true);dom.window.close();
});
