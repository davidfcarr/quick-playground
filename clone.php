<?php
add_action('init', 'quickplayground_clone_init');
function quickplayground_clone_init() {
    if(isset($_GET['clonetest'])) {
        $cloneoutput = quickplayground_clone();
        wp_die($cloneoutput);
    }
    if(get_option('is_playground_clone',false) && !get_option('playground_downloaded',false)) {
        quickplayground_clone();
        //add_filter('the_content', 'quickplayground_clone_message');
    }
}
function quickplayground_clone_message($content) {
    if(is_admin()) {
        return $content;
    }
    //return '<p>You are a clone</p>';
    $cloneoutput = quickplayground_clone();
    if(!empty($cloneoutput)) {
        return '<div class="quickplayground-clone-message">'.$cloneoutput.'</div>';
    } else {
        return $content;
    }
}

$baseurl='';
$mysite_url='';
$cloneoutput='';
function quickplayground_clone() {

    global $wpdb, $current_user, $baseurl, $mysite_url, $cloneoutput;
    $url = get_option('playground_sync_origin');
    $mysite_url = rtrim(get_option('siteurl'),'/');
    if(empty($url)) {
        return;
    }
    error_log('quickplayground_clone called with url: '.$url);  
    update_option('is_playground_clone',true);
    $playground_profile = get_option('playground_profile','default');
    
    add_user_meta( $current_user->ID, 'tm_member_welcome', time() );

    $event_table = $wpdb->prefix.'rsvpmaker_event';

    $firstevent = 0;

    if($url == $mysite_url) {
        return '<p>Error: You cannot clone your own website</p>';
    }

    $cloneoutput = '';

    $baseurl = $url;

    $url .= '/wp-json/quickplayground/v1/playground_clone/'.$playground_profile;
    $taxurl = $baseurl .'/wp-json/quickplayground/v1/clone_taxonomy';

    $cloneoutput .= '<h1>Cloning from '.$url.'</h1>';

    error_log('Cloning from '.$url);
    $response = wp_remote_get($url);

    $pagecount = 0;

    if(is_wp_error($response)) {

        $cloneoutput .=  '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response->get_error_message());
        return $cloneoutput;
    } 
        $clone = json_decode($response['body'],true);
        if(!empty($clone['playground_premium']))
            update_option('playground_premium',$clone['playground_premium']);
        if(!empty($clone['posts'])) {
            //get rid of hello world and sample page
            $wpdb->query("truncate $wpdb->posts");
        }

        $clone = apply_filters('playground_clone_received_by_playground',$clone);
        error_log('fetched clone with: '.sizeof($clone['posts']).' posts');
        if(!empty($clone['playground_sync_code']))
            update_option('playground_sync_code',$clone['playground_sync_code']);
        $cloneoutput .= sprintf('<p>Cloning %s posts</p>',sizeof($clone['posts']));

        $cloneoutput .= sprintf('<p>Nav id from source %d</p>',$clone['nav_id']);

        $nav_id = 0;
        $page_ids = [];

        foreach($clone['posts'] as $post) {
            error_log('post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']);
            $cloneoutput .= 'post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type']."<br>\n";
            if($post['post_type'] == 'wp_navigation') {

                $post['post_content'] = str_replace($baseurl,$mysite_url,$post['post_content']);

            }

            else {

                $post['post_content'] = str_replace('href="'.$baseurl,'href="'.$mysite_url,$post['post_content']);

            }

            if($post['post_type'] == 'page') {
                $page_ids[] = $post['ID'];
                $pagecount++;
            }

            if($post['post_type'] == 'rsvpmaker') {
                error_log('Processing RSVP post: '.$post['ID'].' '.$post['post_title']);
                if(!function_exists('get_rsvpversion'))
                    continue; // rsvpmaker not available, skip this post
                $event = array('event'=>$post['ID']);

                $event['date'] = $post['date'];

                $event['enddate'] = $post['enddate'];

                $event['ts_start'] = $post['ts_start'];

                $event['ts_end'] = $post['ts_end'];

                $event['timezone'] = $post['timezone'];

                $event['display_type'] = $post['display_type'];

                $event['post_title'] = $post['post_title'];
                error_log('extracting event data: '.var_export($event,true));

                $cloneoutput .= '<p>Extracting event data: '.var_export($event,true).'</p>';
                $result = $wpdb->replace($event_table,$event);
                error_log('insert result: '.$result .' last error '.$wpdb->last_error);
                
                //remove unnecessary fields

                $post = array(

                    'ID' => $post['ID'],

                    'post_type' => 'rsvpmaker',

                    'post_name' => $post['post_name'],

                    'post_date' => $post['post_date'],

                    'post_date_gmt' => $post['post_date_gmt'],

                    'post_modified' => $post['post_modified'],

                    'post_modified_gmt' => $post['post_modified_gmt'],

                    'post_excerpt' => $post['post_excerpt'],

                    'post_author' => $post['post_author'],

                    'post_title' => $post['post_title'],

                    'post_content' => $post['post_content'],

                    'post_status' => 'publish',

                );

            }

            $cloneoutput .= '<p>Inserting post: '.$post['ID'].' '.$post['post_title'].' '.$post['post_type'].'</p>';
            unset($post['filter']);  
            $result = $wpdb->replace($wpdb->posts,$post);
            error_log('Result of post insert: '.var_export($result,true));
            error_log('Last error: '.var_export($wpdb->last_error,true));

            if(!$result) {
                $cloneoutput .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';
            }
            else {
                update_post_meta($post['ID'],'cloned',1);
            }
        }

        update_option('playground_sync_date',date('Y-m-d H:i:s'));
        do_action('quickplayground_clone',$url,$clone);
        update_option('playground_downloaded',true);
        update_option('pages_cloned',$pagecount);

        if(!empty($clone['thumbnails']) && is_array($clone['thumbnails'])) {
            $retry = [];
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            foreach($clone['thumbnails'] as $id_img) {
                $cloneoutput .= sprintf('<p>Downloaded Thumbnail %s</p>',var_export($id_img,true));
                if(empty($id_img['guid']))
                    continue;
                try {
                $id = media_sideload_image($id_img['guid'], $id_img['post_id'], 'cloned thumbail', 'id');
                if($id) {
                $result = add_post_meta($id_img['post_id'],'_thumbnail_id',$id);                    
                $cloneoutput .= sprintf('<p>Downloaded Thumbnail %s, setting thumbnail for %d to %d %s</p>',$id_img['guid'],$id_img['post_id'],$id,var_export($result,true));
                }
                else
                    $cloneoutput .= sprintf('<p>Error downloading thumbnail %s for %d, returned %s</p>',$id_img['guid'],$id_img['post_id'],var_export($id,true));
                    $retry[] = $id_img;
                }
                catch (Exception $e) {
                    $cloneoutput .= sprintf('<p>Error downloading thumbnail %s %s</p>',$id_img['guid'],$e->getMessage());
                    error_log($id_img['guid'] .' download error '.$e->getMessage());
                    $retry[] = $id_img;
                }
                }
        }
            
    //second try, downloading thumbnails
    if(!empty($retry)) {
        foreach($retry as $id_img) {
            $id = media_sideload_image($id_img['guid'], $id_img['post_id'], 'cloned thumbail', 'id');
            if($id) {
            $result = add_post_meta($id_img['post_id'],'_thumbnail_id',$id);                    
            $cloneoutput .= sprintf('<p>On retry, downloaded Thumbnail %s, setting thumbnail for %d to %d %s</p>',$id_img['guid'],$id_img['post_id'],$id,var_export($result,true));
            }
            else
                $cloneoutput .= sprintf('<p>On retry, error downloading thumbnail %s for %d, returned %s</p>',$id_img['guid'],$id_img['post_id'],var_export($id,true));        
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

    $response2 = wp_remote_get($taxurl);
    if(is_wp_error($response2)) {
        $cloneoutput .=  '<p>Error: '.htmlentities($response2->get_error_message()).'</p>';
        error_log('Error retrieving clone data: '.$response2->get_error_message());
        update_option('quickplayground_log', $cloneoutput);
        return $cloneoutput;
    } 

    $clone = json_decode($response2['body'],true);

        if(!empty($clone['postmeta'])) {

            foreach($clone['postmeta'] as $meta) {

                $result = $wpdb->replace($wpdb->postmeta,$meta);

                if(!$result) {

                    $cloneoutput .= '<p>Error: '.htmlentities($wpdb->last_error).'</p>';

                }

            }

        }

    if(!empty($clone['termmmeta'])) {

        foreach($clone['termmeta'] as $meta) {

            $result = $wpdb->replace($wpdb->termmeta,$meta);

            if(!$result) {

                $cloneoutput .= '<p>Error: termmeta '.htmlentities($wpdb->last_error).'</p>';

            }

        }

    }

    if(!empty($clone['terms'])) {

        foreach($clone['terms'] as $row) {

            $result = $wpdb->replace($wpdb->terms,$row);
            $cloneoutput .= "<p>$wpdb->last_query</p>";

            if(!$result) {

                $cloneoutput .= '<p>Error: terms '.htmlentities($wpdb->last_error).'</p>';

            }

        }

    }

    if(!empty($clone['term_relationships'])) {
        $cloneoutput .= sprintf('<p>%d term_relationships',sizeof($clone['term_relationships']));
        foreach($clone['term_relationships'] as $row) {

            $result = $wpdb->replace($wpdb->term_relationships,$row);
            $cloneoutput .= "<p>$wpdb->last_query</p>";

            if(!$result) {
                $cloneoutput .= '<p>Error: term_relationships '.htmlentities($wpdb->last_error).'</p>';
            }
        }
    }

    if(!empty($clone['term_taxonomy'])) {
        foreach($clone['term_taxonomy'] as $row) {
            $result = $wpdb->replace($wpdb->term_taxonomy,$row);
            $cloneoutput .= "<p>$wpdb->last_query</p>";
            if(!$result) {
                $cloneoutput .= '<p>Error: term_taxonomy '.htmlentities($wpdb->last_error).'</p>';
            }
        }
    }

    //$json2 = json_encode(json_decode($response['body']), JSON_PRETTY_PRINT);
    $cloneoutput = apply_filters('playground_clone_received_by_playground_end',$cloneoutput,$clone);

    if(function_exists('future_toastmaster_meetings')) {
        $future = future_toastmaster_meetings(1);
        if(!empty($future)) {
        $next = $future[0]->ID;
        $rolekeys = array('_role_Speaker_1','_role_Speaker_2','_role_Speaker_3','_role_Toastmaster_of_the_Day_1','_role_Timer_1','_role_Grammarian_1','_role_Ah_Counter_1','_role_General_Evaluator_1','_role_Evaluator_1','_role_Evaluator_2','_role_Evaluator_3','_role_Table_Topic_Master_1');

        $cloneoutput .= sprintf('<p>Before add users first event %d</p>',$clone['next_event']);

        $names = array('George Test','Abraham Test','Teddy Test','Susan Test','John Test','Thomas Test','Thomas Test Jr.','Jackie Test');

        $password = wp_generate_password();

        foreach($names as $name) {

            $firstlast = explode(' ',$name);

            $slug = str_replace(' ','_',strtolower($name));

            $u = array('user_login' => $slug ,'user_email' => $slug.'@example.com', 'user_pass' => $password,'display_name' => $name,'first_name' => $firstlast[0] ,'last_name' => $firstlast[1] );

            $cloneoutput .= '<p>Creating user '.var_export($u,true)."<p>\n"; 

            $id = wp_insert_user($u);
                if($next) {
                    $role = array_shift($rolekeys);

                    if($role === null) {

                        continue;

                    }

                    $cloneoutput .= '<p>Adding role '.$role.' to user '.$id.'</p>';

                    update_post_meta($next, $role, $id);
                }
            }   
        }
    }
    update_option('quickplayground_log', $cloneoutput);
   return $cloneoutput;
}

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