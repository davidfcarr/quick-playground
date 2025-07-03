<?php

/**
 * Adds RSVPmaker event data to the playground clone if RSVPmaker is active.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with RSVPmaker events.
 * $clone = apply_filters('quickplayground_playground_clone_posts',$clone);
 */
add_filter('quickplayground_playground_clone_posts','quickplayground_playground_clone_rsvpmakers', 10, 2);
function quickplayground_playground_clone_rsvpmakers($clone, $settings) {
    if(!empty($settings['copy_events']) && function_exists('rsvpmaker_get_future_events')) {
        $clone['next_event'] = 0;
        $clone['rsvpmakers'] = [];
        $rsvpmakers = rsvpmaker_get_future_events(['limit'=>intval($clone['settings']['copy_events'])]);
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
                $event = array('event'=>$post['ID']);
                $event['date'] = $r->date;
                $event['enddate'] = $r->date;
                $event['ts_start'] = $r->ts_start;
                $event['ts_end'] = $r->ts_end;
                $event['timezone'] = $r->timezone;
                $event['display_type'] = $r->display_type;
                $event['post_title'] = $r->post_title;
                $clone['rsvpmakers'][] = $event;
            }
        }
    }
    if(!empty($settings['demo_rsvpmakers']) && is_array($settings['demo_rsvpmakers']) && function_exists('rsvpmaker_get_future_events')) {
      foreach($settings['demo_rsvpmakers'] as $r) {
        $r = intval($r);
        if($post = get_post($r)) {
        $clone['ids'][] = $r;
        $clone['rsvpmakers'][] = (array) get_rsvpmaker_event($r);
        $clone['posts'][] = (array) $post;
        }
    }
}
    return $clone;
}

/**
 * Inserts RSVPmaker event data into the database during the playground cloning process. Runs within playground. Adds demo RSVPs to events
 *
 * @param array $clone The clone data array.
 * @return array Modified clone data array with output log.
 * $clone = apply_filters('playground_clone_posts',$clone);
 */
add_filter('playground_clone_posts','rsvpmaker_playground_clone');
function rsvpmaker_playground_clone($clone) {
    global $wpdb;
    $t = time();
    $clone['output'] .= '<p>checking for rsvpmaker events</p>';
    if(empty($clone['rsvpmakers']) || !function_exists('rsvpmaker_get_future_events')) {
        $clone['output'] .= '<p>no rsvpmaker events to clone</p>';
        return $clone;
    }
    else {
        $clone['output'] .= '<p>found '.sizeof($clone['rsvpmakers']).' to clone</p>';
    }
    $event_table = $wpdb->prefix.'rsvpmaker_event';
    usort($clone['rsvpmakers'],'quickplayground_rsvpmaker_datesort');
    $addtime = 0;
    $r = (array) $clone['rsvpmakers'][0];
    if($r['ts_start'] < $t) {
        $diff = $t - $r['ts_start'];
        $weeks = ceil($diff / WEEK_IN_SECONDS) + 1;
        $addtime = ($weeks > 6) ? 52 * WEEK_IN_SECONDS : $weeks * WEEK_IN_SECONDS;
    }
    foreach($clone['rsvpmakers'] as $index => $r) {
        $r = (array) $r; // Ensure $r is an array
        if($addtime) {
            $r['ts_start'] += $addtime;
            $r['date'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_start']);
            $r['ts_end'] += $addtime;
            $r['enddate'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_end']);
            $clone['output'] .= '<p>advancing date of '.$r['post_title'].' to '.date('r',intval($r['ts_start'])).'</p>';
        }
        rsvpmakers_add((object) $r);
        $result = $wpdb->replace($event_table, $r);
        $clone['output'] .= "<p>$wpdb->last_query</p>";
        if($result) {
            $t = time();
            if($i < 6) {
                $limit = 5 - $i;
                for($loop = 0; $loop < $limit; $loop++) {
                    $person = quickplayground_fake_user(0);
                    $rsvp['first'] = $person['first_name'];
                    $rsvp['last'] = $person['last_name'];
                    $rsvp['email'] = $person['user_email'];
                    $rsvp['event'] = $r['event'];
                    $rsvp['yesno'] = 1;
                    $rsvp['timestamp'] = date('Y-m-d H:i:s', $t - (DAY_IN_SECONDS * ($loop + 1)));
                    $rsvp['mobile_phone'] = '954-555-1212';
                    $rsvp['details'] = serialize($rsvp);
                    $wpdb->insert($wpdb->prefix.'rsvpmaker', $rsvp);
                }
            }
        }
        else {
            $clone['output'] .= '<p>Error: terms '.esc_html($wpdb->last_error).'</p>';
        }
    }
    return $clone;
}

function quickplayground_rsvpmaker_datesort($a, $b) {
    $a = (array) $a;
    $b = (array) $b;
    return ($a['ts_start'] > $b['ts_start']) ? 1 : -1;
}

/**
 * Adds RSVPmaker options to the list of settings to copy into the playground blueprint.
 *
 * @param array $settings_list The list of settings to copy.
 * @return array Modified settings list.
 * $settings_to_copy = apply_filters('playground_settings_to_copy',$settings_list);
 */
add_filter('playground_settings_to_copy','rsvpmaker_settings_for_playground');
function rsvpmaker_settings_for_playground($settings_list) {
    if(function_exists('rsvpmaker_get_future_events')) {
        $settings_list[] = 'RSVPMAKER_Options';
    }
    return $settings_list;
}

add_filter('quickplayground_clone_save_posts','quickplayground_clone_save_rsvpmaker_posts',10,1);

/**
 * Adds RSVPMaker event date and time details. Runs within the API process for downloading from the live site to the playground 
 *
 * @param array     $clone array of posts and related content
 * @return array    Modified $clone array with rsvpmakers (event parameters) added.
 */
function quickplayground_clone_save_rsvpmaker_posts($clone) {
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
 * triggered by $settings = apply_filters('quickplayground_new_settings',$settings, $postvars);
*/

add_filter('quickplayground_new_settings','quickplayground_new_settings_rsvpmaker',10,2);
function quickplayground_new_settings_rsvpmaker($settings, $postvars) {
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

add_action('quickplayground_form_demo_content','quickplayground_form_demo_rsvpmaker_content',10,1);
/**
 * Adds RSVPMaker fields to form 
 *
 * @param array $settings      The list of settings to to determine what should be displayed.
*/

function quickplayground_form_demo_rsvpmaker_content($settings) {
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
  printf('<p%s>Demo Event: <select class="select_with_hidden" name="demo_rsvpmakers[]">%s</select></p>'."\n",$classAndID,'<option value="">Choose Event</option>'.$events_dropdown);
  }
}