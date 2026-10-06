// Local fixture protocol integration, not browser automation. No vendor requests.
import assert from 'node:assert/strict';
const base = 'http://itd-cookies.local/';
const results = [];
for (const kind of ['html','json','xml','download','redirect','404','500','gzip','length','stream']) {
  const response = await fetch(base+'?fw_case=response&fw_strategy=D&fw_response='+kind,{redirect:'manual'});
  const body = await response.text();
  const transformed = body.includes('id="itd-fw-poc-controller"');
  const expected = ['html','length'].includes(kind);
  assert.equal(transformed,expected,kind+' rewrite eligibility');
  assert.equal(body.includes('data-itd-poc-src='),expected,kind+' source eligibility');
  if (kind==='json') assert.ok(JSON.parse(body).literal.includes('<script'));
  if (kind==='length' && response.headers.has('content-length')) assert.equal(Number(response.headers.get('content-length')),Buffer.byteLength(body));
  if (kind==='404'||kind==='500'||kind==='redirect') assert.equal(response.status,kind==='redirect'?302:Number(kind));
  results.push({kind,status:response.status,transformed,type:response.headers.get('content-type'),encoding:response.headers.get('content-encoding'),length:response.headers.get('content-length')});
}
const consent = analytics => 'itd_cookies_consent='+encodeURIComponent(JSON.stringify({schema:1,version:'1',expiresAt:Date.now()+100000,categories:{necessary:true,functional:false,analytics,marketing:false}}));
const url=base+'?fw_case=response&fw_strategy=D&fw_response=html';
const normalize=html=>html.replaceAll(/fw_doc=[a-f0-9]{32}/g,'fw_doc=DOC');
const denied=normalize(await (await fetch(url,{headers:{cookie:consent(false)}})).text());
const granted=normalize(await (await fetch(url,{headers:{cookie:consent(true)}})).text());
assert.equal(denied,granted,'common blocked HTML independent of consent; document counter ID normalized');
for (const path of ['?rest_route=/','?feed=rss2','?sitemap=index','?robots=1','wp-admin/']) {
  const target=new URL(path,base);
  target.searchParams.set('fw_case','exclusion');
  target.searchParams.set('fw_strategy','D');
  const response=await fetch(target,{redirect:'manual'});
  const body=await response.text();
  assert.equal(body.includes('id="itd-fw-poc-controller"'),false,path+' exclusion');
  results.push({path,status:response.status,transformed:false,type:response.headers.get('content-type')});
}
console.log(JSON.stringify({pass:true,results,commonBlockedHtml:true},null,2));
