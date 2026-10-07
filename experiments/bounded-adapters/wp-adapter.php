<?php
/** Original GPL-2.0-or-later. Dev-only adapter for closed classic WP groups. */
require_once __DIR__ . '/../firewall-poc/parser.php';
final class ITD_Bounded_WP_Adapter {
    public $groups;
    public $diagnostics = array();
    private $owners = array();
    private $captured = array();
    private $prepared = false;
    public function __construct(array $groups, array $native = array()) {
        $this->groups = $groups;
        foreach ($this->groups as $index => &$group) {
            $group['nodes'] = array();
            if (!isset($group['resources']) || $group['resources'] !== array()) $group['issue'] = 'UNSUPPORTED_ANCILLARY_RESOURCES';
            if (!empty($group['provider']) && in_array($group['provider'], $native, true)) $group['issue'] = 'FAILED_OWNERSHIP_CONFLICT';
            foreach ($group['handles'] as $handle) {
                if (isset($this->owners[$handle])) {
                    $group['issue'] = 'FAILED_DUPLICATE_OWNER';
                    $this->groups[$this->owners[$handle]]['issue'] = 'FAILED_DUPLICATE_OWNER';
                }
                $this->owners[$handle] = $index;
            }
        }
        unset($group);
    }
    public function attach() {
        add_action('wp_print_scripts', array($this, 'prepare'), 0);
        add_action('wp_print_footer_scripts', array($this, 'prepare'), 0);
        add_filter('script_loader_tag', array($this, 'tag'), PHP_INT_MAX, 3);
    }
    private function inline_tag($scripts, $handle, $phase) {
        if ($phase !== 'data' && method_exists($scripts, 'get_inline_script_tag')) return $scripts->get_inline_script_tag($handle, $phase);
        ob_start();
        if ($phase === 'data') $scripts->print_extra_script($handle);
        else $scripts->print_inline_script($handle, $phase);
        return ob_get_clean();
    }
    public function prepare() {
        if ($this->prepared) return;
        $this->prepared = true;
        $scripts = wp_scripts();
        foreach ($this->groups as &$group) {
            // An unsupported contract is reported, never counted as zero-request coverage.
            foreach ($group['handles'] as $handle) {
                if (!isset($scripts->registered[$handle])) { $group['issue'] = 'MISSING_HANDLE'; break; }
                $item = $scripts->registered[$handle];
                if (!$item->src || !empty($item->textdomain) || !empty($item->extra['conditional']) || $scripts->do_concat || in_array($handle, $scripts->done, true)) { $group['issue'] = 'UNSUPPORTED_HANDLE'; break; }
                foreach ($item->deps as $dependency) if (!in_array($dependency, $group['handles'], true)) { $group['issue'] = 'MISSING_DEPENDENCY'; break 2; }
            }
            $visiting = array(); $done = array();
            $visit = function($handle) use (&$visit, &$visiting, &$done, &$group, $scripts) {
                if (isset($visiting[$handle])) { $group['issue'] = 'CYCLE'; return; }
                if (isset($done[$handle]) || !isset($scripts->registered[$handle])) return;
                $visiting[$handle] = true;
                foreach ($scripts->registered[$handle]->deps as $dependency) $visit($dependency);
                unset($visiting[$handle]); $done[$handle] = true;
            };
            foreach ($group['handles'] as $handle) $visit($handle);
            // Even an invalid declared group has its owned classic inline code withheld.
            foreach ($group['handles'] as $handle) {
                if (!isset($scripts->registered[$handle])) continue;
                foreach (array('data', 'before', 'after') as $phase) {
                    $this->captured[$handle][$phase] = $this->inline_tag($scripts, $handle, $phase);
                    unset($scripts->registered[$handle]->extra[$phase]);
                }
            }
        }
        unset($group);
    }
    public function block($tag, $id) {
        // Only one explicitly owned, classic native WP tag. Inline body stays byte-identical.
        if (!preg_match('/^\s*(<script\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)([\s\S]*)(<\/script>)\s*$/i', $tag, $match)) throw new RuntimeException('UNSUPPORTED_OWNED_TAG');
        $parser = new ITD_FW_POC_Parser(array());
        list($attributes, $spans) = $parser->attributes($match[1]);
        if (isset($attributes['type']) && !in_array(strtolower($attributes['type']), array('', 'text/javascript', 'application/javascript'), true)) throw new RuntimeException('UNSUPPORTED_SCRIPT_TYPE');
        $opening = $match[1]; $edits = array();
        foreach (array('type', 'src') as $name) if (isset($spans[$name])) $edits[] = array($spans[$name][0], $spans[$name][1], 'data-itd-bounded-' . $name);
        usort($edits, function($a, $b) { return $b[0] - $a[0]; });
        foreach ($edits as $edit) $opening = substr_replace($opening, $edit[2], $edit[0], $edit[1]);
        $opening = substr($opening, 0, -1) . ' type="application/x-itd-bounded" data-itd-bounded-node="' . esc_attr($id) . '">';
        return $opening . $match[2] . $match[3] . "\n";
    }
    public function tag($tag, $handle, $src = '') {
        if (!isset($this->owners[$handle])) return $tag;
        $index = $this->owners[$handle]; $group =& $this->groups[$index];
        if (!isset($this->captured[$handle])) return $tag; // Explicit BYPASSED diagnostics for unsupported ownership contracts.
        if (!empty($group['issue'])) return '';
        $scripts = wp_scripts(); $dependencies = array();
        foreach ($scripts->registered[$handle]->deps as $dependency) $dependencies[] = $dependency . ':end';
        $output = ''; $last = null;
        try {
            foreach (array('data', 'before', 'main', 'after') as $phase) {
                $native = $phase === 'main' ? $tag : $this->captured[$handle][$phase];
                if (!$native) continue;
                $id = $handle . ':' . $phase;
                $output .= $this->block($native, $id);
                $group['nodes'][] = array('id' => $id, 'deps' => $last ? array($last) : $dependencies);
                $last = $id;
            }
            $group['nodes'][] = array('id' => $handle . ':end', 'deps' => $last ? array($last) : $dependencies, 'virtual' => true);
        } catch (RuntimeException $error) { $group['issue'] = $error->getMessage(); $this->diagnostics[] = $handle . ':' . $error->getMessage(); return ''; }
        return $output;
    }
    public function manifest() {
        $output = array();
        foreach ($this->groups as $group) { unset($group['handles'], $group['provider']); $output[] = $group; }
        return $output;
    }
}
