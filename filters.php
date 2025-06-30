<?php

/**
 * Adds RSVPmaker event data to the playground clone if RSVPmaker is active.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with RSVPmaker events.
 * $clone = apply_filters('quickplayground_playground_clone_posts',$clone);
 */
add_filter('quickplayground_playground_clone_posts','quickplayground_playground_clone_rsvpmakers');
function quickplayground_playground_clone_rsvpmakers($clone) {
    if(!empty($clone['settings']['copy_events']) && function_exists('rsvpmaker_get_future_events')) {
        $clone['next_event'] = 0;
        $clone['rsvpmakers'] = [];
        $rsvpmakers = rsvpmaker_get_future_events();
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
    return $clone;
}

/*
    Run within cloning process of playground
    
*/

/**
 * Inserts RSVPmaker event data into the database during the playground cloning process. Runs within playground.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with output log.
 * $clone = apply_filters('playground_clone_posts',$clone);
 */
add_filter('playground_clone_posts','rsvpmaker_playground_clone');
function rsvpmaker_playground_clone($clone) {
    global $wpdb;
    $t = time();
    $event_table = $wpdb->prefix.'rsvpmaker_event';
    if(!empty($clone['rsvpmakers'])) {
        foreach($clone['rsvpmakers'] as $r) {
            $r = (array) $r; // Ensure $r is an array
            if($r['ts_start'] < $t) {
                $diff = $r['ts_start'] - $t;
                $addweek = ceil($diff / WEEK_IN_SECONDS) * WEEK_IN_SECONDS;
                $r['ts_start'] += $addweek;
                $r['date'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_start']);
                $r['ts_end'] += $addweek;
                $r['enddate'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_end']);
            }
            rsvpmakers_add((object) $r);
            $result = $wpdb->replace($event_table, $r);
            $clone['output'] .= "<p>$wpdb->last_query</p>";
            if(!$result) {
                $clone['output'] .= '<p>Error: terms '.htmlentities($wpdb->last_error).'</p>';
            }
        }
    }
    return $clone;
}

/**
 * Adds RSVPmaker options to the list of settings to copy into the playground blueprint.
 *
 * @param array $settings_list The list of settings to copy.
 * @return array               Modified settings list.
 * $settings_to_copy = apply_filters('playground_settings_to_copy',$settings_list);
 */
add_filter('playground_settings_to_copy','rsvpmaker_settings_for_playground');
function rsvpmaker_settings_for_playground($settings_list) {
    if(function_exists('rsvpmaker_get_future_events')) {
        $settings_list[] = 'RSVPMAKER_Options';
    }
    return $settings_list;
}

add_action('quickplayground_form_demo_content','quickplayground_form_demo_rsvpmaker_content',10,1);

function quickplayground_form_demo_rsvpmaker_content($settings) {
if(!function_exists('get_rsvpmaker_event_table'))
  return;
$copy_events = (!isset($settings['copy_events'])) ? 10 : intval($settings['copy_events']);
echo '<h3>RSVPmaker Events</h3>';
echo '<p>Copy <input type="number" name="settings[copy_events]" value="'.$copy_events.'"> Future RSVPMaker events </p>'; 
$events_dropdown = get_events_dropdown ();
  for($i = 0; $i < 10; $i++) {
  $classAndID = ($i > 0) ? ' class="hidden_item rsvpmaker" id="rsvpmaker_'.$i.'" ' : ' class="rsvpmaker" id="rsvpmaker_'.$i.'" ';
  printf('<p%s>Demo Blog Post: <select class="select_with_hidden" name="demo_events[]">%s</select></p>'."\n",$classAndID,'<option value="">Choose Event</option>'.$events_dropdown);
  }
}