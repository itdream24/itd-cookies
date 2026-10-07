<?php
/** Run only with wp eval-file in a disposable CI site or the dedicated local QA. */
if (!defined('ABSPATH')) exit(1);
require __DIR__.'/wp-adapter.php';
$GLOBALS['checks']=0;
function r2_check($condition, $message) { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
function r2_scripts() { global $wp_scripts; $wp_scripts=new WP_Scripts(); return $wp_scripts; }
function r2_group($handles) { return array('id'=>'test','category'=>'analytics','handles'=>$handles,'resources'=>array()); }
$scripts=r2_scripts();
wp_register_script('r2-library','http://itd-cookies.local/mock/library.js',array(),null,false);
wp_register_script('r2-dependent','http://itd-cookies.local/mock/dependent.js',array('r2-library'),null,true);
wp_enqueue_script('r2-dependent');
wp_localize_script('r2-library','R2Data',array('message'=>'Привет'));
wp_add_inline_script('r2-library','window.order.push("before-A");','before');
wp_add_inline_script('r2-library','window.order.push("after-A");','after');
wp_add_inline_script('r2-dependent','window.order.push("before-B");','before');
wp_add_inline_script('r2-dependent','window.library.init();','after');
$adapter=new ITD_Bounded_WP_Adapter(array(r2_group(array('r2-library','r2-dependent'))));
$adapter->attach(); $adapter->prepare();
ob_start();$scripts->do_head_items();$head=ob_get_clean();
ob_start();$scripts->do_footer_items();$footer=ob_get_clean();
$html=$head.$footer;
r2_check(strpos($head,'r2-library:main')!==false,'head handle missing');
r2_check(strpos($footer,'r2-dependent:main')!==false,'footer handle missing');
r2_check(!preg_match('/<script\b[^>]*\ssrc=/', $html),'SDK left active');
r2_check(substr_count($html,'type="application/x-itd-bounded"')===7,'data/before/main/after capture');
r2_check(strpos($html,'window.library.init();')!==false,'owned inline lost');
r2_check(empty($scripts->registered['r2-library']->extra['data']),'localized code printed early');
$nodes=$adapter->manifest()[0]['nodes'];$byId=array();foreach($nodes as $node)$byId[$node['id']]=$node;
r2_check($byId['r2-dependent:before']['deps']===array('r2-library:end'),'dependency terminal not before B');
r2_check($byId['r2-library:before']['deps']===array('r2-library:data'),'localize order');
r2_check($byId['r2-dependent:after']['deps']===array('r2-dependent:main'),'after init order');
wp_register_script('r2-unknown','http://itd-cookies.local/mock/unknown.js',array(),null);
wp_add_inline_script('r2-unknown','window.unknownMustRemainIndependent=true;');
ob_start();$scripts->do_items(array('r2-unknown'));$unknown=ob_get_clean();
r2_check(strpos($unknown,'src=')!==false && strpos($unknown,'unknownMustRemainIndependent')!==false && strpos($unknown,'application/x-itd-bounded')===false,'unowned code changed');
remove_filter('script_loader_tag',array($adapter,'tag'),PHP_INT_MAX);
foreach(array('MISSING_DEPENDENCY','CYCLE','FAILED_OWNERSHIP_CONFLICT','UNSUPPORTED_ANCILLARY_RESOURCES') as $issue) {
    $scripts=r2_scripts();
    $deps=$issue==='MISSING_DEPENDENCY'?array('absent'):($issue==='CYCLE'?array('r2-b'):array());
    wp_register_script('r2-a','http://itd-cookies.local/a.js',$deps,null);
    if($issue==='CYCLE')wp_register_script('r2-b','http://itd-cookies.local/b.js',array('r2-a'),null);
    wp_add_inline_script('r2-a','window.shouldNotRun=true;');
    $group=r2_group($issue==='CYCLE'?array('r2-a','r2-b'):array('r2-a'));
    if($issue==='FAILED_OWNERSHIP_CONFLICT')$group['provider']='ga4';
    if($issue==='UNSUPPORTED_ANCILLARY_RESOURCES')$group['resources']=array('pixel','noscript','preload');
    $bad=new ITD_Bounded_WP_Adapter(array($group),array('ga4'));$bad->prepare();
    r2_check($bad->groups[0]['issue']===$issue,$issue.' missing');
    r2_check(empty($scripts->registered['r2-a']->extra['after']),$issue.' inline leaked');
    r2_check($bad->tag('<script src="http://itd-cookies.local/a.js"></script>','r2-a')==='',$issue.' SDK leaked');
}
echo 'Experimental WordPress integration PASS: '.$GLOBALS['checks'].' assertions; WP '.get_bloginfo('version').'; PHP '.PHP_VERSION."\n";
