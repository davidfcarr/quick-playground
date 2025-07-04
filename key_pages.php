<?php
/**
 * Finds key pages by scraping the home page for internal links.
 *
 * @return array Array of key page slugs.
 */
function quickplayground_find_key_pages() {
    $siteurl = rtrim(get_option('siteurl'),'/');
    $response = wp_remote_get($siteurl);
    if(is_wp_error($response)) {
        echo $output .=  '<p>Error: '.esc_html($response->get_error_message()).'</p>';
        error_log('Error find key pages data: '.$response->get_error_message());
        return;
    }
    $home_html = $response['body'];
    $parse = parse_url($siteurl);
    $domain = $parse['host'];
    $pattern = '/<a[^>]+href\s*=\s*(?:["\'](?<url>[^"\']*)["\'])/';
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

/**
 * Outputs checkboxes for each key page found, for use in a form.
 */
function quickplayground_key_pages_checkboxes() {
    $keypages = quickplayground_find_key_pages();
    $done = [];
    foreach($keypages as $slug) {
        if(in_array($slug,$done))
            continue;
        $done[] = $slug;
        $page = get_page_by_path($slug, OBJECT,['page', 'post']);
        if($page)
        printf('<p><input type="checkbox" value="%d"> %s %s %d</p>',intval($page->ID),esc_html($page->post_title),esc_html($page->post_status),intval($page->ID));
    }
}

/**
 * Retrieves WP_Post objects for each key page found.
 *
 * @return array Array of WP_Post objects for key pages.
 */
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