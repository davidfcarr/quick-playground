<?php
function quickplayground_find_key_pages() {
    $siteurl = rtrim(get_option('siteurl'),'/');
    $response = wp_remote_get($siteurl);
    if(is_wp_error($response)) {
        echo $output .=  '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        error_log('Error find key pages data: '.$response->get_error_message());
        return;
    }
    $home_html = $response['body'];
    $parse = parse_url($siteurl);
    $domain = $parse['host'];
    $pattern = '/<a href\s*=\s*(?:["\'](?<url>[^"\']*)["\'])/';
    preg_match_all($pattern, $home_html, $matches);
    $keypages = [];
    foreach($matches[1] as $match) {
        if(!strpos($match,'.php') && strpos($match,$domain) !== false)
        {
        $match = basename($match);
        if(!empty($match) && $match != $domain)
            $keypages[] = $match;
        }
    }
    set_transient('key_pages_html',$home_html); 
    set_transient('key_pages',$keypages); 
    return $keypages;
}
function quickplayground_key_pages_checkboxes() {
    $keypages = quickplayground_find_key_pages();
    $done = [];
    foreach($keypages as $slug) {
        if(in_array($slug,$done))
            continue;
        $done[] = $slug;
        $page = get_page_by_path($slug, OBJECT,['page', 'post']);
        if($page)
        printf('<p><input type="checkbox" value="%d"> %s %s %d</p>',$page->ID,$page->post_title,$page->post_status,$page->ID);
    }
}
function quickplayground_key_pages() {
    $keypages = quickplayground_find_key_pages();
    $kp = [];
    $done = [];
    foreach($keypages as $slug) {
        if(in_array($slug,$done))
            continue;
        $done[] = $slug;
        $page = get_page_by_path($slug, OBJECT,['page', 'post']);
        if($page)
            $kp[] = $page;
    }
return $kp;
}