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
	global $wpdb, $playground_uploads;
    $profile = sanitize_text_field($request['profile']);
    if(empty($_GET['nocache'])) {
      $savedfile = $playground_uploads.'/quickplayground_posts_'.$profile.'.json';
      if(file_exists($savedfile) && !isset($_GET['refresh'])) {
      $json = file_get_contents($savedfile);
      if($json && $clone = json_decode($json)) {
        //$clone['savedfile'] = $savedfile;
        return new WP_REST_Response($clone, 200);
      }
    }
    }
    
    $clone['client_ip'] = $_SERVER['REMOTE_ADDR'];
    $clone['profile'] = $profile;
    $clone['settings'] = get_option('playground_clone_settings_'.$profile,array());
    $clone['settings']['playground_premium'] = (strpos($profile,'-publicdemo')) ? false : playground_premium_enabled();
    $clone['site_icon_url'] = get_site_icon_url();
    $clone['settings']['timezone_string'] = get_option('timezone_string');
    if($clone['playground_premium'] && !strpos($profile,'-publicdemo')) {
      $playground_sync_code = wp_generate_password(24,false);
      update_option('playground_sync_code', $playground_sync_code);
      $clone['settings']['playground_sync_code'] = $playground_sync_code;
    }
    $template_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
    $header_content = (empty($template_part->content)) ? '' : $template_part->content;
    $clone['nav_id'] = 0;
    if($header_content) {
    preg_match('/"ref":([0-9]+)/',$header_content,$match);
    if(!empty($match[1]))
        $clone['nav_id'] = $match[1];
    }
    $moretypes = '';
    if(!empty($clone['settings']['post_types']) && is_array($clone['settings']['post_types']))
    {
      foreach($clone['settings']['post_types'] as $t)
        $t = sanitize_text_field($t);
        $moretypes .= " OR `post_type` = '$t' ";
    }

    $sql = "SELECT * FROM $wpdb->posts WHERE post_status='publish' AND (`post_type` = 'rsvpmaker_form' OR `post_type` = 'rsvpmaker_template' OR `post_type` = 'wp_block' OR `post_type` = 'wp_global_styles' OR `post_type` = 'wp_navigation' OR `post_type` = 'wp_template' OR `post_type` = 'wp_template_part' $moretypes)";
    error_log(var_export($clone['settings'],true));
    error_log('playground sql '.$sql);
    $posts = $wpdb->get_results($sql);
    if(!empty($clone['settings']['copy_blogs'])) {
        $blogs = get_posts(array('numberposts'=>intval($clone['settings']['copy_blogs'])));
        $posts = array_merge($blogs, $posts);
    }
    if(!empty($clone['settings']['copy_pages'])) {
      $pages = get_posts(array('post_type'=>'page','numberposts'=>-1));
      $posts = array_merge($pages, $posts);
    }
    if(!empty($clone['settings']['key_pages'])) {
      $pages = quickplayground_key_pages();
      $posts = array_merge($pages, $posts);
    }
    
    $clone['ids'] = array();
    foreach($posts as $index => $post) {
      if(in_array($post->ID,$clone['ids']))
        $posts[$index] = null;
      else
        $clone['ids'][] = $post->ID;
    }
    $posts = array_filter($posts); // remove nulls

    if($clone['settings']['page_on_front'] && !in_array($clone['settings']['page_on_front'], $clone['ids'])) {
        $page = get_post($clone['settings']['page_on_front']);
        $clone['ids'][] = intval($clone['settings']['page_on_front']);
        if($page) {
            $page->post_status = 'publish'; // ensure it is published, even if it was copied from a draft
            $posts[] = $page;
        }
    }
    
    if(!empty($clone['settings']['demo_pages']) && is_array($clone['settings']['demo_pages'])) {
      if($clone['settings']['make_menu']) {
        $clone['make_menu'] = true;
        $clone['make_menu_ids'] = $clone['settings']['demo_pages'];
      }
      $clone['demo_pages'] = [];
        foreach($clone['settings']['demo_pages'] as $page_id) {
            if(in_array($page_id, $clone['ids']))
              continue; // already included
            $page = get_post($page_id);
            if($page) {
                $page->post_status = 'publish'; // ensure it is published
                $clone['demo_pages'][] = $page;
                $clone['ids'][] = $page->ID;
            }
      }
    }
    elseif(!empty($clone['settings']['make_menu'])) {
        $clone['make_menu'] = true;
    }

    if(!empty($clone['settings']['demo_posts']) && is_array($clone['settings']['demo_posts'])) {
      $clone['demo_posts'] = [];
        foreach($clone['settings']['demo_posts'] as $id) {
            if(in_array($id, $clone['ids']))
              continue; // already included
            $p = get_post($id);
            if($p) {
                $p->post_status = 'publish'; // ensure it is published
                $clone['demo_posts'][] = $p;
                $clone['ids'][] = $p->ID;
            }
      }
    }

    $clone['posts'] = $posts;
    $sql = "SELECT post_id, guid FROM $wpdb->postmeta meta JOIN $wpdb->posts posts ON meta.meta_value = posts.ID WHERE meta.post_id IN (".implode(',',$clone['ids']).") and meta.meta_key='_thumbnail_id' ";
    $clone['thumbnails'] = $wpdb->get_results($sql);
    $clone = apply_filters('quickplayground_playground_clone_posts',$clone);
    set_transient('playground_ids',$clone['ids'],HOUR_IN_SECONDS);
    return new WP_REST_Response($clone, 200);
}
}

class Quick_Playground_Clone_Taxonomy extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_taxonomy/(?P<profile>[a-z0-9_]+)';

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
  global $playground_uploads;
  require('getmenus.php');
	global $wpdb;
  $profile = $request['profile'];
  $savedfile = $playground_uploads.'/quickplayground_meta_'.$profile.'.json';
  
  if(file_exists($savedfile)) {
    $json = file_get_contents($savedfile);
    if($json && $clone = json_decode($json))
      return new WP_REST_Response($clone, 200);
  }
  
    $clone = [];
    $clone['savedfile'] = $savedfile;
    $clone['ids'] = get_transient('playground_ids', array());
    $clone = quickplayground_get_category_data($clone);
    $clone = quickplayground_get_menu_data($clone);
    $clone['postmeta'] = quickplayground_postmeta($clone['ids']);
    $blog_id = get_current_blog_id();
    $blogusers = get_users(
      array(
        'blog_id' => $blog_id,
        'orderby' => 'display_name',
      )
    );
    $one = false;
    $user_ids = [];
    $clone['users'] = $clone['usermeta'] = [];
    foreach($blogusers as $user) {
      if($user->ID == 1) {
        $one = true;
      }
      else {
        $user_ids[] = $user->ID;
        $clone['users'][] = quickplayground_fake_user($user->ID);
      }
    }
    if(!empty($user_ids))
    if($one) {
        $fake = quickplayground_fake_user(1);
        $clone['adminuser']['first_name'] = $fake['first_name'];
        $clone['adminuser']['last_name'] = $fake['last_name'];
    }
    $clone = apply_filters('quickplayground_playground_clone_meta',$clone);

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
    quickplayground_playground_zip_plugin("quick-playground");
    $blueprint = get_option('playground_blueprint_'.$request['profile']);
    if (!$blueprint) {
        return new WP_REST_Response(array('error'=>'blueprint_not_found'), 404);
    }
    if(isset($_GET['stylesheet'])) {
      $blueprint = quickplayground_swap_theme($blueprint, sanitize_text_field($_GET['stylesheet']));
    }
    if(!empty($_GET['key'])) {
      $blueprint = quickplayground_change_blueprint_setting($blueprint, array('is_demo'=>false,'playground_premium'=>sanitize_text_field($_GET['key'])));
    }
    else {
        $blueprint = quickplayground_change_blueprint_setting($blueprint, array('is_demo'=>true,'playground_premium'=>false));
    }

    if(playground_premium_enabled())
      $blueprint = apply_filters('quickplayground_blueprint',$blueprint);
    $t = time();
    $json = str_replace('TIMESTAMP',$t,json_encode($blueprint));
    return new WP_REST_Response(json_decode($json), 200);
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

class Quick_Playground_Save_Posts extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_posts/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

	public function get_items_permissions_check($request) {

	  return true;

	}

  public function get_items($request) {

	global $wpdb, $playground_uploads;
    $profile = $request['profile'];
    $savedfile = $playground_uploads.'/quickplayground_posts_'.$profile.'.json';
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_posts',$data);
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

class Quick_Playground_Save_Meta extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_meta/(?P<profile>[a-z0-9_]+)';

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
	global $wpdb, $playground_uploads;
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_meta',$data);
    $savedfile = $playground_uploads.'/quickplayground_meta_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

class Quick_Playground_Save_Image extends WP_REST_Controller {

	public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_image';

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
	global $wpdb, $playground_uploads;
    $profile = $request['profile'];
    $playground_saved = get_option('playground_saved_images',array());
    $data = $request->get_json_params();
    if(is_string($data))
      $data = json_decode($data);
    $savedfile = $playground_uploads.$data->path;
    $sync_response['savedfile'] = $savedfile;
    $ext = pathinfo($savedfile,PATHINFO_EXTENSION);
    $allowed = array('jpg','jpeg','jpe',
			'gif',
			'png',
			'bmp',
			'tiff|tif',
			'webp',
			'avif',
			'ico');
    if(!in_array($ext,$allowed)) {
      $sync_response['error'] = 'Not an allowed type';
    }
    elseif(file_exists($savedfile))
    {
      $sync_response['error'] = 'File exists';
    } else {
      $handle = fopen($savedfile,'wb');
      $filedata = base64_decode($data->base64);
      $result = fwrite($handle,$filedata);
      if($result) {
        $sync_response['written'] = $result;
        if(file_is_valid_image($savedfile)) {
          $sync_response['image_validated'] = true;
          $playground_saved[] = $data->path;
          $sync_response['file'] = $savedfile;
        }
        else {
          $sync_response['image_validated'] = false;
          unlink($savedfile);
          $sync_response['error'] = 'Failed to validate image';
        }
      }
    }
    $sync_response['saved'] = $playground_saved;
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
  $hook = new Quick_Playground_Save_Posts();
	$hook->register_routes();           
  $hook = new Quick_Playground_Save_Meta();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Save_Image();
	 $hook->register_routes();           

} );

