<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * REST controller for managing blueprints in the playground.
 */
class Qckply_Blueprint extends WP_REST_Controller {

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
    $profile = sanitize_text_field($request['profile']);
    do_action('qckply_fetch_blueprint',$profile);
    $clone = qckply_get_clone_posts($profile);
    $clone = qckply_zip_images($profile,$clone);
    set_transient('qckply_clone_posts_'.$profile,$clone);
    update_option('qckply_ids_'.$profile,$clone['ids']);

    qckply_zip_plugin("quick-playground");
    $blueprint = get_option('qckply_blueprint_'.$profile);
    if (!$blueprint) {
        return new WP_REST_Response(array('error'=>'blueprint_not_found'), 404);
    }
    if(isset($_GET['stylesheet'])) {
      //no nonce check because this can be called from a static link
      $blueprint = qckply_swap_theme($blueprint, sanitize_text_field(wp_unslash($_GET['stylesheet'])));
    }
    if(empty($_GET['is_demo'])) {
      //no nonce check because this can be called from a static link
      $blueprint = qckply_change_blueprint_setting($blueprint, array('qckply_sync_code'=>qckply_cloning_code($profile)));
    } 
    else {
      $blueprint = qckply_change_blueprint_setting($blueprint, array('qckply_is_demo'=>true));
    }
    if(!empty($_GET['nocache'])) {
      //no nonce check because this can be called from a static link
      $blueprint = qckply_change_blueprint_setting($blueprint, array('qckply_no_cache'=>true));
    }
    $blueprint = apply_filters('qckply_blueprint',$blueprint);
    qckply_hits($profile);
    $response = new WP_REST_Response( $blueprint, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
	}
}

/**
 * REST controller for cloning posts and related data for the playground.
 */
class Qckply_Clone extends WP_REST_Controller {

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
  global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $profile = sanitize_text_field($request['profile']);
    
    $clone = qckply_get_clone_posts($profile);
    $clone['post_count'] = count($clone['posts']);
    $clone = qckply_zip_images($profile,$clone);
    $clone['post_count_with_images'] = is_array($clone['posts']) ? count($clone['posts']) : 'error';
    $clone = qckply_posts_related($clone);
    $clone = qckply_get_menu_data($clone);
    unset($clone['ids']);
    $response = new WP_REST_Response( $clone, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
}
}

function qckply_get_clone_posts($profile) {
  global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $top = qckply_top_ids(true);
    $posts = array();
    $clone['ids'] = array();
    if(empty($_GET['nocache'])) {
      $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
      if(file_exists($savedfile) && !isset($_GET['refresh'])) {
      $json = file_get_contents($savedfile);
      if($json && $cache = json_decode($json,true)) {
        if(empty($cache['posts']))
          $clone['cache_posts_empty'] = true;
        else
        foreach($cache['posts'] as $post) {
          $post = (object) $post;
          $clone['posts_from_cache'][] = $post->ID.': '.$post->post_title;
          $clone['ids'][] = $post->ID;
          $posts[] = $post;
        }
        $clone['json_error'] = json_last_error_msg();
      }
      else
        $clone['error_reading_file'] = false;
      }
      else {
        $clone['error_file_not_found'] = $savedfile;
      }
    }

    $clone['profile'] = $profile;
    $top_post = qckply_top_ids()['posts'];
    $clone['top_post'] = $top_post;
    $settings = get_option('quickplay_clone_settings_'.$profile,array());
    $template_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
    $header_content = (empty($template_part->content)) ? '' : $template_part->content;
    $clone['nav_id'] = 0;
    if($header_content) {
    preg_match('/"ref":([0-9]+)/',$header_content,$match);
    if(!empty($match[1]))
        $clone['nav_id'] = $match[1];
    }

    if(!empty($settings['page_on_front'])) {
        $front_page = intval($settings['page_on_front']);
        if(!in_array($front_page,$clone['ids'])) {
        $page = get_post($front_page);
        $clone['ids'][] = $front_page;
        if($page) {
            $page->post_status = 'publish'; // ensure it is published, even if it was copied from a draft
            $posts[] = $page;
        }
        }
    }

    $params = [$wpdb->posts];
    $sql = "SELECT * FROM %i WHERE post_status='publish' AND (`post_type` = 'wp_block' OR `post_type` = 'wp_global_styles' OR `post_type` = 'wp_navigation' OR `post_type` = 'wp_template' OR `post_type` = 'wp_template_part' ";
    if(!empty($settings['post_types']) && is_array($settings['post_types']))
    {
      foreach($settings['post_types'] as $t) {
        $t = sanitize_text_field($t);
        $sql .= " OR `post_type` = %s ";
        $params[] = $t;
      }
    }
    $sql .= ")";
    $templates = $wpdb->get_results($wpdb->prepare($sql, $params));
    foreach($templates as $p) {
      if(!in_array($p->ID,$clone['ids'])) {
        $clone['ids'][] = $p->ID;
        $posts[] = $p;
      }
    }
    if(!empty($settings['copy_blogs'])) {
        $blogs = get_posts(array('numberposts'=>intval($settings['copy_blogs'])));
        foreach($blogs as $blog)
        {
          if(!in_array($blog->ID,$clone['ids']))
          {
            $posts[] = $blog;
            $clone['ids'][] = $blog->ID;
          }
        }
    }
    if(!empty($settings['copy_pages'])) {
      $pages = get_posts(array('post_type'=>'page','numberposts'=>-1));
        foreach($pages as $p)
        {
          if(!in_array($p->ID,$clone['ids']))
          {
            $posts[] = $p;
            $clone['ids'][] = $p->ID;
          }
    }
  }
    if(!empty($settings['qckply_key_pages'])) {
      $pages = qckply_qckply_key_pages($profile);
        foreach($pages as $p)
        {
        if(!in_array($p->ID,$clone['ids']))
        {
          $posts[] = $p;
          $clone['ids'][] = $p->ID;
        }
    }
  }
    
    if(!empty($settings['demo_pages']) && is_array($settings['demo_pages'])) {
      if(!empty($settings['make_menu'])) {
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
                $posts[] = $page;
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
                $posts[] = $p;
                $clone['ids'][] = $p->ID;
            }
      }
    }
    $clone['posts'] = $posts;
  
    $clone['posts'][] = (object) array(
      'ID' => $top_post + 1,
      'post_type' => 'qckply_placeholder',
      'post_title' => 'Playground Placeholder',
      'post_content' => '',
      'post_status' => 'draft'
    );

    $clone = apply_filters('qckply_qckply_clone_posts',$clone, $settings);
    return $clone;
}

/**
 * REST controller for cloning posts and related data for the playground.
 */
class Qckply_Clone_Settings extends WP_REST_Controller {

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
  global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $profile = sanitize_text_field($request['profile']);
    
    $clone['profile'] = $profile;
    $settings = get_option('quickplay_clone_settings_'.$profile,array());
    $settings['timezone_string'] = get_option('timezone_string');
    $mods = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE option_name LIKE %s ",$wpdb->options,'theme_mods_%'));
    if(!empty($mods) && is_array($mods)) {
      foreach($mods as $mod) {
        $settings[$mod->option_name] = maybe_unserialize($mod->option_value);
      }
    }
    $clone['settings'] = $settings;
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
        $clone['users'][] = qckply_fake_user($user->ID);
    }

    $clone['settings_from_cache'] = [];
    if(empty($_GET['nocache'])) {
      $savedfile = $qckply_site_uploads.'/quickplayground_settings_'.$profile.'.json';
      if(file_exists($savedfile) && !isset($_GET['refresh'])) {
      $json = file_get_contents($savedfile);
      if($json && $cache_clone = json_decode($json,true)) {
        foreach($cache_clone['settings'] as $key => $value) {
          $clone['settings'][$key] = $value;
          $clone['settings_from_cache'][] = $key;
        }
      }
    }
    }
    $clone = apply_filters('qckply_qckply_clone_settings',$clone);
    $response = new WP_REST_Response( $clone, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
}
}

/**
 * REST controller for cloning taxonomy and metadata for the playground.
 */
class Qckply_Clone_Taxonomy extends WP_REST_Controller {

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
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
  require('getmenus.php');
	global $wpdb;
  $profile = $request['profile'];
  $site_dir = is_multisite() ? '/sites/'.get_current_blog_id() : '';
  $savedfile = $qckply_site_uploads.'/quickplayground_meta_'.$profile.'.json';
  
    $clone = [];
    $clone['savedfile'] = $savedfile;
    $clone['ids'] = get_option('qckply_ids_'.$profile, array());
    $clone = qckply_posts_related($clone);
    //$clone = qckply_get_category_data($clone);
    $clone = qckply_get_menu_data($clone);
    //$clone['postmeta'] = qckply_postmeta($clone['ids']);
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
        $clone['users'][] = qckply_fake_user($user->ID);
    }
    $clone = apply_filters('qckply_qckply_clone_meta',$clone);
    if(file_exists($savedfile) && empty($_GET['nocache'])) {
    $json = file_get_contents($savedfile);
    if($json && $cache_clone = json_decode($json, true)) {
        $clone['savedfile'] = $savedfile;
        if(!empty($cache_clone['related']))
        foreach($cache_clone['related'] as $key => $related)
        {
          $clone['related'][$key] = $related;
        }
    }
  }  unset($clone['ids']);
    $response = new WP_REST_Response( $clone, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
}
}

/**
 * REST controller for cloning custom data for the playground.
 */
class Qckply_Clone_Custom extends WP_REST_Controller {

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
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
  $profile = $request['profile'];
  $site_dir = is_multisite() ? '/sites/'.get_current_blog_id() : '';
  $savedfile = $qckply_site_uploads.'/quickplayground_custom_'.$profile.'.json';
  
  if(file_exists($savedfile) && empty($_GET['nocache'])) {
    $json = file_get_contents($savedfile);
    if($json && $clone = json_decode($json)) {
        $response = new WP_REST_Response( $clone, 200 );
        $response->header( "Access-Control-Allow-Origin", "*" );
        return $response;
    }
  }
  
    $clone = ['custom_tables'=>[]];
    $clone['ids'] = get_option('qckply_ids_'.$profile, array());
    $clone = apply_filters('qckply_clone_custom',$clone,$clone['ids']);
    $response = new WP_REST_Response( $clone,200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
}
}

class Qckply_Download extends WP_REST_Controller {

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
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    $filename = sanitize_text_field($request['filename']);
    $file = $qckply_uploads.'/'.$filename;
 
        if(!file_exists($file)){ // file does not exist

            die('file not found');

        } else {

            header("Cache-Control: public");

            header("Content-Description: File Transfer");

            header("Content-Disposition: attachment; filename=".$filename);

            header("Content-Type: application/zip");

            header("Content-Transfer-Encoding: binary");

            header("Access-Control-Allow-Origin: *");
            //tried but could not get this to work correctly with wp_filesystem
            readfile($file);
        }
    }
}

/**
 * REST controller for cloning posts and related data for the playground.
 */
class Qckply_Download_Json extends WP_REST_Controller {

    /**
     * Registers REST API routes for cloning posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'download_json/(?P<profile>[a-z0-9_]+)';

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
  global $wpdb;
    $local_directories = qckply_get_directories();
    $profile = sanitize_text_field($request['profile']);
    $file = 'quickplayground_posts_'.$profile.'.json';
    $filename = trailingslashit($local_directories['site_uploads']).$file;

    if(!file_exists($filename)){ // file does not exist

        die($filename.' file not found');

    } else {

        header("Cache-Control: public");

        header("Content-Description: File Transfer");

        header("Content-Disposition: attachment; filename=".$file);

        header("Content-Type: application/zip");

        header("Content-Transfer-Encoding: binary");

        header("Access-Control-Allow-Origin: *");

        // read the file from disk

        readfile($filename);

  }
}

}


add_action('rest_api_init', function () {
	 $hook = new Qckply_Clone();
	 $hook->register_routes();
	 $hook = new Qckply_Clone_Taxonomy();
	 $hook->register_routes();           
	 $hook = new Qckply_Clone_Settings();
	 $hook->register_routes();           
	 $hook = new Qckply_Clone_Custom();
	 $hook->register_routes();           
   $hook = new Qckply_Blueprint();
	 $hook->register_routes();           
  $hook = new Qckply_Download();
	 $hook->register_routes();
   if(qckply_is_playground()) {
    $hook = new Qckply_Download_Json();
	  $hook->register_routes();
   }
} );
