<?php
/**
 * Plugin Name: ITD Cookies Bounded Adapter Fixtures (LOCAL RESEARCH ONLY)
 * Version: 0.0.1
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH') || wp_parse_url(home_url(),PHP_URL_HOST)!=='itd-cookies.local') return;
require_once __DIR__.'/../bounded-adapters/wp-adapter.php';
function itd_r2_case() { $case=isset($_GET['r2_case'])&&is_string($_GET['r2_case'])?$_GET['r2_case']:'';return in_array($case,array('supported','error','timeout','missing','cycle','ownership','csp-nonce','csp-hash'),true)?$case:''; }
function itd_r2_doc() { static $id; if(!$id)$id=str_replace('-','',wp_generate_uuid4());return $id; }
function itd_r2_url($asset) { return add_query_arg(array('r2_asset'=>$asset,'r2_doc'=>itd_r2_doc()),home_url('/')); }
function itd_r2_group($id,$category,$handles) {return array('id'=>$id,'category'=>$category,'handles'=>$handles,'resources'=>array());}
function itd_r2_setup() {
    $case=itd_r2_case(); if(!$case)return;
    $bad=$case==='error'?'error':($case==='timeout'?'slow':'library');
    wp_register_script('r2-library',itd_r2_url($bad),$case==='missing'?array('r2-absent'):($case==='cycle'?array('r2-dependent'):array()),null,false);
    wp_register_script('r2-dependent',itd_r2_url('dependent'),array('r2-library'),null,true);
    wp_register_script('r2-marketing',itd_r2_url('marketing'),array(),null,true);
    wp_enqueue_script('r2-dependent'); wp_enqueue_script('r2-marketing');
    wp_localize_script('r2-library','R2OwnedData',array('value'=>42));
    wp_add_inline_script('r2-library','window.R2Fixture.order.push("before-A");','before');
    wp_add_inline_script('r2-library','window.R2Fixture.order.push("after-A");','after');
    wp_add_inline_script('r2-dependent','window.R2Fixture.order.push("before-B");','before');
    wp_add_inline_script('r2-dependent','window.R2Library.init();','after');
    wp_add_inline_script('r2-marketing','window.R2Fixture.order.push("marketing-init");window.R2Fixture.inits.marketing++;','after');
    $groups=array(itd_r2_group('analytics-group','analytics',array('r2-library','r2-dependent')),itd_r2_group('marketing-group','marketing',array('r2-marketing')));
    if($case==='ownership')$groups[0]['provider']='ga4';
    // Real native inventory is read only; synthetic ownership adds no real provider.
    $native=array();foreach(ITD_Cookies_Services::providers(ITD_Cookies_Settings::get()) as $provider)$native[]=$provider['type'];
    if($case==='ownership')$native[]='ga4';
    $GLOBALS['itd_r2_adapter']=new ITD_Bounded_WP_Adapter($groups,$native);$GLOBALS['itd_r2_adapter']->attach();
    wp_enqueue_script('r2-application',itd_r2_url('application'),array(),null,true);
}
add_action('wp_enqueue_scripts','itd_r2_setup',20);
add_filter('itd_cookies_services',function($services){if(itd_r2_case())foreach(array('analytics','marketing') as $category)$services[]=array('id'=>'r2-'.$category,'name'=>'R2 '.$category.' local mock','category'=>$category,'enabled'=>true,'description'=>'Explicit local WP handle group. No ancillary resources.','provider_type'=>'external');return $services;});
add_action('wp_head',function(){if(itd_r2_case())echo '<script>window.R2Fixture={order:[],inits:{analytics:0,marketing:0},errors:[]};window.addEventListener("error",function(e){window.R2Fixture.errors.push(e.message);});</script>';},-20000);
function itd_r2_controller() {
    if(empty($GLOBALS['itd_r2_adapter']))return;
    echo '<script id="itd-bounded-manifest" type="application/json">'.wp_json_encode(array('groups'=>$GLOBALS['itd_r2_adapter']->manifest(),'timeoutMs'=>1000),JSON_HEX_TAG|JSON_HEX_AMP).'</script>';
    foreach(array('group-engine.js','controller.js') as $file)echo '<script src="'.esc_url(plugins_url('../bounded-adapters/'.$file,__FILE__)).'"></script>';
}
add_action('wp_footer','itd_r2_controller',1000);
function itd_r2_panel($standalone=false) {
    return '<section id="r2-probe" data-config="'.esc_attr(wp_json_encode(array('doc'=>itd_r2_doc(),'standalone'=>$standalone))).'"><h1>Bounded WP groups R2</h1><p>Local mock SDK + owned data/before/after. Resources: no hints, pixel or noscript.</p><button id="r2-clear">Clear local consent</button><button id="r2-grant">CSP probe grant analytics</button><button id="r2-refresh">Refresh evidence</button><button id="r2-menu">Toggle menu</button><p id="r2-menu-result" hidden>Menu works</p><form id="r2-form"><label>Fixture input<input id="r2-input" required></label><button>Validate fixture form</button><output id="r2-form-result"></output></form><pre id="r2-evidence">{}</pre></section>'.($standalone?'':do_shortcode('[itd_cookies_settings]'));
}
add_shortcode('itd_bounded_fixture',function(){return itd_r2_panel();});
add_action('template_redirect',function(){
    header('Cache-Control: no-store');
    if(isset($_GET['r2_stats'])&&is_string($_GET['r2_stats'])&&preg_match('/^[a-f0-9]{32}$/',$_GET['r2_stats'])){
        global $wpdb;$prefix='itd_r2_hits_'.$_GET['r2_stats'].'_';$counts=array();
        foreach($wpdb->get_results($wpdb->prepare("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s",$wpdb->esc_like($prefix).'%')) as $row)$counts[substr($row->option_name,strlen($prefix))]=(int)$row->option_value;
        header('Content-Type: application/json');echo wp_json_encode($counts);exit;
    }
    if(!isset($_GET['r2_asset'],$_GET['r2_doc'])||!is_string($_GET['r2_asset'])||!is_string($_GET['r2_doc'])||!preg_match('/^[a-f0-9]{32}$/',$_GET['r2_doc']))return;
    $asset=$_GET['r2_asset'];if(!in_array($asset,array('library','dependent','marketing','error','slow','application'),true)){status_header(400);exit;}
    global $wpdb;$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,'1','no') ON DUPLICATE KEY UPDATE option_value=CAST(option_value AS UNSIGNED)+1",'itd_r2_hits_'.$_GET['r2_doc'].'_'.$asset));
    header('Content-Type: application/javascript; charset=UTF-8');header('X-Content-Type-Options: nosniff');
    if($asset==='error'){status_header(404);echo '/* unavailable mock SDK */';exit;}
    if($asset==='slow')sleep(3);
    if($asset==='application')echo file_get_contents(__DIR__.'/application.js');
    elseif($asset==='library'||$asset==='slow')echo 'window.R2Library={init:function(){window.R2Fixture.order.push("inline-B");window.R2Fixture.inits.analytics++;}};window.R2Fixture.order.push("A");';
    elseif($asset==='dependent')echo 'if(!window.R2Library)throw Error("B-before-A");window.R2Fixture.order.push("B");';
    else echo 'window.R2Fixture.order.push("marketing-SDK");';
    exit;
},-1000);
add_action('template_redirect',function(){
    $case=itd_r2_case();if($case!=='csp-nonce'&&$case!=='csp-hash')return;
    itd_r2_setup();$adapter=$GLOBALS['itd_r2_adapter'];
    $nonce=$case==='csp-nonce';
    if($nonce)add_filter('wp_inline_script_attributes',function($attrs){$attrs['nonce']='r2-nonce';return $attrs;});
    $adapter->prepare();ob_start();wp_scripts()->do_items(array('r2-dependent','r2-marketing'));$owned=ob_get_clean();
    $sources="'self' ";
    if($nonce)$sources.="'nonce-r2-nonce'";
    else {preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script>/i',$owned,$bodies);foreach($bodies[1] as $body)if($body!=='')$sources.="'sha256-".base64_encode(hash('sha256',$body,true))."' ";}
    header('Content-Security-Policy: script-src '.$sources);header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Bounded CSP '.$case.'</title></head><body>'.itd_r2_panel(true).'<script src="'.esc_url(itd_r2_url('application')).'"></script>'.$owned;
    itd_r2_controller();echo '</body></html>';exit;
},-50);
