<?php
/** Original, dev-only limited tokenizer experiment. GPL-2.0-or-later. */
final class ITD_FW_POC_Parser {
    public $diagnostics = array();
    private $rules;
    public function __construct(array $rules) { $this->rules = $rules; }
    public function category(array $attrs, $body = '') {
        $type = strtolower(isset($attrs['type']) ? $attrs['type'] : '');
        if (!in_array($type, array('', 'text/javascript', 'application/javascript', 'module'), true)) return null;
        if (isset($attrs['data-itd-cookies-category']) && in_array($attrs['data-itd-cookies-category'], array('analytics','marketing'), true)) return $attrs['data-itd-cookies-category'];
        if (!empty($attrs['src'])) return $this->url_category($attrs['src']);
        // Deliberately narrow: a literal known SDK URL AND an injection/bootstrap.
        // Still heuristic (strings/comments may match), not a JS parser.
        if (strpos($body, 'createElement') !== false) {
            foreach ($this->rules as $rule) if (strpos($body, $rule['host'] . $rule['path']) !== false) return $rule['category'];
        }
        return null;
    }
    public function url_category($url) {
        $parts = parse_url(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) return null;
        $host = strtolower(isset($parts['host']) ? $parts['host'] : '');
        $path = isset($parts['path']) ? $parts['path'] : '';
        if ($host === 'itd-cookies.local' || $host === '') {
            parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
            // Explicit developer rules for SAME-ORIGIN mock endpoints only.
            if ($path === '/' && isset($query['fw_asset']) && is_string($query['fw_asset'])) {
                if (in_array($query['fw_asset'], array('analytics','library','dependent','module','module-import','dynamic','late-src','hint'), true)) return 'analytics';
                if (in_array($query['fw_asset'], array('marketing','pixel'), true)) return 'marketing';
            }
        }
        foreach ($this->rules as $rule) if ($host === $rule['host'] && strpos($path, $rule['path']) === 0) return $rule['category'];
        return null;
    }
    public static function eligible(array $response) {
        foreach (array('admin','rest','ajax','cron','cli','feed','xml','sitemap','robots','download','stream','gzip','redirect') as $flag) if (!empty($response[$flag])) return false;
        $status = isset($response['status']) ? $response['status'] : 0;
        $type = isset($response['content_type']) ? strtolower($response['content_type']) : '';
        return $status >= 200 && $status < 300 && (bool) preg_match('/^text\/html(?:\s*;\s*charset=utf-8)?\s*$/', $type);
    }
    private function end_tag($html, $start) {
        $quote = null; $length = strlen($html);
        for ($i = $start; $i < $length; $i++) {
            $c = $html[$i];
            if ($quote !== null) { if ($c === $quote) $quote = null; }
            elseif ($c === '"' || $c === "'") $quote = $c;
            elseif ($c === '>') return $i + 1;
        }
        return false;
    }
    public function attributes($tag) {
        if (!preg_match('/^<\/?[a-zA-Z][a-zA-Z0-9:-]*/', $tag, $m)) return array();
        $i = strlen($m[0]); $length = strlen($tag); $attrs = array(); $spans = array();
        while ($i < $length) {
            while ($i < $length && ctype_space($tag[$i])) $i++;
            if ($i >= $length || $tag[$i] === '>' || $tag[$i] === '/') break;
            $start = $i;
            while ($i < $length && !ctype_space($tag[$i]) && strpos('=/>', $tag[$i]) === false) $i++;
            if ($i === $start) throw new RuntimeException('ambiguous-attribute');
            $name = strtolower(substr($tag, $start, $i - $start)); $nameEnd = $i;
            while ($i < $length && ctype_space($tag[$i])) $i++;
            $value = '';
            if ($i < $length && $tag[$i] === '=') {
                $i++; while ($i < $length && ctype_space($tag[$i])) $i++;
                if ($i >= $length) throw new RuntimeException('incomplete-attribute');
                $quote = $tag[$i];
                if ($quote === '"' || $quote === "'") {
                    $i++; $v = $i; while ($i < $length && $tag[$i] !== $quote) $i++;
                    if ($i >= $length) throw new RuntimeException('incomplete-attribute');
                    $value = substr($tag, $v, $i - $v); $i++;
                } else { $v = $i; while ($i < $length && !ctype_space($tag[$i]) && $tag[$i] !== '>') $i++; $value = substr($tag, $v, $i - $v); }
            }
            if (array_key_exists($name, $attrs)) throw new RuntimeException('duplicate-attribute');
            $attrs[$name] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $spans[$name] = array($start, $nameEnd - $start);
        }
        return array($attrs, $spans);
    }
    private function block_tag($tag, array $attrs, array $spans, $category) {
        $edits = array();
        foreach (array('src','type') as $name) if (isset($spans[$name])) $edits[] = array($spans[$name][0], $spans[$name][1], 'data-itd-poc-' . $name);
        usort($edits, function($a,$b) { return $b[0] - $a[0]; });
        foreach ($edits as $edit) $tag = substr_replace($tag,$edit[2],$edit[0],$edit[1]);
        return substr($tag,0,-1) . ' type="application/x-itd-poc-blocked" data-itd-poc-category="' . $category . '">';
    }
    public function rewrite($html, $hints = false) {
        $this->diagnostics = array();
        if (strlen($html) > 2 * 1024 * 1024) { $this->diagnostics[] = 'size-cap-bypass'; return $html; }
        if (!preg_match('//u',$html)) { $this->diagnostics[]='encoding-bypass'; return $html; }
        $edits = array(); $position = 0; $templateDepth = 0;
        try {
            while (($start = strpos($html,'<',$position)) !== false) {
                if (substr($html,$start,4) === '<!--') {
                    $end = strpos($html,'-->',$start+4); if ($end === false) throw new RuntimeException('incomplete-comment'); $position=$end+3; continue;
                }
                $end = $this->end_tag($html,$start+1); if ($end === false) throw new RuntimeException('incomplete-tag');
                $tag = substr($html,$start,$end-$start); $position=$end;
                if (!preg_match('/^<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)(?:\s|\/?[>])/', $tag,$m)) continue;
                $closing = $m[1] === '/'; $name=strtolower($m[2]);
                if ($name === 'template') { $templateDepth += $closing ? -1 : 1; if ($templateDepth < 0) throw new RuntimeException('template-context'); continue; }
                if ($closing) continue;
                if (in_array($name,array('script','style','textarea','title','xmp','noembed','noframes','iframe','noscript'),true)) {
                    $close = stripos($html,'</'.$name,$end); if ($close === false) throw new RuntimeException('incomplete-rawtext');
                    if (!preg_match('/^<\/'.preg_quote($name,'/').'(?:\s|>)/i',substr($html,$close))) throw new RuntimeException('rawtext-end-boundary');
                    $position=$this->end_tag($html,$close); if ($position === false) throw new RuntimeException('incomplete-rawtext');
                    if ($name !== 'script' || $templateDepth > 0) continue;
                    $body=substr($html,$end,$close-$end);
                    list($attrs,$spans)=$this->attributes($tag);
                    $type = strtolower(isset($attrs['type']) ? $attrs['type'] : '');
                    if (!in_array($type, array('', 'text/javascript', 'application/javascript', 'module'), true)) continue;
                    if (stripos($body,'<!--')!==false || stripos($body,'<script')!==false) throw new RuntimeException('unsupported-script-escaped-state');
                    if (!empty($attrs['data-itd-poc-category'])) continue;
                    $category=$this->category($attrs,$body);
                    if ($category) $edits[]=array($start,$end-$start,$this->block_tag($tag,$attrs,$spans,$category));
                } elseif ($name==='link' && $hints && $templateDepth===0) {
                    list($attrs,$spans)=$this->attributes($tag);
                    if (isset($attrs['rel'],$attrs['href']) && in_array(strtolower($attrs['rel']),array('preload','modulepreload','preconnect','dns-prefetch'),true) && $this->url_category($attrs['href'])) {
                        $s=$spans['href']; $edits[]=array($start,$end-$start,substr_replace($tag,'data-itd-poc-href',$s[0],$s[1]));
                    }
                }
            }
            if ($templateDepth!==0) throw new RuntimeException('template-context');
        } catch (RuntimeException $e) { $this->diagnostics[]=$e->getMessage(); return $html; }
        // Byte edits only; no DOM serialization or inline code modification.
        for ($i=count($edits)-1;$i>=0;$i--) $html=substr_replace($html,$edits[$i][2],$edits[$i][0],$edits[$i][1]);
        return $html;
    }
}
