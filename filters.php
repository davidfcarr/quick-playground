<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Adds RSVPmaker event data to the playground clone if RSVPmaker is active.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with RSVPmaker events.
 * $clone = apply_filters('qckply_qckply_clone_posts',$clone);
 */
add_filter('qckply_qckply_clone_posts','qckply_qckply_clone_rsvpmakers', 10, 2);
function qckply_qckply_clone_rsvpmakers($clone, $settings) {
    error_log('qckply_qckply_clone_rsvpmakers copy events '.intval($settings['copy_events']));
    $was = sizeof($clone['posts']);
    if(!empty($settings['copy_events']) && function_exists('rsvpmaker_get_future_events')) {
        $clone['next_event'] = 0;
        $clone['rsvpmakers'] = [];
        $rsvpmakers = rsvpmaker_get_future_events(['limit'=>intval($settings['copy_events'])]);
        error_log('retrived rsvpmakers '.sizeof($rsvpmakers));
        if(!empty($rsvpmakers) ) {
            $clone['next_event'] = $rsvpmakers[0]->ID;
            foreach($rsvpmakers as $r) {
                $clone['ids'][] = $r->ID;
                $post = array(
                    'ID' => $r->ID,
                    'post_type' => 'rsvpmaker',
                    'post_name' => $r->post_name,
                    'post_date' => $r->post_date,
                    'post_date_gmt' => $r->post_date_gmt,
                    'post_modified' => $r->post_modified,
                    'post_modified_gmt' => $r->post_modified_gmt,
                    'post_excerpt' => $r->post_excerpt,
                    'post_author' => $r->post_author,
                    'post_title' => $r->post_title,
                    'post_content' => $r->post_content,
                    'post_status' => 'publish',
                );
                $clone['posts'][] = $post;
            }
        }
    }
    if(!empty($settings['demo_rsvpmakers']) && is_array($settings['demo_rsvpmakers']) && function_exists('rsvpmaker_get_future_events')) {
      foreach($settings['demo_rsvpmakers'] as $r) {
        $r = intval($r);
        if($post = get_post($r)) {
        $clone['ids'][] = $r;
        $clone['posts'][] = (array) $post;
        }
    }
}
    $diff = sizeof($clone['posts']) - $was;
    error_log('added '.$diff.' rsvpmaker posts ');
    return $clone;
}

add_filter('qckply_clone_custom','rsvpmaker_qckply_clone_custom',10,2);
/**
 * $clone = apply_filters('qckply_qckply_clone_custom',$clone,$ids);
 */
function rsvpmaker_qckply_clone_custom($clone, $ids) {
    global $wpdb;
    $table = $wpdb->prefix.'rsvpmaker_event';
    $clone['custom_tables']['wp_rsvpmaker_event'] = [];
    $clone['custom_tables']['wp_rsvpmaker'] = [];
    $t = time();
    $rsvp_id = 1;
    $events = [];
    foreach($ids as $id) {
        if('rsvpmaker' == get_post_type($id)) {
            $event = $wpdb->get_row("SELECT * FROM $table where event=".intval($id),ARRAY_A);
            if(!empty($event)) {
                $events[] = $event;
                for($loop = 0; $loop < rand(1, 10); $loop++) {
                    $person = qckply_fake_user(0);
                    $rsvp['id'] = $rsvp_id++;
                    $rsvp['first'] = $person['first_name'];
                    $rsvp['last'] = $person['last_name'];
                    $rsvp['email'] = $person['user_email'];
                    $rsvp['event'] = $id;
                    $rsvp['yesno'] = 1;
                    $rsvp['timestamp'] = date('Y-m-d H:i:s', $t - (DAY_IN_SECONDS * ($loop + 1)));
                    $details = array_merge($rsvp,['mobile_phone'=>'954-555-1212']);
                    $rsvp['details'] = serialize($details);
                    $clone['custom_tables']['wp_rsvpmaker'][] = $rsvp;
                }
            }
        }
    }
    $clone['custom_tables']['wp_rsvpmaker_event'] = $events;
    return $clone;
}

add_filter('qckply_custom_qckply_clone_receiver','rsvpmaker_qckply_custom_qckply_clone_receiver');
function rsvpmaker_qckply_custom_qckply_clone_receiver($clone) {
    if(empty($clone['custom_tables']) || empty($clone['custom_tables']['wp_rsvpmaker_event']))
        return $clone;
    $t = time();
    $events = $clone['custom_tables']['wp_rsvpmaker_event'];
    $events = array_filter($events, function($event) { return !empty($event['event']) && !empty($event['ts_start']); } );
    $clone['custom_tables']['wp_rsvpmaker_event'] = [];
    usort($events,'qckply_rsvpmaker_datesort');
    $addtime = 0;
    $r = $events[0];
    if($r['ts_start'] < $t) {
        $diff = $t - $r['ts_start'];
        $weeks = ceil($diff / WEEK_IN_SECONDS) + 1;
        $addtime = ($weeks > 6) ? 52 * WEEK_IN_SECONDS : $weeks * WEEK_IN_SECONDS;
        $clone['addtime'] = $addtime;
    }
    foreach($events as $index => $event) {
        $event = (array) $event;
        if($addtime && ($event['ts_start'] < $t)) {
            $event['ts_start'] += $addtime;
            $event['date'] = rsvpmaker_date('Y-m-d H:i:s',$event['ts_start']);
            $event['ts_end'] += $addtime;
            $event['enddate'] = rsvpmaker_date('Y-m-d H:i:s',$event['ts_end']);
            $clone['output'] .= $out;
        }
        $clone['custom_tables']['wp_rsvpmaker_event'][] = $event;
        if(function_exists('rsvpmakers_add'))
            rsvpmakers_add((object) $event);
    }
    return $clone;
}


function qckply_rsvpmaker_datesort($a, $b) {
    if(empty($a['ts_start']) || empty($b['ts_start']))
        return -1;
    return ($a['ts_start'] > $b['ts_start']) ? 1 : -1;
}

/**
 * Adds RSVPmaker options to the list of settings to copy into the playground blueprint.
 *
 * @param array $settings_list The list of settings to copy.
 * @return array Modified settings list.
 * $settings_to_copy = apply_filters('qckply_settings_to_copy',$settings_list);
 */
add_filter('qckply_settings_to_copy','rsvpmaker_settings_for_playground');
function rsvpmaker_settings_for_playground($settings_list) {
    if(function_exists('rsvpmaker_get_future_events')) {
        $settings_list[] = 'RSVPMAKER_Options';
    }
    return $settings_list;
}

//        $data = apply_filters('qckply_settings_content',$data,$setting);
add_filter('qckply_settings_content','qckply_rsvpmaker_settings_filter',10,2);
function qckply_rsvpmaker_settings_filter($data,$setting) {
    if('RSVPMAKER_Options' == $setting) {
        unset($data['smtp_password']);
        unset($data['smtp_server']);
        unset($data['smtp_useremail']);
        unset($data['smtp_username']);
    }
    return $data;
}

add_filter('qckply_clone_save_posts','qckply_clone_save_rsvpmaker_posts',10,1);

/**
 * Adds RSVPMaker event date and time details. Runs within the API process for downloading from the live site to the playground 
 *
 * @param array     $clone array of posts and related content
 * @return array    Modified $clone array with rsvpmakers (event parameters) added.
 */
function qckply_clone_save_rsvpmaker_posts($clone) {
    if(function_exists('rsvpmaker_get_future_events')) {
        $clone['rsvpmakers'] = [];
        foreach($clone['posts'] as $post) {
            $post = (array) $post;
            if($post['post_type'] == 'rsvpmaker') {
                $clone['rsvpmakers'][] = (array) get_rsvpmaker_event($post['ID']);
            }
        }
    }
    return $clone;
}

/**
 * Adds RSVPMaker events to settings array 
 *
 * @param array $settings_list The list of settings to copy.
 * @return array               Modified $settings array.
 * triggered by $settings = apply_filters('qckply_new_settings',$settings, $postvars);
*/

add_filter('qckply_new_settings','qckply_new_settings_rsvpmaker',10,2);
function qckply_new_settings_rsvpmaker($settings, $postvars) {
    if(isset($postvars['demo_rsvpmakers']) && is_array($postvars['demo_rsvpmakers'])) {
    error_log('demo_rsvpmakers for settings: '.print_r($postvars['demo_rsvpmakers'], true));
        foreach($postvars['demo_rsvpmakers'] as $i => $id) {
        if(!empty($id)) {
            $settings['demo_rsvpmakers'][] = intval($id);
        }
    }
    }
return $settings;
}

add_action('qckply_form_demo_content','qckply_form_demo_rsvpmaker_content',10,1);
/**
 * Adds RSVPMaker fields to form 
 *
 * @param array $settings      The list of settings to to determine what should be displayed.
*/

function qckply_form_demo_rsvpmaker_content($settings) {
if(!function_exists('get_rsvpmaker_event_table'))
  return;
$copy_events = (!isset($settings['copy_events'])) ? 10 : intval($settings['copy_events']);
echo '<h3>RSVPmaker Events</h3>';
echo '<p>Copy <input type="number" name="settings[copy_events]" value="'.$copy_events.'" style="width:5em" > Future RSVPMaker events </p>'; 
if(!empty($settings['demo_rsvpmakers']) && is_array($settings['demo_rsvpmakers'])) {
  foreach($settings['demo_rsvpmakers'] as $r) {
    $event = get_rsvpmaker_event($r);
    printf('<p>Keep Event: <input type="checkbox" name="demo_rsvpmakers[]" value="%s" checked="checked" /> %s </p>',intval($r),esc_html($event->post_title));
  }
}
$events_dropdown = get_events_dropdown ();
  for($i = 0; $i < 10; $i++) {
  $classAndID = ($i > 0) ? ' class="hidden_item rsvpmaker" id="rsvpmaker_'.$i.'" ' : ' class="rsvpmaker" id="rsvpmaker_'.$i.'" ';
  printf('<p%s>Demo Event: <select class="select_with_hidden" name="demo_rsvpmakers[]">%s</select></p>'."\n",wp_kses($classAndID, qckply_kses_allowed()),'<option value="">Choose Event</option>'.wp_kses($events_dropdown, qckply_kses_allowed()));
  }
  printf('<p><input type="radio" name="settings[rsvpmaker_prompt]" value="1" %s /> %s <input type="radio" name="settings[rsvpmaker_prompt]" value="0" %s /> %s </p>', empty($settings['rsvpmaker_prompt']) ? '' : ' checked="checked" ',esc_html__('SHOW','quick-playground'), !empty($settings['rsvpmaker_prompt']) ? '' : ' checked="checked" ', esc_html__('DO NOT SHOW RSVPMaker help prompts in demo.','quick-playground' ) );
}

add_action('wp_footer','qckply_clone_footer_message');
add_action('admin_footer','qckply_clone_footer_message');
function qckply_clone_footer_message() {
    if(isset($_GET['qckply_clone'])) {
        echo qckply_footer_prompt();
        return;
    }
    if(!get_option('is_qckply_clone')) {
        return;
    }
    $slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : basename(sanitize_text_field($_SERVER['REQUEST_URI']));
    if(is_home() || is_front_page())
        $slug = 'home';
    elseif(('index.php' == $slug) || ('index.php' == $slug))
        $slug = 'dashboard';
    $slug = preg_replace('/[^a-z0-9\_]/','-',$slug);
    $keymessage = array('key'=>$slug,'message'=>'','welcome'=> is_admin() ? 'admin-welcome' : 'welcome');
    $keymessage = apply_filters('qckply_key_message',$keymessage);
    if(!empty($keymessage['message'])) {
    ?>
<div id="playground-overlay-message">
  <span><p><strong>Playground Prompt</strong></p>
    <?php
        echo wpautop(wp_kses_post($keymessage['message']));
  ?>
  </span>
  <button id="playground-overlay-close">&times;</button>
</div>
<?php
    }
}

add_filter('qckply_key_message','qckply_admin_message');

function qckply_admin_message($keymessage) {
    if(('quickplayground' == $keymessage['key'] || 'qckply_builder' == $keymessage['key'])  && !playground_premium_enabled()) {
        $keymessage['message'] = sprintf('Visit the <a href="%s">Playground Pro page</a> to unlock your 30-day trial of enhanced customization features (and the ability to add these little messages to your demos).',admin_url('admin.php?page=qckply_pro'));
    }
    return $keymessage;
}

add_action('http_api_debug','qckply_http_log',10,5);
function qckply_http_log($response, $context, $class, $parsed_args, $url) {
    if(qckply_is_playground()) {
        error_log('called '.$url .' context '.var_export($context,true).' args '.var_export($parsed_args,true));
    }
}

//add_filter('the_content','qckply_the_content');

function qckply_the_content($content) {
    if(qckply_is_playground() && isset($_GET['qckply_clone'])) {
        $target = $_GET['qckply_clone'];
        $output = '';
        $permalink = get_permalink();
        ob_start();
        if('images' == $target) {
            $more = qckply_clone_images('images');
            $output = ob_get_clean();
            if($more) {
                return $content.'<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.$permalink.'?qckply_clone=thumbnails"</script>';
            }
            return $content;//.'<div id="playground-overlay-message"><p>Loading more...</p></div><script>window.location.href="'.$permalink.'?qckply_clone=thumbnails"</script>';
        } 
        elseif('thumbnails' == $target) {
            $more = qckply_get_more_thumbnails();
            if($more) {
                return $content.'<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.$permalink.'?qckply_clone=thumbnails"</script>';
            }
            $output = ob_get_clean();
            qckply_top_ids();
            return $content.'<div id="playground-overlay-message"><p>Done</p></div><script>window.location.href="'.$permalink.'"</script>';
        }
        else {
            qckply_clone( 'settings' );
            qckply_clone( 'taxonomy' );
            qckply_clone( 'custom' );
            $output = ob_get_clean();
            return $content.'<div id="playground-overlay-message"><p>Loading images ...</p></div><script>window.location.href="'.$permalink.'?qckply_clone=images"</script>';
        }
        //update_option('qckply_sync_date',date('Y-m-d H:i:s'));
    }
    return $content;
}

function qckply_footer_prompt() {
    if(qckply_is_playground() && isset($_GET['qckply_clone'])) {
        $target = $_GET['qckply_clone'];
        $output = '';
        ob_start();
        if('images' == $target) {
            $more = qckply_clone_images('images');
            $output = ob_get_clean();
            if($more) {
                return '<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.qckply_link(['qckply_clone'=>'thumbnails']).'"</script>';
            }
            return $content;//.'<div id="playground-overlay-message"><p>Loading more...</p></div><script>window.location.href="'.$permalink.'?qckply_clone=thumbnails"</script>';
        } 
        elseif('thumbnails' == $target) {
            $more = qckply_get_more_thumbnails();
            if($more) {
                return '<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.qckply_link(['qckply_clone'=>'thumbnails']).'"</script>';
            }
            $output = ob_get_clean();
            qckply_top_ids();
            return '<div id="playground-overlay-message"><p>Done</p></div><script>window.location.href="'.qckply_link().'"</script>';
        }
        else {
            qckply_clone( 'settings' );
            qckply_clone( 'taxonomy' );
            qckply_clone( 'custom' );
            $output = ob_get_clean();
            return '<div id="playground-overlay-message"><p>Loading images ...</p></div><script>window.location.href="'.qckply_link($args = ['qckply_clone'=>'images']).'"</script>';
        }
        //update_option('qckply_sync_date',date('Y-m-d H:i:s'));
    }
    //return $content;
}

