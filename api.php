<?php

class Quick_Playground_Clone extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'playground_clone/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

	public function get_items_permissions_check($request) {

	  return true;

	}

  public function get_items($request) {
  require('getmenus.php');
	global $wpdb;
    $profile = sanitize_text_field($request['profile']);
    $clone['playground_premium'] = playground_premium_enabled();
    $clone['site_icon_url'] = get_site_icon_url();
    $clone['timezone_string'] = get_option('timezone_string');
    if(isset($_GET['user_id'])) {
      $playground_sync_code = wp_generate_password(24,false);
      set_transient('playground_sync_code', $playground_sync_code, HOUR_IN_SECONDS * 12);
      $clone['playground_sync_code'] = $playground_sync_code;
    }
    $settings = get_option('playground_clone_settings_'.$profile,array());
    $template_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
    $header_content = (empty($template_part->content)) ? '' : $template_part->content;
    $clone['nav_id'] = 0;
    if($header_content) {
    preg_match('/"ref":([0-9]+)/',$header_content,$match);
    if(!empty($match[1]))
        $clone['nav_id'] = $match[1];
    }
    $moretypes = '';
    if(!empty($settings['post_types']) && is_array($settings['post_types']))
    {
      foreach($settings['post_types'] as $t)
        $t = sanitize_text_field($t);
        $moretypes .= " OR `post_type` = '$t' ";
    }

    $sql = "SELECT * FROM $wpdb->posts WHERE post_status='publish' AND (`post_type` = 'rsvpmaker_form' OR `post_type` = 'rsvpmaker_template' OR `post_type` = 'wp_block' OR `post_type` = 'wp_global_styles' OR `post_type` = 'wp_navigation' OR `post_type` = 'wp_template' OR `post_type` = 'wp_template_part' $moretypes)";
    error_log(var_export($settings,true));
    error_log('playground sql '.$sql);
    $posts = $wpdb->get_results($sql);
    if(!empty($settings['copy_blogs'])) {
        $blogs = get_posts(array('numberposts'=>intval($settings['copy_blogs'])));
        $posts = array_merge($blogs, $posts);
    }
    if(!empty($settings['copy_pages'])) {
      $pages = get_posts(array('post_type'=>'page','numberposts'=>-1));
      $posts = array_merge($pages, $posts);
    }
    if(!empty($settings['key_pages'])) {
      $pages = quickplayground_key_pages();
      $posts = array_merge($pages, $posts);
    }
    
    $ids = array();
    foreach($posts as $index => $post) {
      if(in_array($post->ID,$ids))
        $posts[$index] = null;
      else
        $ids[] = $post->ID;
    }
    $posts = array_filter($posts); // remove nulls

    $clone['next_event'] = 0;

    if(!empty($settings['copy_events']) && function_exists('rsvpmaker_get_future_events')) {
        $rsvpmakers = rsvpmaker_get_future_events();
        if(!empty($rsvpmakers) ) {
            $clone['next_event'] = $rsvpmakers[0]->ID;
          foreach($rsvpmakers as $r) {
            $ids[] = $r->ID;
            $posts[] = $r;
          }
        }
    }

    if($settings['page_on_front'] && !in_array($settings['page_on_front'], $ids)) {
        $page = get_post($settings['page_on_front']);
        $ids[] = intval($settings['page_on_front']);
        if($page) {
            $page->post_status = 'publish'; // ensure it is published, even if it was copied from a draft
            $posts[] = $page;
        }
    }
    
    if(!empty($settings['demo_pages']) && is_array($settings['demo_pages'])) {
      if($settings['make_menu']) {
        $clone['make_menu'] = true;
        $clone['make_menu_ids'] = $settings['demo_pages'];
      }
      $clone['demo_pages'] = [];
        foreach($settings['demo_pages'] as $page_id) {
            if(in_array($page_id, $ids))
              continue; // already included
            $page = get_post($page_id);
            if($page) {
                $page->post_status = 'publish'; // ensure it is published
                $clone['demo_pages'][] = $page;
                $ids[] = $page->ID;
            }
      }
    }
    elseif($settings['make_menu']) {
        $clone['make_menu'] = true;
    }


    if(!empty($settings['demo_posts']) && is_array($settings['demo_posts'])) {
      $clone['demo_posts'] = [];
        foreach($settings['demo_posts'] as $id) {
            if(in_array($id, $ids))
              continue; // already included
            $p = get_post($id);
            if($p) {
                $p->post_status = 'publish'; // ensure it is published
                $clone['demo_posts'][] = $p;
                $ids[] = $p->ID;
            }
      }
    }

    $clone['posts'] = $posts;
    $sql = "SELECT post_id, guid FROM $wpdb->postmeta meta JOIN $wpdb->posts posts ON meta.meta_value = posts.ID WHERE meta.post_id IN (".implode(',',$ids).") and meta.meta_key='_thumbnail_id' ";
    $clone['thumbnails'] = $wpdb->get_results($sql);
    set_transient('playground_ids',$ids,HOUR_IN_SECONDS);
    if(playground_premium_enabled())
    $clone = apply_filters('quickplayground_design_playground_clone',$clone);    
    return new WP_REST_Response($clone, 200);
}
}

class Quick_Playground_Clone_Taxonomy extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_taxonomy';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

	public function get_items_permissions_check($request) {

	  return true;

	}

  public function get_items($request) {
  require('getmenus.php');
	global $wpdb;
    $ids = get_transient('playground_ids', array());
    $clone = quickplayground_get_category_data($ids);
    $clone = quickplayground_get_menu_data($clone);
    $clone['postmeta'] = quickplayground_postmeta($ids);
    return new WP_REST_Response($clone, 200);
}
}

class Quick_Playground_Blueprint extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'blueprint/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

	public function get_items_permissions_check($request) {

	  return true;

	}

  

  public function get_items($request) {
    $blueprint = get_option('playground_blueprint_'.$request['profile']);
    if(playground_premium_enabled())
      $blueprint = apply_filters('quickplayground_blueprint',$blueprint);
    if (!$blueprint) {
        return new WP_REST_Response(array('error'=>'blueprint_not_found'), 404);
    }
    return new WP_REST_Response($blueprint, 200);
	}

}

class Quick_Playground_Sync extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'sync';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET, POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

	public function get_items_permissions_check($request) {

	  return true;

	}

  

  public function get_items($request) {

	global $wpdb;

    $data = $request->get_json_params();
    $to = get_option('playground_premium_email', $post['email']);
    $subject = 'Request to sync design playground with '.$_SERVER['SERVER_NAME'];
    $confirm_url = admin_url('admin.php?page=quickplayground_sync');
    $body = '<p>Click this link to review and confirm changes from the playground as proposed changes to your live website: <a href="'.$confirm_url.'">Confirm Sync</a> (login required).</p>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail( $to, $subject, $body, $headers );

    set_transient('changes_from_playground', $data, HOUR_IN_SECONDS * 12);

    $sync_response['approval_required'] = '<p>Changes from the playground have been saved. Please check your email at '.$user->user_email.' for a confirmation link to allow these changes to be synced back to the website. '.$result.'</p>';

    $sync_response['data'] = $data;

    return new WP_REST_Response($sync_response, 200);
	}
}



add_action('rest_api_init', function () {

	 $hook = new Quick_Playground_Clone();

	 $hook->register_routes();           

	 $hook = new Quick_Playground_Clone_Taxonomy();

	 $hook->register_routes();           

   $hook = new Quick_Playground_Blueprint();

	 $hook->register_routes();           

  $hook = new Quick_Playground_Sync();

	 $hook->register_routes();           

} );

