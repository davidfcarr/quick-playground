<?php
/**
 * Initializes the Quick Playground clone process. Called by playground blueprint
 */
function quickplayground_clone_init() {
    if(isset($_GET['clonetest'])) {
        $clone['output'] = quickplayground_clone();
        wp_die(wp_kses_post($clone['output']));
    }
    if(get_option('is_playground_clone',false) && !get_option('playground_downloaded',false)) {
        quickplayground_clone();
        //wp_schedule_single_event( time() + 300, 'quickplayground_load_images');
        //add_action('wp_footer','quickplayground_load_images');
    }
}

/**
 * Optionally displays a clone message on the front end.
 *
 * @param string $content The original post content.
 * @return string         Modified content with clone message if applicable.
 */
function quickplayground_clone_message($content) {
    if(is_admin()) {
        return $content;
    }
    //return '<p>You are a clone</p>';
    $clone['output'] = quickplayground_clone();
    if(!empty($clone['output'])) {
        return '<div class="quickplayground-clone-message">'.wp_kses_post($clone['output']).'</div>';
    } else {
        return $content;
    }
}

/**
 * Clones posts, taxonomy, users, and images from the source site to the playground.
 *
 * @param string|null $target The specific target to clone ('posts', 'taxonomy', 'images'), or null for all.
 * @return string             Output log of the cloning process.
 */
function quickplayground_clone( $target = null ) {

    global $wpdb, $current_user, $baseurl, $mysite_url;
    $localdir = trailingslashit(plugin_dir_path( __FILE__ ));
    $baseurl = get_option('playground_sync_origin');
    $no_cache = get_option('playground_no_cache',false);
    $mysite_url = rtrim(get_option('siteurl'),'/');
    $page_on_front = get_option('page_on_front');
    if(empty($baseurl)) {
        return '<p>Error: No base URL set for cloning. Please set the playground sync origin in the settings.</p>';
    }
    $playground_profile = get_option('playground_profile','default');
    
    add_user_meta( $current_user->ID, 'tm_member_welcome', time() );

    if($baseurl == $mysite_url) {
        return '<p>Error: You cannot clone your own website</p>';
    }

    $url = $baseurl .'/wp-json/quickplayground/v1/clone_posts/'.$playground_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$playground_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $settingsurl = $baseurl .'/wp-json/quickplayground/v1/clone_settings/'.$playground_profile.'?t='.time();
    if($no_cache) $settingsurl .= '&nocache=1';
    $imgurl = $baseurl .'/wp-json/quickplayground/v1/clone_images/'.$playground_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';
    $customurl = $baseurl .'/wp-json/quickplayground/v1/clone_custom/'.$playground_profile.'?t='.time();
    if($no_cache) $customurl .= '&nocache=1';

    error_log('quickplayground_clone called with url: '.$url);  
    if(empty($target) || 'posts' == $target) {
    error_log('Cloning from '.$url);

    $pagecount = 0;
    $localjson = $localdir.'posts.json';
    $out = '';
    if(file_exists($localjson)) {
        $json = file_get_contents($localjson);
        echo $out = '<p>Using '.$localjson.'</p>';
    }
    else {
        $out = "<p>Trying $url not $localjson</p>";
    $response = wp_remote_get($url);
        if(is_wp_error($response)) {
            $out .=  '<p>Error: '.esc_html($response->get_error_message()).$url.'</p>';
            error_log('Error retrieving clone data: '.$response->get_error_message());
            return $clone['output'];
        } 
        $json = $response['body'];
    }
        update_option('clone_posts_json',$json);
        $json = quickplayground_json_incoming($json);
        update_option('clone_posts_json_modified',$json);
        $clone = json_decode($json,true);//decode as associative array
        if(!$clone) {
        $clone = quickplayground_clone_output($clone, $out."<p>Unable to decode JSON ".substr($json,0,50)."</p>");
        update_option('clone_posts_log',$clone['output']);
        return;
        }
        $clone = quickplayground_clone_output($clone, $out);
        if(!is_array($clone)) {
            error_log('json decode error '.var_export($clone,true));
            return 'json decode error '.esc_html(var_export($clone,true)).' json '.esc_html($json);
        }
        error_log('clone array created '.sizeof($clone));
        $out = '<h1>Cloning from '.esc_html($url).'</h1>';
        $out .= sprintf('<p>quickplayground_clone cache %s</p>',var_export($no_cache,true));
        $clone = quickplayground_clone_output($clone, $out);
        if(!empty($clone['client_ip']))
            error_log('client_ip '.$clone['client_ip']);

        if(!empty($clone['playground_premium']))
            update_option('playground_premium',$clone['playground_premium']);
        if(!empty($clone['posts'])) {
            //get rid of hello world and sample page
            $wpdb->query("truncate $wpdb->posts");
        }

        $clone = apply_filters('playground_clone_received',$clone);
        error_log('fetched clone with: '.sizeof($clone['posts']).' posts');
        if(!empty($clone['playground_sync_code']))
            update_option('playground_sync_code',$clone['playground_sync_code']);
        $out = sprintf('<p>Cloning %s posts</p>',sizeof($clone['posts']));
        $clone = quickplayground_clone_output($clone, $out);

        $out = sprintf('<p>Nav id from source %d</p>',intval($clone['nav_id']));
        $clone = quickplayground_clone_output($clone, $out);

        $nav_id = 0;
        $page_ids = [];
        error_log('start cloning posts');
        if(!empty($clone['posts']) && is_array($clone['posts']))
        foreach($clone['posts'] as $post) {
            error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
            $out = 'post: '.intval($post['ID']).' '.esc_html($post['post_title'].' '.$post['post_type'])."<br>\n";
            $clone = quickplayground_clone_output($clone, $out);
            if('wp_navigation' == $post['post_type']) {
                $post['post_content'] = str_replace($baseurl,$mysite_url,$post['post_content']);
            }
            if(isset($_GET['page']) && $post['ID'] == $page_on_front) {
                $out = sprintf('<p>Front page html</p><pre>%s</pre>',esc_html($post['post_content']));
                $clone = quickplayground_clone_output($clone, $out);
            }
            if($post['post_type'] == 'page') {
                $page_ids[] = $post['ID'];
                $pagecount++;
            }

            $out = '<p>Inserting post: '.intval($post['ID']).' '.esc_html($post['post_title'].' '.$post['post_type']).'</p>';
            $clone = quickplayground_clone_output($clone, $out);

            unset($post['filter']);  
            $result = $wpdb->replace($wpdb->posts,$post);
            error_log('Result of post insert: '.var_export($result,true));
            error_log('Last error: '.var_export($wpdb->last_error,true));

            if(!$result) {
                $out = '<p>Error: '.esc_html($wpdb->last_error).'</p>'; $clone = quickplayground_clone_output($clone, $out);
            }
        }

        do_action('quickplayground_clone',$url,$clone);
        update_option('playground_downloaded',true);
        update_option('pages_cloned',$pagecount);

            if(!empty($clone['demo_pages'])) {
            foreach($clone['demo_pages'] as $post) {
                    error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
                    $out = 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
                    $clone = quickplayground_clone_output($clone, $out);

                    if($post['post_type'] == 'page')
                        $pagecount++;
                    $out = '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
                    $clone = quickplayground_clone_output($clone, $out);
                    unset($post['filter']);  
                    $result = $wpdb->replace($wpdb->posts,$post);
                    error_log('Result of post insert: '.var_export($result,true));
                    error_log('Last error: '.var_export($wpdb->last_error,true));

                    if(!$result) {
                        $out = '<p>Error: '.esc_html($wpdb->last_error).'</p>';
                        $clone = quickplayground_clone_output($clone, $out);
                    }
                }
            }   
            if(!empty($clone['demo_posts'])) 
            {
                foreach($clone['demo_posts'] as $post) {
                        error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
                        $out = 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
                        $clone = quickplayground_clone_output($clone, $out);
                        $out = '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
                        $clone = quickplayground_clone_output($clone, $out);
                        unset($post['filter']);  
                        $result = $wpdb->replace($wpdb->posts,$post);
                        $out = "<p>$wpdb->last_query</p>";
                        $clone = quickplayground_clone_output($clone, $out);
                        error_log('Demo post query: '.$wpdb->last_query);
                        error_log('Result of demo post insert: '.esc_html(var_export($result,true)));
                        error_log('Last error: '.esc_html(var_export($wpdb->last_error,true)));
                        if(!$result) {
                            $out = '<p>Error: '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);
                            error_log('error saving demo post '.$post['post_title']);
                        }
                }
            }   

    if(!empty($clone['make_menu'])) {
        $clone = quickplayground_clone_output($clone, '<p>Make menu</p>');
        if(empty($clone['make_menu_ids'])) {
            if(sizeof($page_ids) > 6)
                $page_ids = array_slice($page_ids, 0, 6);
            quickmenu_build_navigation($page_ids);
        }
        else {
            quickmenu_build_navigation($clone['make_menu_ids']);
        }
    } 
    if(!empty($clone['front_page_thumbnail'])) {
        $clone = quickplayground_clone_output($clone, '<p>Cloning front page thumbnail</p>');
        $result = quickplayground_sideload($clone['front_page_thumbnail']);
        $clone = quickplayground_clone_output($clone, '<p>quickplayground_sideload returned</p><div>'.var_export($result,true).'</div>');
        if(is_wp_error($result)) {
            $out = "<p>Error downloading ".$clone['front_page_thumbnail']['guid']."</p>";
        }
        else
            $out = $result;
        $clone = quickplayground_clone_output($clone, $out);
    }

    // incudes posts that might be rsvpmakers
    $clone = apply_filters('playground_clone_posts',$clone);
    update_option('clone_posts_log',$clone['output']);
    }
    if('settings' == $target || empty($target)) {
    $localjson = $localdir.'settings.json';
    if(file_exists($localjson)) {
        $json = file_get_contents($localjson);
        $out = '<p>Using '.$localjson.'</p>';
    }
    else {

    $response = wp_remote_get($settingsurl);
    $out = "<p>trying $settingsurl </p>";
    if(is_wp_error($response)) {
        $out .=  '<p>Error: '.esc_html($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return $clone['output'];
    }
    $json = $response['body'];
    }
    $clone = json_decode($json,true);
    if(!$clone) {
        $clone = quickplayground_clone_output($clone, $out."<p>Unable to decode JSON ".substr($json,0,50)."</p>");
        update_option('clone_settings_log',$clone['output']);
        return;
    }

    $clone = quickplayground_clone_output($clone, $out);
    if(!empty($clone["settings"]))
    {
    $blueprint_only_settings = ["playground_no_cache",
        "playground_is_demo",
        "playground_premium_enabled",
        "playground_premium_expiration",
        "origin_stylesheet",
        "is_playground_clone",
        "playground_profile",
        "playground_sync_origin"];
        foreach($clone["settings"] as $setting => $value) {
            if(in_array($setting,$blueprint_only_settings))
                continue;
            $out = '<p>setting '.esc_html($setting).' = '.esc_html(var_export($value,true)).'</p>';$clone = quickplayground_clone_output($clone, $out);
            error_log('setting '.$setting.' = '.var_export($value,true));
            update_option($setting,$value);
            $saved_options[] = $setting;
        }
        update_option('quickplayground_clone_options',$saved_options);
    }

    update_option('clone_settings_log',$clone['output']);
    }

    $localjson = $localdir.'quickplayground_prompts.json';
    if(file_exists($localjson)) {
        $promptjson = file_get_contents($localjson);
        $out = '<p>Using '.$localjson.'</p>';
    }
    else {
    $url = $baseurl.'/wp-content/uploads/quickplayground_prompts_'.$playground_profile.'.json?t='.time();
    $out = "<p>$url</p>";
    $response = wp_remote_get($url);
    if(is_wp_error($response)) {
        $out .= '<p>Error: '.esc_html($response->get_error_message()).$url.'</p>';
    } else {
        $status_code = wp_remote_retrieve_response_code( $response );
        if('200' == $status_code) {
        $promptjson = $response['body'];
        $out .= $promptjson;
        set_transient('playgroundmessages',json_decode($promptjson,true),DAY_IN_SECONDS * 5);
        }
        else {
            $out .= "<p>Returned $status_code</p>";
        }
        }
    }

    //run before the taxonomy / metadata stage so attachment ids can be added to those checked for metadata
    quickplayground_clone_images($target);

    if(empty($target) || 'taxonomy' == $target) {
    $localjson = $localdir.'taxonomy.json';
    if(file_exists($localjson)) {
        $json = file_get_contents($localjson);
        $out .= '<p>Using '.$localjson.'</p>';
    }
    else {
    $out .= "<p>$taxurl</p>";
    $response = wp_remote_get($taxurl);
    if(is_wp_error($response)) {
        $out .=  '<p>Error: '.esc_html($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return $clone['output'];
    }
    $json = $response['body'];
    }
    update_option('clone_tax_json',$json);

    $clone = json_decode($json,true);
    if(!$clone) {
    $clone = quickplayground_clone_output($clone, $out."<p>Unable to decode JSON ".substr($json,0,50)."</p>");
    update_option('clone_posts_log',$clone['output']);
    return;
    }

    $clone = quickplayground_clone_output($clone, $out);
    if(!is_array($clone)) {
        error_log('error decoding taxonomy clone json');
        $clone['output'] = '<p>Error decoding taxonomy clone json</p>';
        return $clone['output'];
    }

    $out = "<h2>Cloning Metadata and Taxonomy</h2>";$clone = quickplayground_clone_output($clone, $out);

        if(!empty($clone['postmeta'])) {

            foreach($clone['postmeta'] as $meta) {

                $result = $wpdb->replace($wpdb->postmeta,$meta);

                if(!$result) {

                    $out = '<p>Error: '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);

                }

            }

        }

    if(!empty($clone['termmmeta'])) {

        foreach($clone['termmeta'] as $meta) {

            $result = $wpdb->replace($wpdb->termmeta,$meta);

            if(!$result) {

                $out = '<p>Error: termmeta '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);

            }

        }

    }

    if(!empty($clone['terms'])) {

        foreach($clone['terms'] as $row) {

            $result = $wpdb->replace($wpdb->terms,$row);
            $out = "<p>$wpdb->last_query</p>";$clone = quickplayground_clone_output($clone, $out);

            if(!$result) {

                $out = '<p>Error: terms '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);

            }

        }

    }

    if(!empty($clone['term_relationships'])) {
        $out = sprintf('<p>%d term_relationships',sizeof($clone['term_relationships']));$clone = quickplayground_clone_output($clone, $out);
        foreach($clone['term_relationships'] as $row) {
            if($row['object_id']) {
                $result = $wpdb->replace($wpdb->term_relationships,$row);
                $out = "<p>$wpdb->last_query</p>";$clone = quickplayground_clone_output($clone, $out);
                if(!$result) {
                    $out = '<p>Error: term_relationships '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);
                }
            }
        }
    }

    if(!empty($clone['term_taxonomy'])) {
        foreach($clone['term_taxonomy'] as $row) {
            $result = $wpdb->replace($wpdb->term_taxonomy,$row);
            $out = "<p>$wpdb->last_query</p>";$clone = quickplayground_clone_output($clone, $out);
            if(!$result) {
                $out = '<p>Error: term_taxonomy '.esc_html($wpdb->last_error).'</p>';$clone = quickplayground_clone_output($clone, $out);
            }
        }
    }

    $out = "<h2>Cloning users</h2>";$clone = quickplayground_clone_output($clone, $out);
    if(!empty($clone["users"]))
    {
        foreach($clone['users'] as $user) {
            if(empty($user['first_name']) || $user['last_name'])
                continue;
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
            unset($user['first_name']);
            unset($user['last_name']); 
            if(!$user['ID'] == 1) {
            $result = $wpdb->replace($wpdb->users,$user);
                $log = printf('%s <br />User Result: %s<br />',esc_html($wpdb->last_query), esc_html(var_export($result,true)));
            }
            else {
                $log = sprintf('User %d %s %s', intval($user['ID']), $first_name, $last_name);
            }
            error_log($log);
            $out = '<p>'.esc_html($log).'</p>';$clone = quickplayground_clone_output($clone, $out);
            update_user_meta($user['ID'],'first_name',$first_name);
            update_user_meta($user['ID'],'last_name',$last_name);
        }
    }
    
    if(!empty($clone['adminuser']))
    {
        foreach($clone['adminuser'] as $key => $value)
            update_user_meta(1,$key,$value);
    }
    update_option('clone_tax_log',$clone['output']);

    }

    //empty($target) || 

    $out = '<h2>Custom</h2>';
    if(empty($target) || 'custom' == $target) {
    $localjson = $localdir.'custom.json';
    if(file_exists($localjson)) {
        $json = file_get_contents($localjson);
        $out .= '<p>Using '.$localjson.'</p>';
    }
    else {
    try {
        $out .= '<p>Trying '.$customurl.'</p>';
        $response = wp_remote_get($customurl);
    } catch (Exception $e) {
        $out .= '<p>Error fetching: '.esc_html($customurl.' '.$e->getMessage()).'</p>';
        error_log('Error fetching: '.esc_html($customurl.' '.$e->getMessage()));
        return;
    }   
    if(is_wp_error($response)) {
        $out .= '<p>Error: '.esc_html($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return;
    }
    $json = $response['body'];
    }
    update_option('clone_custom_json',$json);

    $clone = json_decode($json,true);
    if(!$clone) {
    $clone = quickplayground_clone_output($clone, $out."<p>Unable to decode JSON ".substr($json,0,50)."</p>");
    update_option('clone_custom_log',$clone['output']);
    return;
    }
    $clone = quickplayground_clone_output($clone, $out);
    if(!is_array($clone)) {
        error_log('error decoding custom clone json');
        $out .= '<p>Error decoding custom clone json</p>';$clone = quickplayground_clone_output($clone, $out);
        return;
    }
    $tablerows = $wpdb->get_results("SHOW TABLES",ARRAY_N);
    $alltables =[];
    foreach($tablerows as $row)
        $alltables[] = $row[0];
    $out = sprintf('<p>Tables %s</p>',var_export($alltables,true));
    $clone = quickplayground_clone_output($clone, $out);
    $clone = apply_filters('quickplayground_custom_clone_receiver',$clone);
    if(!empty($clone['custom_tables']))
        foreach($clone['custom_tables'] as $table => $rows) {
            if(!in_array($table,$alltables)) {
                $out = "<p>$table not installed</p>";
                $clone = quickplayground_clone_output($clone, $out);
                continue;
            }
            $out = sprintf('<p>%s %d rows</p>',$table,sizeof($rows));$clone = quickplayground_clone_output($clone, $out);
            foreach($rows as $row) {
                $wpdb->replace($table,(array) $row);
                $out = "<p>$wpdb->last_query</p>";$clone = quickplayground_clone_output($clone, $out);
            }
        }
        update_option('clone_custom_log',$clone['output']);
    }
    if(empty($target)) {
        update_option('playground_sync_date',date('Y-m-d H:i:s'));
    }
}

/**
 * Builds a navigation menu from an array of page IDs and updates or creates the navigation post.
 *
 * @param array $ids Array of page IDs to include in the navigation.
 */
function quickmenu_build_navigation($ids) {
    global $wpdb;
if(!wp_is_block_theme())
    return;
$page_on_front = get_option('page_on_front');
$nav = sprintf('<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"custom"} /-->','Home',esc_attr(get_site_url()))."\n";
foreach($ids as $id) {
    if($id != $page_on_front)
        $nav .= sprintf('<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->',esc_attr(get_the_title($id)),$id,esc_attr(get_permalink($id)))."\n";
}
$nav_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type='wp_navigation'");
if($nav_id) {
  $wpdb->update($wpdb->posts,array('post_content' => $nav),array('ID' => $nav_id));
}
else {
  $post = array(
    'post_content'   => $nav,
    'post_name'      => 'navigation',
    'post_title'     => 'Navigation',
    'post_status'    => 'publish',
    'post_type'      => 'wp_navigation',
    'post_author'    => $site_owner,
    'ping_status'    => 'closed'
  );
  $nav_id = wp_insert_post($post);
}
$headers = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'wp_template_part' AND post_title LIKE '%Header%' or post_name LIKE '%header%'");
$pattern = '/<!-- wp:navigation([^\/]+)\/-->/';
if($headers) {
  foreach($headers as $header) {
    $header_id = $header->ID;
    preg_match($pattern,$header->post_content,$matches);
    if(!empty($matches[0])) {
    if(!empty(trim($matches[1]))){
        $json = json_decode($matches[1],true);
        $json['ref'] = $nav_id;
    }
    else {
        $json = ['ref'=>$nav_id];
    }
    $header_content = $header->post_content = preg_replace($pattern,'<!-- wp:navigation '.json_encode($json).' /-->',$header->post_content);
    $result = wp_update_post(array('ID' => $header_id,'post_content' => $header->post_content));
    $headerpost = get_post($result);    
    }
  }
}
else {
  $template_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
  $header_content = $template_part->content;
    preg_match($pattern,$header_content,$matches);
    if(empty($matches[0]))
      return 'no match '.var_export($matches,true);
    if(!empty(trim($matches[1]))){
        $json = json_decode($matches[1],true);
        $json['ref'] = $nav_id;
    }
    else {
        $json = ['ref'=>$nav_id];
    }
    $header_content = preg_replace($pattern,'<!-- wp:navigation '.json_encode($json).' /-->',$header_content);
  $post = array(
    'post_content'   => $header_content,
    'post_name'      => 'header',
    'post_title'     => 'Header',
    'post_status'    => 'publish',
    'post_type'      => 'wp_template_part',
    'post_author'    => $site_owner,
    'ping_status'    => 'closed'
  );
  wp_insert_post($post);
  }
}

function quickplayground_clone_output($clone, $message) {
    echo $message;
    if(!isset($clone['output']))
        $clone['output'] = '';
    $clone['output'] .= $message;
    return $clone;
}

function quickplayground_clone_images($target) {
    global $wpdb, $playground_uploads;
    $localdir = trailingslashit(plugin_dir_path( __FILE__ ));
    $baseurl = get_option('playground_sync_origin');
    $no_cache = get_option('playground_no_cache',false);
    $mysite_url = rtrim(get_option('siteurl'),'/');
    $playground_profile = get_option('playground_profile','default');
    
    echo $imgurl = $baseurl .'/wp-json/quickplayground/v1/clone_images/'.$playground_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';
   // || empty($target))
    if(('images' == $target) || empty($target))  {
    $out = '<h2>Ready for images '.esc_html($imgurl).'</h2>';
    $localjson = $localdir.'images.json';
    if(file_exists($localjson)) {
        $json = file_get_contents($localjson);
        $out = '<p>Using '.$localjson.'</p>';
    }
    else {
        try {
            $response = wp_remote_get($imgurl);
        } catch (Exception $e) {
            $out .= '<p>Error fetching: '.esc_html($imgurl.' '.$e->getMessage()).'</p>';
            error_log('Error fetching: '.esc_html($imgurl.' '.$e->getMessage()));
            return;
        }   
        if(is_wp_error($response)) {
            $out . '<p>Error: '.esc_html($response->get_error_message()).'</p>';
            error_log('Error retrieving clone data: '.$response->get_error_message());
            return;
        }
        $json =  $response['body'];
    }
    update_option('clone_images_json',$json);
    $clone = json_decode($json,true);
    if(!$clone) {
    $clone = quickplayground_clone_output($clone, $out."<p>Unable to decode JSON ".substr($json,0,50)."</p>");
    update_option('clone_images_log',$clone['output']);
    return;
    }

    if(!empty($clone['site_logo'])) {
        $result = quickplayground_sideload($clone['site_logo'],['update_option'=>'site_logo']);
        $clone = quickplayground_clone_output($clone, '<p>quickplayground_sideload for site_logo returned</p><div>'.$result.'</div>');
        //retry later if this doesn't work the first time
        set_transient('playground_logo',$clone['site_logo'],HOUR_IN_SECONDS);
    }
    if(!empty($clone['site_icon'])) {
        $result = quickplayground_sideload($clone['site_icon'],['update_option'=>'site_icon']);
        $clone = quickplayground_clone_output($clone, '<p>quickplayground_sideload for site_icon returned</p><div>'.$result.'</div>');
        set_transient('playground_icon',$clone['site_icon'],HOUR_IN_SECONDS);
    }
    if(!empty($clone['thumbnails'])) {
        $newthumb = [];
        foreach($clone['thumbnails'] as $index => $thumb) {
            if($index < 5) {
                $result = quickplayground_sideload($thumb);
                $clone = quickplayground_clone_output($clone, '<p>quickplayground_sideload returned</p><div>'.$result.'</div>');
                if(is_wp_error($result)) {
                    $out .= "<p>Error downloading ".$thumb['guid']."</p>";
                    $clone = quickplayground_clone_output($clone, $out);
                    $newthumb[] = $thumb;
                }
                else {
                    $clone = quickplayground_clone_output($clone, $result);
                }
            }
            else {
                $newthumb[] = $thumb;
            }
        }
        $clone['thumbnails'] = $newthumb;
    }
    $clone = quickplayground_clone_output($clone, '<p>Saving image data to import later.</p>');
    update_option('quickplayground_clone_images',$clone);
    update_option('clone_images_log',$clone['output']);
    }
}

function quickplayground_sideload_images($attachments) {
    print_r($attachments);    
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $miss = [];
        $out = '';    
        foreach($attachments as $att) {
            printf('<p>%s</p>',var_export($att,true));
            try {
                $id = media_sideload_image($att['guid'], $att['post_parent'], $att['post_excerpt'], 'id');
                if($id) {
                $out = sprintf('<p>Downloaded %s</p>',esc_html($att['guid']));
                $clone = quickplayground_clone_output($clone, $out);
                if(!empty($att['thumbnail_for']))
                    update_post_meta(intval($att['thumbnail_for']),'_thumbnail_id',$id);
                if(!empty($att['option']))
                    update_option(sanitize_text_field($att['option']),$id);
                }
                else {
                   $out .= sprintf('<p>Error downloading thumbnail %s, returned %s</p>',esc_html($att['guid']),esc_html(var_export($id,true)));
                    $miss[] = $att;
                }
                }
            catch (Exception $e) {
                $out .= sprintf('<p>Error downloading %s %s</p>',esc_html($att['guid']),esc_html($e->getMessage()));
                error_log($att['guid'] .' download error '.$e->getMessage());
                $miss[] = $att;
            }
        }
    if(!empty($miss)) {
        $out .= sprintf('<p>Saving %d attachments to retry</p>',sizeof($miss));
        $misses = get_transient('quickplayground_images_retry',[]);
        $misses[] = $miss;
        set_transient('quickplayground_images_retry',$misses,HOUR_IN_SECONDS);
    }
    return $out;
}

function quickplayground_sideload( $post_data, $args = [] ) {
	global $wpdb;
    $desc = (empty($args['desc'])) ? null : $args['desc'];
    $attachment_id = $post_data['ID'];    
    if(isset($args['update_option'])) {
        $option = $args['update_option'];
        update_option($option,$attachment_id);
        $out .= "<p>Set $option $attachment_id</p>";
    }
require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_id = intval($post_data['ID']);
    $exists = get_post($attachment_id);
    $url = $post_data['guid'];
    unset($post_data['guid']);
    unset($post_data['post_content']);
    $out = '';
    if($exists) {
    $parts = explode('/upload/',$url);
    if(!empty($parts[1]) && strpos($exists->guid,$parts[1])) {
        $out = "<p>Previously downloaded $url $attachment_id to $exists->guid $exists->ID</p>";
        $metadata = wp_get_attachment_metadata($exists->ID);
        $out .= "<pre>".var_export($metadata,true).'</pre>';
        return $out;
    }
    else {
        //some kind of error
        return "<p>Duplicate duplicate entry for $attachment_id $exists->guid</p>";
    }

    }
    $file_array         = array();
	$file_array['name'] = wp_basename( $url );
	// Download file to temp location.
	$file_array['tmp_name'] = download_url( $url );
    $post_id = $post_data['post_parent'];
    //save downloaded file, keeping original date folders
    $attachment_id = quickplayground_media_handle_sideload( $file_array, $post_id, $desc, $post_data );
	if ( is_wp_error( $attachment_id ) ) {
        error_log("error downloading $url ".$temp_id->get_error_message());
        return $attachment_id;
	}
    else {
        $out .= "<p>Downloaded $url ".$attachment_id."</p>";
    }
    return $out;
}

/**
 * quickplayground_media_handle_sideload
 * Slightly hacked version of core function, keeps the post ID
 *
 * @param [type] $file_array
 * @param integer $post_id
 * @param [type] $desc
 * @param array $post_data
 * @return void
 */
function quickplayground_media_handle_sideload( $file_array, $post_id = 0, $desc = null, $post_data = array() ) {
	$overrides = array( 'test_form' => false );

	if ( isset( $post_data['post_date'] ) && substr( $post_data['post_date'], 0, 4 ) > 0 ) {
		$time = $post_data['post_date'];
	} else {
		$post = get_post( $post_id );
		if ( $post && substr( $post->post_date, 0, 4 ) > 0 ) {
			$time = $post->post_date;
		} else {
			$time = current_time( 'mysql' );
		}
	}

	$file = wp_handle_sideload( $file_array, $overrides, $time );

	if ( isset( $file['error'] ) ) {
		return new WP_Error( 'upload_error', $file['error'] );
	}

	$url     = $file['url'];
	$type    = $file['type'];
	$file    = $file['file'];
	$title   = preg_replace( '/\.[^.]+$/', '', wp_basename( $file ) );
	$content = '';

	// Use image exif/iptc data for title and caption defaults if possible.
	$image_meta = wp_read_image_metadata( $file );

	if ( $image_meta ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}

		if ( trim( $image_meta['caption'] ) ) {
			$content = $image_meta['caption'];
		}
	}

	if ( isset( $desc ) ) {
		$title = $desc;
	}

	// Construct the attachment array.
	$attachment = array_merge(
		array(
			'post_mime_type' => $type,
			'guid'           => $url,
			'post_parent'    => $post_id,
			'post_title'     => $title,
			'post_content'   => $content,
		),
		$post_data
	);

	// This should never be set as it would then overwrite an existing attachment.
	// changed unset( $attachment['ID'] );

	// Save the attachment metadata.
	$attachment_id = quickplayground_insert_attachment( $attachment, $file, $post_id, true );

	if ( ! is_wp_error( $attachment_id ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
	}

	return $attachment_id;
}

function quickplayground_insert_attachment( $args, $file = false, $parent_post_id = 0, $wp_error = false, $fire_after_hooks = true ) {
	global $wpdb;
    $defaults = array(
		'file'        => $file,
		'post_parent' => 0,
	);

	$data = wp_parse_args( $args, $defaults );

	if ( ! empty( $parent_post_id ) ) {
		$data['post_parent'] = $parent_post_id;
	}

	$data['post_type'] = 'attachment';
    $file = $data['file'];
    unset($data['file']);
    $result = $wpdb->replace($wpdb->posts,$data);
    if(!$result) {
        return new WP_Error( 'error_code', __( 'Failed to insert attachment '.$args['ID'], 'text-domain' ) );
    }
    $attachment_id = $args['ID'];
    update_attached_file( $attachment_id, $file );
	return $attachment_id;//wp_insert_post( $data, $wp_error, $fire_after_hooks );
}

function quickplayground_get_thumbnails_footer() {
    $clone = get_option('quickplayground_clone_images',[]);
    if(!$clone)
        return;
    $successful = 0;
    $out = '<p>Playground is downloading additional images</p>';
    if(!empty($clone['thumbnails'])) {
      $out .= '<p>'.sizeof($clone['thumbnails']).' saved thumbnails</p>';
      foreach($clone['thumbnails'] as $thumb) {
        $result = quickplayground_sideload($thumb);
        if(is_wp_error($result)) {
            $out .= "<p>Error downloading ".$thumb['guid']."</p>";
        }
        else {
        $out .= "<p>Downloaded ".$thumb['guid']."</p>";
        $successful++;
        }
      }
    }
    $logo = get_transient('playground_logo');
    if(!empty($logo)) {
    $result = quickplayground_sideload($logo,['update_option'=>'site_logo']);
    if(is_wp_error($result)) {
        $out .= "<p>Error downloading ".$thumb['guid']."</p>";
    }
    else {
    $out .= "<p>Downloaded ".$thumb['guid']."</p>";
      $out .= ' set site logo to '.$logo['ID'];
      update_option('site_logo',$logo['ID']);
      $successful++;
    }
    }

    $icon = get_transient('playground_icon');
    if(!empty($icon)) {
    $result = quickplayground_sideload($icon,['update_option'=>'site_icon']);
    if(is_wp_error($result)) {
        $out .= "<p>Error downloading ".$thumb['guid']."</p>";
    }
    else {
    $out .= "<p>Downloaded ".$thumb['guid']."</p>";
      update_option('site_icon',$icon['ID']);
      $successful++;
    }
    }

    $out .= sprintf('<p>%d successful image downloads</p>',$successful);
    $log = get_option('clone_images_log');
    update_option('clone_images_log',$log."\n".$out);
    delete_option('quickplayground_clone_images');
    return "<p>Downloaded $successful additional images. Page should reload automatically.</p>";
}
