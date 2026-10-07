import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
const budget=JSON.parse(fs.readFileSync(new URL('./performance-budget.json',import.meta.url)));
let failed=false;
for(const item of budget.cases){
  const result=spawnSync(process.env.PHP_BIN||'php',[fileURLToPath(new URL('../firewall-poc/benchmark.php',import.meta.url)),String(item.kib),'tokenizer',String(item.tags)],{encoding:'utf8',timeout:budget.processTimeoutMs});
  if(result.error||result.status!==0){console.error({case:item,error:result.error?.message,stderr:result.stderr});failed=true;continue;}
  const measured=JSON.parse(result.stdout);
  const pass=measured.correct&&measured.median_ms<=item.medianMs&&measured.max_ms<=budget.maxMs&&measured.peak_bytes<=budget.peakMiB*1024*1024&&measured.peak_delta_bytes<=budget.deltaMiB*1024*1024;
  console.log(JSON.stringify({...measured,median_budget_ms:item.medianMs,timeout_ms:budget.processTimeoutMs,result:pass?'PASS':'FAILED'}));
  if(!pass)failed=true;
}
if(failed)process.exitCode=1;
