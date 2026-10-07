<?php
/** Original GPL-2.0-or-later bounded comparison. Original download URL is never changed. */
final class ITD_Bounded_URL {
    public static function category($url, $base, array $rules) {
        if (!is_string($url) || preg_match('/[\x00-\x20\x7f\\\\]|%(?![a-f0-9]{2})/i', $url)) return null;
        if (preg_match('~^(?:https?:)?//([^/?#]*)~i', $url, $authority) && preg_match('/%|[^\x00-\x7f]/', $authority[1])) return null;
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) && !preg_match('~^https?://~i', $url)) return null;
        $parent = parse_url($base); $parts = parse_url($url);
        if (!$parent || $parts === false) return null;
        if (isset($parts['user']) || isset($parts['pass'])) return null;
        $scheme = strtolower(isset($parts['scheme']) ? $parts['scheme'] : (isset($parent['scheme']) ? $parent['scheme'] : ''));
        if (!in_array($scheme, array('http','https'), true)) return null;
        $absolute = isset($parts['host']);
        $host = strtolower($absolute ? $parts['host'] : (isset($parent['host']) ? $parent['host'] : ''));
        $port = isset($parts['port']) ? $parts['port'] : ($absolute ? null : (isset($parent['port']) ? $parent['port'] : null));
        if ($port !== null && $port !== ($scheme==='https' ? 443 : 80)) return null;
        $path = isset($parts['path']) ? $parts['path'] : '';
        if (!$absolute && ($path==='' || $path[0]!=='/')) {
            $parentPath=isset($parent['path']) ? $parent['path'] : '/';
            $path=$path==='' ? $parentPath : substr($parentPath,0,strrpos($parentPath,'/')+1).$path;
        }
        $segments=array();
        $inputSegments = explode('/', $path);
        foreach($inputSegments as $index=>$segment) {
            if ($index===0 && $segment==='') continue;
            $dot=preg_replace('/%2e/i','.',$segment);
            if ($dot==='..') array_pop($segments);
            elseif ($dot!=='.') $segments[]=$segment;
            if (($dot==='.' || $dot==='..') && $index===count($inputSegments)-1) $segments[]='';
        }
        $path='/'.implode('/',$segments);
        foreach($rules as $rule) {
            $prefix=substr($rule['path'],-1)==='/';
            if ($host===$rule['host'] && ($prefix ? strpos($path,$rule['path'])===0 : $path===$rule['path'])) return $rule['category'];
        }
        return null;
    }
}
