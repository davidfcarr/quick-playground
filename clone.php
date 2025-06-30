<?php
add_action('init', 'quickplayground_clone_init');

/**
 * Initializes the Quick Playground clone process on 'init' action.
 */
function quickplayground_clone_init() {
    if(isset($_GET['clonetest'])) {
        $clone['output'] = quickplayground_clone();
        wp_die($clone['output']);
    }
    if(get_option('is_playground_clone',false) && !get_option('playground_downloaded',false)) {
        quickplayground_clone();
        //add_filter('the_content', 'quickplayground_clone_message');
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
        return '<div class="quickplayground-clone-message">'.$clone['output'].'</div>';
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

    $url = $baseurl .'/wp-json/quickplayground/v1/playground_clone/'.$playground_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$playground_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $imgurl = $baseurl .'/wp-json/quickplayground/v1/clone_images/'.$playground_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';

    error_log('quickplayground_clone called with url: '.$url);  
    if(empty($target) || 'posts' == $target) {
    error_log('Cloning from '.$url);
    $response = wp_remote_get($url);

    $pagecount = 0;

    if(is_wp_error($response)) {
        $clone['output'] .=  '<p>Error: '.htmlentities($response->get_error_message()).$url.'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return $clone['output'];
    } 
        $json = $response['body'];
        update_option('clone_posts_json',$response['body']);
        $json = quickplayground_json_incoming($json);
        update_option('clone_posts_json_modified',$json);
        $clone = json_decode($json,true);//decode as associative array
        if(!is_array($clone)) {
            error_log('json decode error '.var_export($clone,true));
            return 'json decode error '.htmlentities(var_export($clone,true)).' json '.htmlentities($json);
        }
        error_log('clone array created '.sizeof($clone));
        $clone['output'] = '';
        $clone['output'] .= '<h1>Cloning from '.$url.'</h1>';
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
        $clone['output'] .= sprintf('<p>Cloning %s posts</p>',sizeof($clone['posts']));

        $clone['output'] .= sprintf('<p>Nav id from source %d</p>',$clone['nav_id']);

        $nav_id = 0;
        $page_ids = [];
        error_log('start cloning posts');
        if(!empty($clone['posts']) && is_array($clone['posts']))
        foreach($clone['posts'] as $post) {
            error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
            $clone['output'] .= 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
            if('wp_navigation' == $post['post_type']) {
                $post['post_content'] = str_replace($baseurl,$mysite_url,$post['post_content']);
            }
            if(isset($_GET['page']) && $post['ID'] == $page_on_front) {
                printf('<p>Front page html</p><pre>%s</pre>',htmlentities($post['post_content']));
            }
            if($post['post_type'] == 'page') {
                $page_ids[] = $post['ID'];
                $pagecount++;
            }

            $clone['output'] .= '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
            unset($post['filter']);  
            $result = $wpdb->replace($wpdb->posts,$post);
            error_log('Result of post insert: '.var_export($result,true));
            error_log('Last error: '.var_export($wpdb->last_error,true));

            if(!$result) {
                $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
            }
            else {
                update_post_meta($post['ID'],'cloned',1);
            }
        }

        do_action('quickplayground_clone',$url,$clone);
        update_option('playground_downloaded',true);
        update_option('pages_cloned',$pagecount);

            if(!empty($clone['demo_pages'])) {
            foreach($clone['demo_pages'] as $post) {
                    error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
                    $clone['output'] .= 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
                    if($post['post_type'] == 'page')
                        $pagecount++;
                    $clone['output'] .= '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
                    unset($post['filter']);  
                    $result = $wpdb->replace($wpdb->posts,$post);
                    error_log('Result of post insert: '.var_export($result,true));
                    error_log('Last error: '.var_export($wpdb->last_error,true));

                    if(!$result) {
                        $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
                    }
                    else {
                        add_post_meta($post['ID'],'cloned',1);
                    }
                }
            }   
            if(!empty($clone['demo_posts'])) 
            {
                foreach($clone['demo_posts'] as $post) {
                        error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
                        $clone['output'] .= 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
                        $clone['output'] .= '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
                        unset($post['filter']);  
                        $result = $wpdb->replace($wpdb->posts,$post);
                        $clone['output'] .= "<p>$wpdb->last_query</p>";
                        error_log('Demo post query: '.$wpdb->last_query);
                        error_log('Result of demo post insert: '.var_export($result,true));
                        error_log('Last error: '.var_export($wpdb->last_error,true));
                        if(!$result) {
                            $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
                            error_log('error saving demo post '.$post['post_title']);
                        }
                        else {
                            add_post_meta($post['ID'],'cloned',1);
                        }
                }
            }   

    if(!empty($clone['make_menu'])) {
        if(empty($clone['make_menu_ids'])) {
            if(sizeof($page_ids) > 6)
                $page_ids = array_slice($page_ids, 0, 6);
            quickmenu_build_navigation($page_ids);
        }
        else {
            quickmenu_build_navigation($clone['make_menu_ids']);
        }
    } 

    
    if(!empty($clone["settings"]))
    {
        foreach($clone["settings"] as $setting => $value) {
            $clone['output'] .= '<p>setting '.$setting.' = '.var_export($value,true).'</p>';
            error_log('setting '.$setting.' = '.var_export($value,true));
            update_option($setting,$value);
        }
    }
    // incudes posts that might be rsvpmakers
    $clone = apply_filters('playground_clone_posts',$clone);

    update_option('clone_posts_log',$clone['output']);
    }

    if(empty($target) || 'taxonomy' == $target) {
    $response = wp_remote_get($taxurl);
    if(is_wp_error($response)) {
        $clone['output'] .=  '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return $clone['output'];
    }
    update_option('clone_tax_json',$response['body']);

    $clone = json_decode($response['body'],true);
    if(!is_array($clone)) {
        error_log('error decoding taxonomy clone json');
        $clone['output'] = '<p>Error decoding taxonomy clone json</p>';
        return $clone['output'];
    }
    $clone['output'] = '';

    echo "<h2>Cloning Metadata and Taxonomy</h2>";

        if(!empty($clone['postmeta'])) {

            foreach($clone['postmeta'] as $meta) {

                $result = $wpdb->replace($wpdb->postmeta,$meta);

                if(!$result) {

                    $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';

                }

            }

        }

    if(!empty($clone['termmmeta'])) {

        foreach($clone['termmeta'] as $meta) {

            $result = $wpdb->replace($wpdb->termmeta,$meta);

            if(!$result) {

                $clone['output'] .= '<p>Error: termmeta '.htmlentities($wpdb->last_error).'</p>';

            }

        }

    }

    if(!empty($clone['terms'])) {

        foreach($clone['terms'] as $row) {

            $result = $wpdb->replace($wpdb->terms,$row);
            $clone['output'] .= "<p>$wpdb->last_query</p>";

            if(!$result) {

                $clone['output'] .= '<p>Error: terms '.htmlentities($wpdb->last_error).'</p>';

            }

        }

    }

    if(!empty($clone['term_relationships'])) {
        $clone['output'] .= sprintf('<p>%d term_relationships',sizeof($clone['term_relationships']));
        foreach($clone['term_relationships'] as $row) {

            $result = $wpdb->replace($wpdb->term_relationships,$row);
            $clone['output'] .= "<p>$wpdb->last_query</p>";

            if(!$result) {
                $clone['output'] .= '<p>Error: term_relationships '.htmlentities($wpdb->last_error).'</p>';
            }
        }
    }

    if(!empty($clone['term_taxonomy'])) {
        foreach($clone['term_taxonomy'] as $row) {
            $result = $wpdb->replace($wpdb->term_taxonomy,$row);
            $clone['output'] .= "<p>$wpdb->last_query</p>";
            if(!$result) {
                $clone['output'] .= '<p>Error: term_taxonomy '.htmlentities($wpdb->last_error).'</p>';
            }
        }
    }

    echo "<h2>Cloning users</h2>";
    if(!empty($clone["users"]))
    {
        foreach($clone['users'] as $user) {
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
            unset($user['first_name']);
            unset($user['last_name']); 
            if(!$user['ID'] == 1) {
            $result = $wpdb->replace($wpdb->users,$user);
                $log = printf('%s <br />User Result: %s<br />',$wpdb->last_query, var_export($result,true));
            }
            else {
                $log = sprintf('User %d %s %s', $user['ID'], $first_name, $last_name);
            }
            error_log($log);
            $clone['output'] = '<p>'.$log.'</p>';
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

    if(empty($target) || 'images' == $target) {
    echo '<h2>Ready for images '.$imgurl.'</h2>';
    try {
        $response = wp_remote_get($imgurl);
    } catch (Exception $e) {
        echo '<p>Error fetching: '.$imgurl.' '.$e->getMessage().'</p>';
        error_log('Error fetching: '.$imgurl.' '.$e->getMessage());
        return;
    }   
    if(is_wp_error($response)) {
        echo '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return;
    }
    update_option('clone_images_json',$response['body']);

    $clone = json_decode($response['body'],true);
    if(!is_array($clone)) {
        error_log('error decoding taxonomy clone json');
        echo '<p>Error decoding image clone json</p>';
        return;
    }
    $clone['output'] = '';
    echo "\n<p>Cloning images from ".$imgurl."</p><pre>".var_export($clone,true)."</pre>";
        if(!empty($clone['attachments'])) {
            $clone['output'] .= sprintf('<p>Cloning %d attachments</p>',sizeof($clone['attachments']));
            foreach($clone['attachments'] as $attachment) {
                $clone['output'] .= '<p>Inserting attachment: '.$attachment['ID'].' '.$attachment['guid'].'</p>';
                $result = $wpdb->replace($wpdb->posts,$attachment);
                if(!$result) {
                    $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
                }
                else {
                    add_post_meta($attachment['ID'],'cloned',1);
                }
            }
        }
        if(!empty($clone['attachments_meta'])) {
            $clone['output'] .= sprintf('<p>Cloning %d attachments meta</p>',sizeof($clone['attachments_meta']));
            foreach($clone['attachments_meta'] as $meta) {
                $result = $wpdb->replace($wpdb->postmeta,$meta);
                if(!$result) {
                    $clone['output'] .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
                }
            }
        }

        if(!empty($clone['thumbnails']) && is_array($clone['thumbnails'])) {
            update_option('clone_thumbnails',$clone['thumbnails']);
            $retry = [];
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            foreach($clone['thumbnails'] as $id_img) {
                $clone['output'] .= sprintf('<p>Downloaded Thumbnail %s</p>',var_export($id_img,true));
                if(empty($id_img['guid']))
                    continue;
                try {
                $id = media_sideload_image($id_img['guid'], $id_img['post_id'], 'cloned thumbail', 'id');
                if($id) {
                $result = update_post_meta($id_img['post_id'],'_thumbnail_id',$id);                    
                $clone['output'] .= sprintf('<p>Downloaded Thumbnail %s, setting thumbnail for %d to %d %s</p>',$id_img['guid'],$id_img['post_id'],$id,var_export($result,true));
                }
                else
                    $clone['output'] .= sprintf('<p>Error downloading thumbnail %s for %d, returned %s</p>',$id_img['guid'],$id_img['post_id'],var_export($id,true));
                    $retry[] = $id_img;
                }
                catch (Exception $e) {
                    $clone['output'] .= sprintf('<p>Error downloading thumbnail %s %s</p>',$id_img['guid'],$e->getMessage());
                    error_log($id_img['guid'] .' download error '.$e->getMessage());
                    $retry[] = $id_img;
                }
                }
        }
            
    if(!empty($retry)) {
        foreach($retry as $id_img) {
            $id = media_sideload_image($id_img['guid'], $id_img['post_id'], 'cloned thumbail', 'id');
            if($id) {
            $result = add_post_meta($id_img['post_id'],'_thumbnail_id',$id);                    
            $clone['output'] .= sprintf('<p>On retry, downloaded Thumbnail %s, setting thumbnail for %d to %d %s</p>',$id_img['guid'],$id_img['post_id'],$id,var_export($result,true));
            }
            else
                $clone['output'] .= sprintf('<p>On retry, error downloading thumbnail %s for %d, returned %s</p>',$id_img['guid'],$id_img['post_id'],var_export($id,true));        
        }
    }

    if(!empty($clone['site_icon_url'])) {
        $id = media_sideload_image($clone['site_icon_url'], 0, 'cloned site icon', 'id');
        update_option('site_icon',$id);
        update_post_meta($id, '_wp_attachment_context', 'site-icon');
        $clone['output'] .= sprintf('<p>Downloaded site icon %s, setting site icon to %d</p>',$clone['site_icon_url'],$id);
    }
    if(!empty($clone['site_logo_url'])) {
        $id = media_sideload_image($clone['site_logo_url'], 0, 'cloned site logo', 'id');
        if(!$id || is_wp_error($id)) {
            //retry with a different function
            $id = media_sideload_image($clone['site_logo_url'], 0, 'cloned site logo', 'id');
        }
        if($id && !is_wp_error($id)) {
            $clone['output'] .= sprintf('<p>Downloaded site logo %s, setting site logo to %d</p>',$clone['site_logo_url'],$id);
            update_option('site_logo',$id);
            update_post_meta($id, '_wp_attachment_context', 'site-logo');
        }
    }
    update_option('clone_images_log',$clone['output']);
    }
    if(empty($target))
        update_option('playground_sync_date',date('Y-m-d H:i:s'));
   return $clone['output'];
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
$nav = sprintf('<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"custom"} /-->','Home',get_site_url())."\n";
foreach($ids as $id) {
    if($id != $page_on_front)
        $nav .= sprintf('<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->',get_the_title($id),$id,get_permalink($id))."\n";
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