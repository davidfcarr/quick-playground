<?php
/*
Filters API output of cloned posts 
$clone = apply_filters('quickplayground_playground_clone_posts',$clone);
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
            //$posts[] = ;
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

                $event['date'] = $post['date'];

                $event['enddate'] = $post['enddate'];

                $event['ts_start'] = $post['ts_start'];

                $event['ts_end'] = $post['ts_end'];

                $event['timezone'] = $post['timezone'];

                $event['display_type'] = $post['display_type'];

                $event['post_title'] = $post['post_title'];
                $clone['rsvpmakers'][] = $event;
          }
        }
    }
    return $clone;
}

/*
    Run within cloning process of playground
    $clone = apply_filters('playground_clone_end',$clone);
*/

add_filter('playground_clone_end','rsvpmaker_playground_clone');
function rsvpmaker_playground_clone($clone) {
  global $wpdb;
  $t = time();
  $event_table = $wpdb->prefix.'rsvpmaker_event';
  if(!empty($clone['rsvpmakers'])) {
    foreach($clone['rsvpmakers'] as $r) {
      if($r['ts_start'] < $t) {
        $diff = $r['ts_start'] - $t;
        $addweek = ceil($diff / WEEK_IN_SECONDS) * WEEK_IN_SECONDS;
        $r['ts_start'] += $addweek;
        $r['date'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_start']);
        $r['ts_end'] += $addweek;
        $r['enddate'] = rsvpmaker_date('Y-m-d H:i:s',$r['ts_end']);
      }
      $result = $wpdb->replace($event_table, $r);
      $clone['output'] .= "<p>$wpdb->last_query</p>";
      if(!$result) {
          $clone['output'] .= '<p>Error: terms '.htmlentities($wpdb->last_error).'</p>';
      }
    }
  }
  return $clone;
}

/*
add to settings copied into blueprint
$settings_to_copy = apply_filters('playground_settings_to_copy',array('timezone_string'));
*/

add_filter('playground_settings_to_copy','rsvpmaker_settings_for_playground');
function rsvpmaker_settings_for_playground($settings_list) {
  if(function_exists('rsvpmaker_get_future_events')) {
    $settings_list[] = 'RSVPMAKER_Options';
  }
  return $settings_list;
}