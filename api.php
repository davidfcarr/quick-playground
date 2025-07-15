<?php
/**
 * REST controller for managing blueprints in the playground.
 */
class Quick_Playground_Blueprint extends WP_REST_Controller {

    /**
     * Registers REST API routes for blueprints.
     */
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

    /**
     * Permissions check for getting blueprint items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return true;
	}

    /**
     * Handles GET requests for retrieving blueprints.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
    quickplayground_playground_zip_plugin("quick-playground");
    $email = $key = '';
    $blueprint = get_option('playground_blueprint_'.$request['profile']);
    if (!$blueprint) {
        return new WP_REST_Response(array('error'=>'blueprint_not_found'), 404);
    }
    if(isset($_GET['stylesheet'])) {
      $blueprint = quickplayground_swap_theme($blueprint, sanitize_text_field($_GET['stylesheet']));
    }
    if(!empty($_GET['key'])) {
      $key = sanitize_text_field($_GET['key']);
      $email = is_multisite() ? get_blog_option(1,'playground_premium_email') : get_option('playground_premium_email');
      $blueprint = quickplayground_change_blueprint_setting($blueprint, array('playground_is_demo'=>false,'playground_premium_enabled'=>$key,'playground_premium_expiration'=>4070908800));
    }
    else {
      $blueprint = quickplayground_change_blueprint_setting($blueprint, array('playground_premium_enabled'=>false));
    }
    if(!empty($_GET['is_demo'])) {
      $blueprint = quickplayground_change_blueprint_setting($blueprint, array('playground_is_demo'=>true));
    }
    if(!empty($_GET['nocache'])) {
      $blueprint = quickplayground_change_blueprint_setting($blueprint, array('playground_no_cache'=>true));
    }
    $blueprint = quickplayground_fix_variables($blueprint,empty($key) ? '' : $key,$email);
    $blueprint = apply_filters('quickplayground_blueprint',$blueprint);

    return new WP_REST_Response($blueprint, 200);
	}

}

/**
 * REST controller for cloning posts and related data for the playground.
 */
class Quick_Playground_Clone extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_posts/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for getting items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {

	  return true;

	}

    /**
     * Handles GET requests for cloning posts and related data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
  require('getmenus.php');
	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = sanitize_text_field($request['profile']);
    
    if(empty($_GET['nocache'])) {
      $savedfile = $playground_site_uploads.'/quickplayground_posts_'.$profile.'.json';
      if(file_exists($savedfile) && !isset($_GET['refresh'])) {
      $json = file_get_contents($savedfile);
      if($json && $clone = json_decode($json)) {
        //$clone['savedfile'] = $savedfile;
        return new WP_REST_Response($clone, 200);
      }
    }
    }
    
    $clone['profile'] = $profile;
    $settings = get_option('playground_clone_settings_'.$profile,array());
    $settings['playground_premium'] = playground_premium_enabled();
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
      $pages = quickplayground_key_pages($profile);
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

    if($settings['page_on_front'] && !in_array($settings['page_on_front'], $clone['ids'])) {
        $front_page = intval($settings['page_on_front']);
        $page = get_post($front_page);
        $clone['ids'][] = $front_page;
        if($page) {
            $page->post_status = 'publish'; // ensure it is published, even if it was copied from a draft
            $posts[] = $page;
        }
    }

    if($front_page && $thumbnail_id = get_post_meta($front_page,'_thumbnail_id',true)) {
      $sql = "SELECT * FROM $wpdb->posts WHERE ID = ".$thumbnail_id." AND post_type = 'attachment' ";
      $clone['front_page_thumbnail'] = $wpdb->get_row($sql);
    }
    
    if(!empty($settings['demo_pages']) && is_array($settings['demo_pages'])) {
      if($settings['make_menu']) {
        $clone['make_menu'] = true;
        $clone['make_menu_ids'] = $settings['demo_pages'];
      }
      $clone['demo_pages'] = [];
        foreach($settings['demo_pages'] as $page_id) {
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
    elseif(!empty($settings['make_menu'])) {
        $clone['make_menu'] = true;
    }

    if(!empty($settings['demo_posts']) && is_array($settings['demo_posts'])) {
      $clone['demo_posts'] = [];
        foreach($settings['demo_posts'] as $id) {
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
    $clone = apply_filters('quickplayground_playground_clone_posts',$clone, $settings);
    update_option('playground_ids_'.$profile,$clone['ids']);
    return new WP_REST_Response($clone, 200);
}
}

/**
 * REST controller for cloning posts and related data for the playground.
 */
class Quick_Playground_Clone_Settings extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_settings/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for getting items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return true;
	}

    /**
     * Handles GET requests for cloning posts and related data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
  require('getmenus.php');
	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = sanitize_text_field($request['profile']);
    
    if(empty($_GET['nocache'])) {
      $savedfile = $playground_site_uploads.'/quickplayground_settings_'.$profile.'.json';
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
    $settings = get_option('playground_clone_settings_'.$profile,array());
    $settings['playground_premium'] = playground_premium_enabled();
    $settings['timezone_string'] = get_option('timezone_string');
    $sql = "SELECT * FROM $wpdb->options WHERE option_name LIKE 'theme_mods_%'";
    $mods = $wpdb->get_results($sql);
    if(!empty($mods) && is_array($mods)) {
      foreach($mods as $mod) {
        $settings[$mod->option_name] = maybe_unserialize($mod->option_value);
      }
    }
    if(isset($clone['playground_premium'])) {
      $playground_sync_code = wp_generate_password(24,false);
      update_option('playground_sync_code', $playground_sync_code);
      $settings['playground_sync_code'] = $playground_sync_code;
    }
    $clone['settings'] = $settings;
    $clone = apply_filters('quickplayground_playground_clone_settings',$clone);
    return new WP_REST_Response($clone, 200);
}
}

/**
 * REST controller for cloning images and attachments for the playground.
 */
class Quick_Playground_Clone_Images extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning images.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_images/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for getting image items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return true;
	}

    /**
     * Handles GET requests for cloning images and attachments.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
  global $playground_uploads, $playground_site_uploads;
	global $wpdb;
  $profile = $request['profile'];
  $site_dir = is_multisite() ? '/sites/'.get_current_blog_id() : '';
  $savedfile = $playground_site_uploads.'/quickplayground_images_'.$profile.'.json';
  
  if(file_exists($savedfile) && empty($_GET['nocache'])) {
    $json = file_get_contents($savedfile);
    if($json)
    $saved = json_decode($json, true);
  }
    $clone['savedfile'] = $savedfile;
    $clone['saved'] = (empty($saved)) ? 'none' : var_export($saved,true);
    $site_logo = get_option('site_logo');
    if(!empty($site_logo)) {
        $sql = "SELECT * FROM $wpdb->posts WHERE ID = ".intval($site_logo)." AND post_type = 'attachment' ";
        $attachment = $wpdb->get_row($sql);
        $clone['site_logo'] = $attachment;
    }
    $site_icon = get_option('site_icon');
    if(!empty($site_icon)) {
        $sql = "SELECT * FROM $wpdb->posts WHERE ID = ".intval($site_icon)." AND post_type = 'attachment' ";
        $attachment = $wpdb->get_row($sql);
        $clone['site_icon'] = $attachment;
    }
    $clone['thumbnails'] = [];
    $attachment_ids = [];
    $ids = get_option('playground_ids_'.$profile, array());
    $front = get_option('page_on_front');
    if($front) {
      $key = array_search($front, $ids);
      if ($key !== false) {
          unset($ids[$key]);
      }
    }
    $keyimages = quickplayground_find_key_images();
    if(!empty($ids) && is_array($ids)) {      
    $sql = "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png','image/gif','image/bmp','image/webp','image/avif') AND post_status = 'inherit' AND post_parent IN (".implode(',',$ids).")  ORDER BY post_date DESC ";
    $attachments = $wpdb->get_results($sql);
    $sql = "SELECT posts.* FROM $wpdb->postmeta meta JOIN $wpdb->posts posts ON meta.meta_value = posts.ID WHERE meta.post_id IN (".implode(',',$ids).") and meta.meta_key='_thumbnail_id' ORDER BY post_date DESC "; 
    $clone['thumbnails'] = $wpdb->get_results($sql);
    $thumbs = [];
    $clone['keyattachments'] = [];
    $clone['attachments'] = [];
    foreach($clone['thumbnails'] as $t)
      $thumbs[] = $t->ID;
    if(!empty($attachments)) {
      foreach($attachments as $attachment) {
        if(in_array($attachment->ID,$thumbs))
          continue;
          if(in_array($attachment->guid,$keyimages))
            $clone['keyattachments'][] = $attachment;
          else
            $clone['attachments'][] = $attachment;
      }
    }
    $clone = apply_filters('quickplayground_playground_clone_images',$clone);
    return new WP_REST_Response($clone, 200);
  }
  }
}

/**
 * REST controller for delayed cloning images and attachments for the playground.
 */
class Quick_Playground_Thumbnails extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning images.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'thumbnails';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for getting image items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return true;
	}

    /**
     * Handles GET requests for cloning images and attachments.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
    quickplayground_get_thumbnails();
    return new WP_REST_Response($response, 200);
  }
}

/**
 * REST controller for cloning taxonomy and metadata for the playground.
 */
class Quick_Playground_Clone_Taxonomy extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning taxonomy.
     */
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

    /**
     * Permissions check for getting taxonomy items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {

	  return true;

	}

    /**
     * Handles GET requests for cloning taxonomy and metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
  global $playground_uploads, $playground_site_uploads;
  require('getmenus.php');
	global $wpdb;
  $profile = $request['profile'];
  $site_dir = is_multisite() ? '/sites/'.get_current_blog_id() : '';
  $savedfile = $playground_site_uploads.'/quickplayground_meta_'.$profile.'.json';
  
  if(file_exists($savedfile) && empty($_GET['nocache'])) {
    $json = file_get_contents($savedfile);
    if($json && $clone = json_decode($json))
      return new WP_REST_Response($clone, 200);
  }
  
    $clone = [];
    $clone['savedfile'] = $savedfile;
    $clone['ids'] = get_option('playground_ids_'.$profile, array());
    $clone = quickplayground_get_category_data($clone);
    $clone = quickplayground_get_menu_data($clone);
    $clone['postmeta'] = quickplayground_postmeta($clone['ids']);
    $blog_id = get_current_blog_id();
    $blogusers = get_users(
      array(
        'blog_id' => $blog_id
      )
    );
    $one = false;
    $user_ids = [];
    if(sizeof($blogusers) > 30)
      $blogusers = array_slice($blogusers,0,30);
    $clone['users'] = $clone['usermeta'] = [];
    foreach($blogusers as $user) {
    if(1 != $user->ID)
        $clone['users'][] = quickplayground_fake_user($user->ID);
    }
    $clone = apply_filters('quickplayground_playground_clone_meta',$clone);
    return new WP_REST_Response($clone, 200);
}
}

/**
 * REST controller for cloning custom data for the playground.
 */
class Quick_Playground_Clone_Custom extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning taxonomy.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'clone_custom/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for getting taxonomy items.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {

	  return true;

	}

    /**
     * Handles GET requests for cloning taxonomy and metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
  global $playground_uploads, $playground_site_uploads;
  $profile = $request['profile'];
  $site_dir = is_multisite() ? '/sites/'.get_current_blog_id() : '';
  $savedfile = $playground_site_uploads.'/quickplayground_custom_'.$profile.'.json';
  
  if(file_exists($savedfile) && empty($_GET['nocache'])) {
    $json = file_get_contents($savedfile);
    if($json && $clone = json_decode($json))
      return new WP_REST_Response($clone, 200);
  }
  
    $clone = ['custom_tables'=>[]];
    $ids = get_option('playground_ids_'.$profile, array());
    $clone = apply_filters('quickplayground_clone_custom',$clone,$ids);
    return new WP_REST_Response($clone, 200);
}
}


/**
 * REST controller for syncing data between the playground and the live site.
 */
class Quick_Playground_Sync extends WP_REST_Controller {

    /**
     * Registers REST API routes for syncing data.
     */
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

  

    /**
     * Permissions check for syncing data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

  

    /**
     * Handles GET and POST requests for syncing data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
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

/**
 * REST controller for saving posts data in the playground.
 */
class Quick_Playground_Save_Posts extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving posts.
     */
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

  

    /**
     * Permissions check for saving posts.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles POST requests for saving posts data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {

	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = $request['profile'];
    $savedfile = $playground_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_posts',$data);
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

/**
 * REST controller for saving settings in the playground.
 */
class Quick_Playground_Save_Settings extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving metadata.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_settings/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET, POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

    /**
     * Permissions check for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles GET and POST requests for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_settings',$data);
    $savedfile = $playground_site_uploads.'/quickplayground_settings_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

/**
 * REST controller for saving metadata in the playground.
 */
class Quick_Playground_Save_Meta extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving metadata.
     */
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

  

    /**
     * Permissions check for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles GET and POST requests for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_meta',$data);
    $savedfile = $playground_site_uploads.'/quickplayground_meta_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

/**
 * REST controller for saving images in the playground.
 */
class Quick_Playground_Save_Image extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving images.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_image/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET, POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

    /**
     * Permissions check for saving images.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles GET and POST requests for saving images.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
	global $wpdb, $playground_uploads,$playground_site_uploads;
    $profile = $request['profile'];
    $playground_saved = get_option('playground_saved_images',array());
    $data = $request->get_json_params();
    $savedfile = $playground_site_uploads.'/quickplayground_images_'.$profile.'.json';
    if(is_string($data))
      $data = json_decode($data, true);
    if(!is_array($data))
      $data = (array) $data;
    if(!empty($data['path']) && !empty($data['base64'])) {
      $savedfile = $playground_site_uploads.$data['path'];
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
        $filedata = base64_decode($data['base64']);
        $result = fwrite($handle,$filedata);
        if($result) {
          $sync_response['written'] = $result;
          $size = wp_getimagesize($savedfile);
          if(!empty($size)) {
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
    }
    if(!empty($data['attachments']) && is_array($data['attachments'])) {
    foreach($data['attachments'] as $attachment) {
      if(!empty($attachment['ID']) && !empty($attachment['guid'])) {
        $id = intval($attachment['ID']);
        $guid = esc_url_raw($attachment['guid']);
        $sync_response['attachments'][$id] = $guid;
      }
    }
    $savedfile = $playground_site_uploads.'/quickplayground_images_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    }
    return new WP_REST_Response($sync_response, 200);
	}
}

/**
 * REST controller for saving custom table content in the playground.
 */
class Quick_Playground_Save_Custom extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving metadata.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_custom/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET, POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

    /**
     * Permissions check for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles GET and POST requests for saving metadata.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('quickplayground_saved_custom',$data);
    $savedfile = $playground_site_uploads.'/quickplayground_custom_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

class Quick_Playground_Save_Prompts extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'save_prompts/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'POST',

		  'callback'            => array( $this, 'get_items' ),

		  'permission_callback' => array( $this, 'get_items_permissions_check' )

			  ),

		  ]);     

	  }

  

    /**
     * Permissions check for saving posts.
     *
     * @param WP_REST_Request $request The REST request.
     * @return bool True if allowed.
     */
	public function get_items_permissions_check($request) {
	  	return 'https://playground.wordpress.net/' == $_SERVER['HTTP_REFERER'];
	}

    /**
     * Handles POST requests for saving posts data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {

	global $wpdb, $playground_uploads, $playground_site_uploads;
    $profile = $request['profile'];
    $savedfile = $playground_site_uploads.'/quickplayground_prompts_'.$profile.'.json';
    //$data = $request->get_json_params();
    $bytes_written = file_put_contents($savedfile,trim(stripslashes($request->get_body()),'"'));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    return new WP_REST_Response($sync_response, 200);
	}
}

class Quick_Playground_Download extends WP_REST_Controller {

	public function register_routes() {

		$namespace = 'quickplayground/v1';

		$path      = 'download/(?P<filename>[A-Za-z0-9_\-\.]+)';

		register_rest_route(

			$namespace,

			'/' . $path,

			array(

				array(

					'methods'             => 'GET,POST',

					'callback'            => array( $this, 'handle' ),

					'permission_callback' => array( $this, 'get_items_permissions_check' ),

				),

			)

		);

	}

	public function get_items_permissions_check( $request ) {

		return true;

	}

	public function handle( $request ) {
    global $playground_uploads;
    $filename = sanitize_text_field($request['filename']);
    $file = $playground_uploads.'/'.$filename;
 
        if(!file_exists($file)){ // file does not exist

            die($file.' file not found');

        } else {

            header("Cache-Control: public");

            header("Content-Description: File Transfer");

            header("Content-Disposition: attachment; filename=".$filename);

            header("Content-Type: application/zip");

            header("Content-Transfer-Encoding: binary");

            header("Access-Control-Allow-Origin: *");

            // read the file from disk

            readfile($file);

        }
    }
}

add_action('rest_api_init', function () {

	 $hook = new Quick_Playground_Clone();
	 $hook->register_routes();
	 $hook = new Quick_Playground_Clone_Taxonomy();
	 $hook->register_routes();           
	 $hook = new Quick_Playground_Clone_Settings();
	 $hook->register_routes();           
	 $hook = new Quick_Playground_Clone_Images();
	 $hook->register_routes();           
	 $hook = new Quick_Playground_Thumbnails();
	 $hook->register_routes();           
	 $hook = new Quick_Playground_Clone_Custom();
	 $hook->register_routes();           
   $hook = new Quick_Playground_Blueprint();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Sync();
	$hook->register_routes();           
  $hook = new Quick_Playground_Save_Posts();
	$hook->register_routes();           
  $hook = new Quick_Playground_Save_Settings();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Save_Meta();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Save_Image();
	 $hook->register_routes();
  $hook = new Quick_Playground_Save_Custom();
	 $hook->register_routes();
  $hook = new Quick_Playground_Save_Prompts();
	 $hook->register_routes();
  $hook = new Quick_Playground_Download();
	 $hook->register_routes();
} );

