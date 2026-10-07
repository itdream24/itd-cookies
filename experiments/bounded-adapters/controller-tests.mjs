import fs from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
import jsdom from 'jsdom';
import './url-classifier.js';
import './group-engine.js';
const {JSDOM,requestInterceptor,VirtualConsole}=jsdom;
const corpus=JSON.parse(fs.readFileSync(new URL('./url-corpus.json',import.meta.url)));
const group=(id='one',category='analytics')=>({id,category,resources:[],nodes:[{id:id+':sdk',deps:[]},{id:id+':init',deps:[id+':sdk']}]});
test('shared PHP/JS URL corpus; no download rewriting',()=>{
  for(const [url,expected] of corpus.cases)assert.equal(globalThis.ITDBoundedURL(url,corpus.base,corpus.rules),expected,url);
});
test('denied, order, exactly once, independent categories',async()=>{
  const order=[];const runner=new globalThis.ITDBoundedRunner([group(),group('two','marketing')],async node=>order.push(node.id));
  await runner.grant({});assert.deepEqual(order,[]);
  await runner.grant({analytics:true});assert.deepEqual(order,['one:sdk','one:init']);
  await runner.grant({analytics:true,marketing:true});await runner.grant({analytics:true,marketing:true});
  assert.deepEqual(order,['one:sdk','one:init','two:sdk','two:init']);
});
for(const failure of ['LOAD_ERROR','LOAD_TIMEOUT'])test(failure+' skips dependent inline without starving independent group',async()=>{
  const executed=[];const runner=new globalThis.ITDBoundedRunner([group('bad'),group('good')],async node=>{if(node.id==='bad:sdk')throw Error(failure);executed.push(node.id);});
  await runner.grant({analytics:true});
  assert.deepEqual(executed,['good:sdk','good:init']);
  assert.equal(runner.snapshot()[0].states['bad:init'],'SKIPPED_DEPENDENCY');
  assert.equal(runner.snapshot()[1].state,'DONE');
});
for(const issue of ['MISSING_DEPENDENCY','CYCLE','FAILED_OWNERSHIP_CONFLICT'])test(issue+' fails before any owned execution',async()=>{
  const bad=group('bad');
  if(issue==='MISSING_DEPENDENCY')bad.nodes[0].deps=['missing'];
  if(issue==='CYCLE')bad.nodes[0].deps=['bad:init'];
  if(issue==='FAILED_OWNERSHIP_CONFLICT')bad.issue=issue;
  const executed=[];const runner=new globalThis.ITDBoundedRunner([bad,group('good')],async node=>executed.push(node.id));
  await runner.grant({analytics:true});assert.deepEqual(executed,['good:sdk','good:init']);assert.equal(runner.snapshot()[0].errors[0],issue);
});
const read=name=>fs.readFileSync(new URL(name,import.meta.url),'utf8');
const pause=ms=>new Promise(resolve=>setTimeout(resolve,ms));
for(const scenario of ['success','error','timeout','ownership'])test('DOM replay '+scenario+'; native loader conflict; unknown inline remains independent',async()=>{
  const requests=[];
  const own=group();if(scenario==='ownership')own.issue='FAILED_OWNERSHIP_CONFLICT';
  const config=JSON.stringify({groups:[own,group('good','marketing')],timeoutMs:80});
  const resources={interceptors:[requestInterceptor(async({url})=>{
    requests.push(url);
    if(url.includes('/sdk')&&scenario==='timeout')await pause(180);
    if(url.includes('/sdk')&&scenario==='error')throw Error('fixture unavailable');
    return new Response(url.includes('gtag')?'window.nativeLoaded=true;':'window.order.push("sdk");window.library={ready:true};');
  })]};
  const native=fs.readFileSync(new URL('../../assets/js/providers.js',import.meta.url),'utf8');
  const dom=new JSDOM(`<script>window.order=[];window.choice={};window.ITDCookies={allowed:c=>!!window.choice[c]};</script><script>${native}</script><script>window.ITDCookiesProviderLoader.load(${scenario==='ownership'?'[{type:"ga4",id:"G-TEST12345"}]':'[]'}, c=>c==='analytics');</script><script>try{window.library.ready;}catch(e){window.unknownInlineFailure=e.name;}</script><script type="application/x-itd-bounded" data-itd-bounded-node="one:sdk" data-itd-bounded-src="/sdk/../sdk?original=yes&amp;q=1" nonce="preserved" integrity="" crossorigin="anonymous"></script><script type="application/x-itd-bounded" data-itd-bounded-node="one:init">window.order.push('init');</script><script type="application/x-itd-bounded" data-itd-bounded-node="good:sdk" data-itd-bounded-src="/independent"></script><script type="application/x-itd-bounded" data-itd-bounded-node="good:init">window.order.push('good-init');</script><script id="itd-bounded-manifest" type="application/json">${config}</script><script>${read('./group-engine.js')}</script><script>${read('./controller.js')}</script>`,{url:'http://itd-cookies.local/',runScripts:'dangerously',resources,virtualConsole:new VirtualConsole()});
  await pause(30);assert.equal(requests.filter(u=>u.includes('/sdk')).length,0);assert.equal(dom.window.unknownInlineFailure,'TypeError');
  dom.window.choice={analytics:true,marketing:true};dom.window.dispatchEvent(new dom.window.Event('itd_cookies_consent_changed'));
  await pause(120);
  const snapshot=dom.window.ITDBoundedResearch.snapshot();assert.equal(snapshot[1].state,'DONE',JSON.stringify(snapshot));
  if(scenario==='success'){assert.equal(snapshot[0].state,'DONE');assert.equal([...dom.window.order].filter(x=>x==='init').length,1);assert.equal(dom.window.document.querySelector('[data-itd-bounded-node="one:sdk"]').getAttribute('nonce'),'preserved');}
  else {assert.equal(snapshot[0].state,'FAILED');assert.equal([...dom.window.order].includes('init'),false);}
  if(scenario==='ownership'){assert.equal(requests.filter(u=>u.includes('/sdk')).length,0);assert.equal(requests.filter(u=>u.includes('googletagmanager.com')).length,1);}
  dom.window.dispatchEvent(new dom.window.Event('itd_cookies_consent_changed'));await pause(20);
  assert.equal(requests.filter(u=>u.includes('/sdk')).length,scenario==='ownership'?0:1);dom.window.close();
});
