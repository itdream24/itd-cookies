<?php
require __DIR__.'/parser.php';
$kb=isset($argv[1])?(int)$argv[1]:100;$method=isset($argv[2])?$argv[2]:'tokenizer';$scripts=isset($argv[3])?(int)$argv[3]:20;
$prefix='<html><head><meta charset="utf-8"></head><body>'; $tags=str_repeat('<script src="https://mc.yandex.ru/metrika/tag.js"></script>',$scripts);$suffix='</body></html>';
$html=$prefix.$tags.str_repeat('x',max(0,$kb*1024-strlen($prefix.$tags.$suffix))).$suffix;
$parser=new ITD_FW_POC_Parser(json_decode(file_get_contents(__DIR__.'/rules.json'),true));$times=array();$base=memory_get_usage(true);
for($i=0;$i<15;$i++) {
 $start=microtime(true);
 if($method==='dom') {$dom=new DOMDocument();@$dom->loadHTML($html);foreach($dom->getElementsByTagName('script') as $script){$script->setAttribute('data-src',$script->getAttribute('src'));$script->removeAttribute('src');$script->setAttribute('type','text/plain');}$output=$dom->saveHTML();unset($dom);}
 elseif($method==='regex')$output=preg_replace('/<script\s+src="([^"]*)">/','<script type="text/plain" data-src="$1">',$html);
 else $output=$parser->rewrite($html);
 $times[]=(microtime(true)-$start)*1000;
}
$correct=$method==='tokenizer' ? substr_count($output,'data-itd-poc-category="analytics"')===$scripts && !$parser->diagnostics : true;
sort($times);echo json_encode(array('php'=>PHP_VERSION,'kb'=>$kb,'method'=>$method,'scripts'=>$scripts,'correct'=>$correct,'median_ms'=>round($times[7],3),'max_ms'=>round(max($times),3),'peak_bytes'=>memory_get_peak_usage(true),'peak_delta_bytes'=>memory_get_peak_usage(true)-$base,'output_delta_bytes'=>strlen($output)-strlen($html)),JSON_PRETTY_PRINT)."\n";
