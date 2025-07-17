<?php
/**
 * Finds key pages by scraping the home page for internal links.
 *
 * @return array Array of key page slugs.
 */
function quickplayground_find_key_pages($profile = 'default') {
    $siteurl = rtrim(get_option('siteurl'),'/');
    $keypages = [];
    $landingpage = get_option('quickplayground_landing_page_'.$profile);
        if($landingpage && !strpos($landingpage,'wp-admin')) {
        $url = site_url($landingpage);
        echo "<p>Trying $url</p>";
        $response = wp_remote_get($url);
        if(is_wp_error($response)) {
            echo $output .=  '<p>Error: '.esc_html($response->get_error_message()).'</p>';
            error_log('Error find key pages data: '.$response->get_error_message());
            return;
        }
        $home_html = $response['body'];
        $parse = parse_url($url);
        $domain = $parse['host'];
        $pattern = '/<a[^>]+href\s*=\s*(?:["\'](?<url>[^"\']*)["\'])/';
        preg_match_all($pattern, $home_html, $matches);
        foreach($matches[1] as $match) {
            if(!strpos($match,'.php') && strpos($match,$domain) !== false)
            {
            $match = basename($match);
            printf('<p>match %s</p>',$match);
            if(!empty($match) && $match != $domain && !in_array($match,$keypages))
                $keypages[] = $match;
            }
        }
    }

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
    foreach($matches[1] as $match) {
        if(!strpos($match,'.php') && strpos($match,$domain) !== false)
        {
        $match = basename($match);
        
        if(!empty($match) && $match != $domain && !in_array($match,$keypages))
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
function quickplayground_key_pages($profile = 'default') {
    $keypages = quickplayground_find_key_pages($profile);
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

function quickplayground_find_key_images() {
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
    $pattern = '/<img[^>]+src\s*=\s*["\'](?<url>[^"\']*uploads\/[^"\']+?)(?:-\d+x\d+)?\.(jpg|jpeg|png|gif|webp)/i';
    //$pattern = '/<img[^>]+src\s*=\s*(?:["\'](?<url>[^"\?\']*uploads[^"\?\'\.]*)\.)/';
    preg_match_all($pattern, $home_html, $matches);
    $keypages = [];
    foreach($matches[1] as $index => $match) {
        $extension = $matches[2][$index];
        $keyimages[] = $match.'.'.$extension;
    }
    set_transient('key_pages_html',$home_html); 
    set_transient('key_images',$keyimages); 
    return $keyimages;
}
