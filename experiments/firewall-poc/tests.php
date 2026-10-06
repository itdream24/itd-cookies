<?php
/** Standalone PHP 7.4+ research tests, not production runtime. */
require __DIR__.'/parser.php';
$parser=new ITD_FW_POC_Parser(json_decode(file_get_contents(__DIR__.'/rules.json'),true));
$count=0;
function check($value,$message) {global $count;if(!$value)throw new RuntimeException($message);$count++;}
foreach(array(
 'https://mc.yandex.ru/metrika/tag.js'=>'analytics',
 '//www.googletagmanager.com/gtag/js?id=G-TEST'=>'analytics',
 'https://www.googletagmanager.com/gtm.js?id=GTM-TEST'=>'analytics',
 'https://www.clarity.ms/tag/test'=>'analytics',
 'https://connect.facebook.net/en_US/fbevents.js'=>'marketing',
 'https://mc.yandex.ru.evil.test/metrika/tag.js'=>null,
 'https://evil.test/?url=https://mc.yandex.ru/metrika/tag.js'=>null,
 'https://mc.yandex.ru@evil.test/metrika/tag.js'=>null,
 'https://cdn.example.test/jquery.js'=>null,
 '/?fw_asset=analytics&amp;fw_label=head'=>'analytics',
 '/?fw_asset=unknown'=>null
) as $url=>$expected)check($parser->url_category($url)===$expected,'URL '.$url);
$original='<script SRC=//www.googletagmanager.com/gtm.js async defer nonce="probe" data-custom="a&gt;b" id="sdk" class="fixture" integrity="sha384-test" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
$blocked=$parser->rewrite($original);
check(strpos($blocked,'data-itd-poc-src=//www.googletagmanager.com/gtm.js')!==false,'unquoted source blocked');
foreach(array('async','defer','nonce="probe"','data-custom="a&gt;b"','id="sdk"','class="fixture"','integrity="sha384-test"','crossorigin="anonymous"','referrerpolicy="no-referrer"') as $attribute)check(strpos($blocked,$attribute)!==false,'preserve '.$attribute);
check($parser->rewrite($blocked)===$blocked,'idempotent rewrite');
foreach(array(
 '<!-- <script src="https://mc.yandex.ru/metrika/tag.js"></script> -->',
 '<textarea><script src="https://mc.yandex.ru/metrika/tag.js"></script></textarea>',
 '<template><div><script src="https://mc.yandex.ru/metrika/tag.js"></script></div></template>',
 '<script type="application/ld+json">{"tracker":"https://mc.yandex.ru/metrika/tag.js"}</script>',
 '<script type="application/ld+json">{"literal":"<!-- <script src=example>"}</script>',
 '<script type="importmap">{"imports":{}}</script>',
 '<script type="text/template">data only</script>',
 '<script>window.menuWorks=true;</script>',
 '<form><input value="&quot;Привет&amp;мир"></form><svg><circle/></svg>'
) as $html)check($parser->rewrite($html)===$html,'unknown/non-JS/structure preserved');
check(strpos($parser->rewrite('<script type="module" data-itd-cookies-category="analytics">import "/local.js";</script>'),'data-itd-poc-type="module"')!==false,'module type retained');
check(strpos($parser->rewrite('<script data-itd-cookies-category="marketing">window.pixel=1;</script>'),'application/x-itd-poc-blocked')!==false,'explicit inline category');
foreach(array('<script src="https://mc.yandex.ru/metrika/tag.js"', '<script src="x" src="https://mc.yandex.ru/metrika/tag.js"></script>', '<script><!-- <script>escaped</script> --></script>', '<template><script src="https://mc.yandex.ru/metrika/tag.js"></script>', str_repeat('x',2*1024*1024+1),"\xFF<html></html>") as $html) {
 check($parser->rewrite($html)===$html,'unsupported input byte-preserved');check(!empty($parser->diagnostics),'explicit bypass diagnostic');
}
check($parser->category(array(),'const s=document.createElement("script");s.src="https://www.clarity.ms/tag/test";')==='analytics','known inline signature');
check($parser->category(array(),'const note="clarity and analytics";')===null,'ordinary inline unknown');
$hint='<link rel="preload" as="script" href="https://mc.yandex.ru/metrika/tag.js">';
check($parser->rewrite($hint)===$hint,'hints experimental opt-in');
check(strpos($parser->rewrite($hint,true),'data-itd-poc-href=')!==false,'known script preload removed');
$noscript='<noscript><img src="https://mc.yandex.ru/watch/123"></noscript>';
check($parser->rewrite($noscript)===$noscript,'noscript outside script POC, explicit limitation');
$safe=array('status'=>200,'content_type'=>'text/html; charset=UTF-8');check(ITD_FW_POC_Parser::eligible($safe),'HTML response accepted');
foreach(array('admin','rest','ajax','cron','cli','feed','xml','sitemap','robots','download','stream','gzip','redirect') as $flag)check(!ITD_FW_POC_Parser::eligible(array_merge($safe,array($flag=>true))),'bypass '.$flag);
foreach(array('', 'application/json','application/xml','text/html; charset=iso-8859-1') as $type)check(!ITD_FW_POC_Parser::eligible(array_merge($safe,array('content_type'=>$type))),'bypass MIME '.$type);
foreach(array(302,404,500) as $status)check(!ITD_FW_POC_Parser::eligible(array_merge($safe,array('status'=>$status))),'bypass status '.$status);
echo json_encode(array('pass'=>true,'assertions'=>$count,'php'=>PHP_VERSION),JSON_PRETTY_PRINT)."\n";
