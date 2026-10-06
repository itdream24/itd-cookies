<?php
/**
 * Plugin Name: ITD Cookies Firewall Fixtures (LOCAL RESEARCH ONLY)
 * Version: 0.0.1
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH') || wp_parse_url(home_url(),PHP_URL_HOST)!=='itd-cookies.local') return;
function itd_fw_fixture_case() {
    $case=isset($_GET['fw_case'])?$_GET['fw_case']:'';
    $allowed=array('full','enqueue','head','footer','inline','dynamic','attributes','dependency','unannotated','module','hints','pixel','append-bypass','late-src','observer','response','csp');
    return is_string($case)&&in_array($case,$allowed,true)?$case:'';
}
function itd_fw_fixture_doc() { static $id; if (!$id) $id=str_replace('-','',wp_generate_uuid4()); return $id; }
function itd_fw_fixture_url($asset,$label) { return add_query_arg(array('fw_asset'=>$asset,'fw_label'=>$label,'fw_doc'=>itd_fw_fixture_doc()),home_url('/')); }
function itd_fw_fixture_body($asset,$label,$doc) {
    $name=wp_json_encode($label); $mark='window.FWFixture.executions['.$name.']=(window.FWFixture.executions['.$name.']||0)+1;';
    if ($asset==='library') return 'window.FWLibrary={ready:true,init:function(){window.FWFixture.order.push("inline-B");}};window.FWFixture.order.push("A");'.$mark;
    if ($asset==='dependent') return 'if(!window.FWLibrary||!window.FWLibrary.ready)throw Error("B-before-A");window.FWFixture.order.push("B");'.$mark;
    if ($asset==='module-import') return 'export const value=42;'.$mark;
    if ($asset==='module') return 'import {value} from '.wp_json_encode(add_query_arg(array('fw_asset'=>'module-import','fw_label'=>$label.'-import','fw_doc'=>$doc),home_url('/'))).';if(value!==42)throw Error("module-import");'.$mark;
    if ($asset==='application') return file_get_contents(__DIR__.'/application.js');
    return $mark;
}
add_action('template_redirect',function() {
    if (isset($_GET['fw_cleanup']) && $_GET['fw_cleanup']==='clear') {
        foreach(array('itd_cookies_consent','itd_modubricks_consent','itd_cookies_legacy_migrated') as $name) setcookie($name,'',array('expires'=>time()-3600,'path'=>'/','samesite'=>'Lax'));
        wp_safe_redirect(add_query_arg('fw_cleanup','verify',home_url('/')));exit;
    }
    if (isset($_GET['fw_cleanup']) && $_GET['fw_cleanup']==='verify') {
        header('Content-Type: text/html; charset=UTF-8');header('Cache-Control: no-store');
        $presence=array();foreach(array('itd_cookies_consent','itd_modubricks_consent','itd_cookies_legacy_migrated') as $name) $presence[$name]=isset($_COOKIE[$name]);
        echo '<!doctype html><html><head><title>Local consent cookie cleanup</title></head><body><pre id="fw-cookie-cleanup">'.esc_html(wp_json_encode(array('localConsentCookiePresence'=>$presence))).'</pre></body></html>';exit;
    }
    if (isset($_GET['fw_stats']) && is_string($_GET['fw_stats']) && preg_match('/^[a-f0-9]{32}$/',$_GET['fw_stats'])) {
        global $wpdb;
        $prefix='itd_fw_fixture_hits_'.$_GET['fw_stats'].'_';
        $counts=array();
        foreach($wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",$wpdb->esc_like($prefix).'%')) as $row) $counts[substr($row->option_name,strlen($prefix))]=(int)$row->option_value;
        header('Content-Type: application/json'); header('Cache-Control: no-store');
        echo wp_json_encode($counts); exit;
    }
    if (!isset($_GET['fw_asset'],$_GET['fw_label'],$_GET['fw_doc']) || !is_string($_GET['fw_asset']) || !is_string($_GET['fw_label']) || !is_string($_GET['fw_doc'])) return;
    $asset=$_GET['fw_asset']; $label=$_GET['fw_label']; $doc=$_GET['fw_doc'];
    if (!in_array($asset,array('analytics','marketing','library','dependent','module','module-import','dynamic','late-src','hint','pixel','application','unknown'),true) || !preg_match('/^[a-z-]{1,40}$/',$label) || !preg_match('/^[a-f0-9]{32}$/',$doc)) { status_header(400);exit; }
    global $wpdb;
    // One atomic counter per document/label: parallel mock loads cannot lose updates.
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,'1','no') ON DUPLICATE KEY UPDATE option_value=CAST(option_value AS UNSIGNED)+1",'itd_fw_fixture_hits_'.$doc.'_'.$label));
    header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
    if ($asset==='pixel') { header('Content-Type: image/gif');echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='); }
    else { header('Content-Type: application/javascript; charset=UTF-8');echo itd_fw_fixture_body($asset,$label,$doc); }
    exit;
},-1000);
function itd_fw_fixture_tag($asset,$label,$attributes='') { return '<script id="fw-'.$label.'" src="'.esc_url(itd_fw_fixture_url($asset,$label)).'" '.$attributes.'></script>'; }
add_filter('itd_fw_poc_handles',function($handles) {$handles['fw-enqueued']='analytics';return $handles;});
add_filter('itd_cookies_services',function($services) {
    if (!itd_fw_fixture_case())return $services;
    foreach(array('analytics','marketing') as $category)$services[]=array('id'=>'fw-'.$category,'name'=>'Local '.$category.' mock','category'=>$category,'enabled'=>true,'description'=>'Local network fixture; no vendor collection.','provider_type'=>'external');
    return $services;
});
add_action('wp_enqueue_scripts',function() {
    $case=itd_fw_fixture_case(); if (!$case || $case==='response' || $case==='csp')return;
    if (in_array($case,array('full','enqueue'),true))wp_enqueue_script('fw-enqueued',itd_fw_fixture_url('analytics','enqueue'),array(),null,false);
    wp_enqueue_script('fw-application',itd_fw_fixture_url('application','application'),array(),null,true);
    wp_enqueue_script('fw-unknown',itd_fw_fixture_url('unknown','unknown'),array(),null,true);
});
add_action('wp_head',function() {
    $case=itd_fw_fixture_case(); if (!$case || $case==='response' || $case==='csp')return;
    echo '<script id="fw-instrumentation">window.FWFixture={executions:{},order:[],errors:[]};window.addEventListener("error",function(e){window.FWFixture.errors.push(e.message);});</script>';
},-20000);
add_action('wp_head',function() {
    $case=itd_fw_fixture_case();
    if (in_array($case,array('full','head'),true))echo itd_fw_fixture_tag('analytics','head');
    if ($case==='hints')echo '<link rel="preload" as="script" href="'.esc_url(itd_fw_fixture_url('hint','hint')).'">';
    if (in_array($case,array('full','attributes'),true)) {
        $sri='sha384-'.base64_encode(hash('sha384',itd_fw_fixture_body('analytics','attributes',itd_fw_fixture_doc()),true));
        echo itd_fw_fixture_tag('analytics','attributes','async defer crossorigin="anonymous" integrity="'.$sri.'" referrerpolicy="no-referrer" nonce="fw-nonce" data-custom="retained" class="fixture-class"');
        echo itd_fw_fixture_tag('analytics','nomodule','nomodule');
    }
},20);
add_action('wp_footer',function() {
    $case=itd_fw_fixture_case(); if (!$case || $case==='response' || $case==='csp')return;
    if(in_array($case,array('full','footer'),true))echo itd_fw_fixture_tag('analytics','footer');
    if(in_array($case,array('full','dependency','unannotated'),true)) {
        echo itd_fw_fixture_tag('library','library').itd_fw_fixture_tag('dependent','dependent');
        $annotation=$case==='unannotated'?'':' data-itd-cookies-category="analytics"';
        echo '<script id="fw-inline-dependency"'.$annotation.'>window.FWLibrary.init();</script>';
    }
    if(in_array($case,array('full','inline'),true)) {
        // Three independently classified patterns; all use local SDK mocks.
        foreach(array('clarity-style'=>'analytics','gtm-style'=>'analytics','meta-style'=>'marketing') as $label=>$category) {
            echo '<script id="fw-'.$label.'" data-itd-cookies-category="'.$category.'">(function(w,d){var s=d.createElement("script");s.src='.wp_json_encode(itd_fw_fixture_url($category,$label)).';s.async=true;d.head.insertBefore(s,d.head.firstChild);})(window,document);</script>';
        }
    }
    if(in_array($case,array('full','module'),true)) {
        echo itd_fw_fixture_tag('module','module','type="module"');
        echo '<script id="fw-inline-module" type="module" data-itd-cookies-category="analytics">import {value} from '.wp_json_encode(itd_fw_fixture_url('module-import','inline-import')).';window.FWFixture.executions["inline-module"]=value===42?1:0;</script>';
    }
    if($case==='pixel') echo '<img alt="Local test pixel" src="'.esc_url(itd_fw_fixture_url('pixel','pixel')).'"><noscript><img alt="noscript pixel" src="'.esc_url(itd_fw_fixture_url('pixel','noscript-pixel')).'"></noscript>';
},5);
add_shortcode('itd_firewall_fixture',function() {
    if (!itd_fw_fixture_case()) return '';
    $config=array('case'=>itd_fw_fixture_case(),'doc'=>itd_fw_fixture_doc(),'dynamic'=>itd_fw_fixture_url('dynamic','dynamic'),'lateSrc'=>itd_fw_fixture_url('late-src','late-src'));
    return '<section id="fw-probe" data-config="'.esc_attr(wp_json_encode($config)).'"><h2>External firewall LOCAL research</h2><p>Mock requests only; ITD Cookies 0.3.0 unchanged.</p><button id="fw-clear">Clear local test consent</button><button id="fw-refresh">Refresh evidence</button><button id="fw-menu">Toggle menu</button><div id="fw-menu-result" hidden>Menu works</div><form id="fw-form"><label>Fixture input<input id="fw-input" required></label><button>Validate fixture form</button><output id="fw-form-result"></output></form><p id="fw-ready">Preparing fixtures</p><pre id="fw-evidence">{}</pre><div id="fw-dom"><!-- script-looking comment <script src="https://mc.yandex.ru/metrika/tag.js"></script> --><svg viewBox="0 0 1 1"><title>UTF-8 Привет &amp; мир</title><circle cx="0" cy="0" r="1"/></svg><p data-entity="&quot;">Согласие &amp; cookie &#169;</p><textarea>&lt;script src="https://mc.yandex.ru/metrika/tag.js"&gt;</textarea><template id="fw-template"><span>Template</span><script src="https://mc.yandex.ru/metrika/tag.js"></script></template><script type="application/ld+json">{"name":"Привет","url":"https://mc.yandex.ru/metrika/tag.js"}</script><script type="importmap">{"imports":{}}</script><script type="text/template">&lt;script&gt;data only&lt;/script&gt;</script></div></section>'.do_shortcode('[itd_cookies_settings]');
});
add_action('template_redirect',function() {
    if(itd_fw_fixture_case()!=='response'&&itd_fw_fixture_case()!=='csp')return;
    $kind=isset($_GET['fw_response'])&&is_string($_GET['fw_response'])?$_GET['fw_response']:'html';
    $body='<html><head></head><body>'.itd_fw_fixture_tag('analytics','response').'</body></html>';
    header('Cache-Control: no-store');
    if (itd_fw_fixture_case()==='csp') {
        $nonce=$kind==='nonce'; $inline='window.FWFixture.executions["csp-inline"]=1;';
        header("Content-Security-Policy: script-src 'self' ".($nonce?"'nonce-fw-nonce'":"'sha256-".base64_encode(hash('sha256',$inline,true))."'"));
        header('Content-Type: text/html; charset=UTF-8');
        echo '<html><head></head><body><section id="fw-probe" data-config="'.esc_attr(wp_json_encode(array('case'=>'csp','doc'=>itd_fw_fixture_doc()))).'"><h1>CSP '.$kind.' replay probe</h1><button id="fw-grant">Grant analytics (CSP probe)</button><button id="fw-refresh">Refresh evidence</button><p id="fw-ready">Preparing fixtures</p><pre id="fw-evidence">{}</pre></section>'.itd_fw_fixture_tag('analytics','csp-external','nonce="fw-nonce"').'<script id="fw-csp-inline" data-itd-cookies-category="analytics" '.($nonce?'nonce="fw-nonce"':'').'>'.$inline.'</script>'.itd_fw_fixture_tag('application','application').'</body></html>';exit;
    }
    if($kind==='json'){header('Content-Type: application/json');echo wp_json_encode(array('literal'=>$body));exit;}
    if($kind==='xml'){header('Content-Type: application/xml');echo '<root><![CDATA['.$body.']]></root>';exit;}
    if($kind==='download'){header('Content-Type: text/html; charset=UTF-8');header('Content-Disposition: attachment; filename="fixture.html"');echo $body;exit;}
    if($kind==='redirect'){status_header(302);header('Location: '.home_url('/'));echo $body;exit;}
    if($kind==='404'||$kind==='500')status_header((int)$kind);
    header('Content-Type: text/html; charset=UTF-8');
    if($kind==='gzip'){header('Content-Encoding: gzip');echo gzencode($body);exit;}
    if($kind==='length')header('Content-Length: '.strlen($body));
    if($kind==='stream'){echo substr($body,0,strlen($body)-14);ob_flush();flush();echo substr($body,-14);exit;}
    echo $body;exit;
},-50);
