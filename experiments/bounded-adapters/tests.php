<?php
require __DIR__.'/url-classifier.php';
require __DIR__.'/wp-adapter.php';
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$count=0;
function check($condition, $message) { global $count; $count++; if (!$condition) throw new RuntimeException($message); }
$corpus=json_decode(file_get_contents(__DIR__.'/url-corpus.json'),true);
foreach ($corpus['cases'] as $case) check(ITD_Bounded_URL::category($case[0],$corpus['base'],$corpus['rules'])===$case[1],$case[0]);
$adapter=new ITD_Bounded_WP_Adapter(array());
$body="\nwindow.example='Привет';\n";
$blocked=$adapter->block('<script nonce="test-nonce" id="owned" type="text/javascript">'.$body.'</script>','owned:before');
check(strpos($blocked,'>'.$body.'</script>')!==false,'inline bytes changed');
check(strpos($blocked,'nonce="test-nonce"')!==false,'nonce changed');
$url='/sdk/../sdk/tag.js?q=a&amp;b=c';
$blocked=$adapter->block('<script src="'.$url.'" integrity="sha384-test" crossorigin="anonymous"></script>','owned:main');
check(strpos($blocked,'data-itd-bounded-src="'.$url.'"')!==false,'download URL rewritten');
check(strpos($blocked,'integrity="sha384-test" crossorigin="anonymous"')!==false,'attrs changed');
try {$adapter->block('<script type="module">code</script>','owned:main'); check(false,'module accepted');}catch(RuntimeException $e){check($e->getMessage()==='UNSUPPORTED_SCRIPT_TYPE','wrong module result');}
$groups=array(array('id'=>'one','category'=>'analytics','handles'=>array('sdk'),'resources'=>array(),'provider'=>'ga4'));
$owner=new ITD_Bounded_WP_Adapter($groups,array('ga4'));
check($owner->groups[0]['issue']==='FAILED_OWNERSHIP_CONFLICT','native ownership conflict');
$groups[0]['resources']=array('noscript');
$fallback=new ITD_Bounded_WP_Adapter($groups);
check($fallback->groups[0]['issue']==='UNSUPPORTED_ANCILLARY_RESOURCES','noscript must not claim support');
// Keep fail-open HTML a distinct, honest outcome, plus byte preservation for inert data.
$parser=new ITD_FW_POC_Parser($corpus['rules']);
$bad='<script src="https://mc.yandex.ru/metrika/tag.js"> <!-- <script </script>';
check($parser->rewrite($bad)===$bad && $parser->outcome==='BYPASS_UNSUPPORTED_HTML','unsupported HTML mislabeled');
$data='<script type="application/ld+json">{"url":"https://mc.yandex.ru/metrika/tag.js"}</script><script type="importmap">{"imports":{}}</script><template><script src="https://mc.yandex.ru/metrika/tag.js"></script></template>';
check($parser->rewrite($data)===$data,'data/template mutated');
echo "Experimental PHP: $count assertions PASS\n";
