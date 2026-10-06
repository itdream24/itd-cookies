<?php
/**
 * Plugin Name: ITD Cookies Firewall POC (LOCAL RESEARCH ONLY)
 * Description: Isolated audit experiment. Never a production dependency.
 * Version: 0.0.1
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH') || !in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('itd-cookies.local'),true)) return;
require_once __DIR__.'/parser.php';
function itd_fw_poc_mode() {
    $mode=isset($_GET['fw_strategy'])?$_GET['fw_strategy']:'D';
    return is_string($mode)&&in_array($mode,array('A','B','C','D','observer','off'),true)?$mode:'off';
}
function itd_fw_poc_scope() { return isset($_GET['fw_case']) && is_string($_GET['fw_case']) && preg_match('/^[a-z-]{1,30}$/',$_GET['fw_case']); }
function itd_fw_poc_parser() { return new ITD_FW_POC_Parser(json_decode(file_get_contents(__DIR__.'/rules.json'),true)); }
function itd_fw_poc_controller() {
    $settings=ITD_Cookies_Settings::get();
    $config=array('mode'=>itd_fw_poc_mode(),'version'=>$settings['consent_version'],'enabled'=>(bool)$settings['enabled'],'rules'=>json_decode(file_get_contents(__DIR__.'/rules.json'),true));
    return '<script id="itd-fw-poc-controller" src="'.esc_url(plugins_url('controller.js',__FILE__)).'" data-config="'.esc_attr(wp_json_encode($config)).'"></script>';
}
add_action('wp_head',function() {
    if (itd_fw_poc_scope() && in_array(itd_fw_poc_mode(),array('A','C','observer'),true)) echo itd_fw_poc_controller();
},-10000);
add_filter('script_loader_tag',function($tag,$handle) {
    if (!itd_fw_poc_scope() || !in_array(itd_fw_poc_mode(),array('A','D'),true)) return $tag;
    // Proposed API name is intentionally local to this POC, not final.
    $handles=apply_filters('itd_fw_poc_handles',array());
    if (isset($handles[$handle]) && in_array($handles[$handle],array('analytics','marketing'),true)) $tag=str_replace('<script ','<script data-itd-cookies-category="'.$handles[$handle].'" ',$tag);
    return itd_fw_poc_parser()->rewrite($tag);
},1000,2);
add_action('template_redirect',function() {
    if (!itd_fw_poc_scope() || !in_array(itd_fw_poc_mode(),array('B','D'),true) || is_admin() || is_feed() || (defined('REST_REQUEST')&&REST_REQUEST) || (defined('DOING_AJAX')&&DOING_AJAX) || (defined('DOING_CRON')&&DOING_CRON) || (defined('WP_CLI')&&WP_CLI)) return;
    $flushed=false;
    ob_start(function($html,$phase) use (&$flushed) {
        if (($phase & PHP_OUTPUT_HANDLER_FLUSH) || !($phase & PHP_OUTPUT_HANDLER_FINAL)) { $flushed=true; return $html; }
        $context=array('status'=>http_response_code(),'content_type'=>'','stream'=>$flushed);
        foreach(headers_list() as $header) {
            if (stripos($header,'Content-Type:')===0) $context['content_type']=trim(substr($header,13));
            if (stripos($header,'Content-Encoding:')===0) $context['gzip']=true;
            if (stripos($header,'Content-Disposition:')===0) $context['download']=true;
            if (stripos($header,'Location:')===0) $context['redirect']=true;
        }
        if (!ITD_FW_POC_Parser::eligible($context)) return $html;
        $parser=itd_fw_poc_parser(); $rewritten=$parser->rewrite($html,!empty($_GET['fw_hints']));
        if ($parser->diagnostics) return $html; // Explicitly fail open; a blocker for universal guarantees.
        if (preg_match('/<head(?:\s[^>]*|)>/i',$rewritten,$match,PREG_OFFSET_CAPTURE)) {
            $pos=$match[0][1]+strlen($match[0][0]);
            $rewritten=substr($rewritten,0,$pos).itd_fw_poc_controller().substr($rewritten,$pos);
        } else return $html;
        if ($rewritten!==$html && !headers_sent()) header_remove('Content-Length');
        return $rewritten;
    });
},-100);
