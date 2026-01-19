<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
  
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
  ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];

    $profile = $request['profile'];
    $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    $data = $request->get_json_params();
    if(empty($request->get_body())) {
      return new WP_Error(
            'empty',          // A unique error code (string)
            __( 'An empty message body was submitted.' ), // A human-readable message (translatable)
            array( 'status' => 400 )      // The HTTP status code (e.g., 400 Bad Request)
        );
    }
    if(file_exists($savedfile) && !isset($_GET['refresh'])) {
      $json = file_get_contents($savedfile);
      if($json && $cache = json_decode($json,true)) {
        $post_ids = [];
        $taxonomy_tracking = [];
        foreach($data['posts'] as $post) {
          $post = (object) $post;
          $post_ids[] = $post->ID;
          $track = qckply_theme_template_tracking_key($post->ID, $data);
          if($track)
            $taxonomy_tracking[] = $track;
        }
        $sync_response['cached_posts_included'] = 0;
        $include = true;
        foreach($cache['posts'] as $post) {
          $post = (object) $post;
          $track = qckply_theme_template_tracking_key($post->ID, $cache);
          if($track && in_array($track,$taxonomy_tracking)) {
            $include = false;
            continue; //if there is a more recent version of a document like a theme header, don't include the cached one regardless of ID
          }
          if(!in_array($post->ID,$post_ids)) {
            //if the post is not already in the data being sent, include it
            $data['posts'][] = $post;
            $sync_response['cached_posts_included']++;
          }
        }
        if($include)
        foreach($cache['related'] as $pid => $value) {
          if(empty($data['related'][$pid]))
            $data['related'][$pid] = $value;
        }
      }
    }

    //$data = apply_filters('qckply_saved_posts',$data);
    $data['cache_saved'] = date('r');
    $data['post_not_empty'] = !empty($data['posts']);
    $data['post_count'] = !empty($data['posts']) ? count($data['posts']) : 0;
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $json = json_encode($data);
    if($json !== false) {
    $bytes_written = file_put_contents($savedfile,$json);
    $sync_response['saved'] = $bytes_written;
    }
    else {
    $sync_response['saved'] = json_last_error_msg();
    }
    $sync_response['file'] = $savedfile;
    ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
    return $response;
	}
}

class Quick_Playground_Sync_Ids extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'sync_ids';

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
  ob_start();
	global $wpdb;
    $data = $request->get_json_params();
    $server_top = qckply_top_ids(true);
    $client_top = $data['top_ids'];
    $clone = array();
    foreach($client_top as $key => $value) {
        $clone[$key] = $value - $server_top[$key];
    }
    ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $clone, 200, $headers );
    return $response;
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
    ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];

    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('qckply_saved_settings',$data);
    $savedfile = $qckply_site_uploads.'/quickplayground_settings_'.$profile.'.json';
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
    return $response;
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
	global $wpdb;
  ob_start();
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('qckply_saved_meta',$data);
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $savedfile = $qckply_site_uploads.'/quickplayground_meta_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
    return $response;
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
    ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];

    $profile = $request['profile'];
    $qckply_saved = get_option('qckply_saved_images',array());
    $data = $request->get_json_params();
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $savedfile = $qckply_site_uploads.'/quickplayground_images_'.$profile.'.json';
    if(is_string($data))
      $data = json_decode($data, true);
    if(!is_array($data)){
      ob_clean();
      return WP_REST_Response( ['error' => 'no data'], 200 );
    }
    $savedfile = $qckply_site_uploads.'/quickplayground_images_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
    return $response;
	}
}

class Quick_Playground_Upload_Image extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving images.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'upload_image/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => WP_REST_Server::ALLMETHODS,

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
      $code =  get_transient('qckply_sync_code');
      $params = $request->get_json_params();
      $sync_code = isset($params['sync_code']) ? $params['sync_code'] : '';
      if(!empty($code) && $code == $sync_code)
        return true;
      else {
        set_transient('invalid_sync_code',$sync_code);
        return false;
      }
	}

    /**
     * Handles GET and POST requests for saving images.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
	global $wpdb;
  ob_start();
    $profile = $request['profile'];
    $qckply_image_uploads = get_option('qckply_image_uploads',[]);
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $params = $request->get_json_params();
    $code =  get_transient('qckply_sync_code');
    $params = $request->get_json_params();
    $sync_code = isset($params['sync_code']) ? $params['sync_code'] : '';
    $sync_response['sync_code'] = $sync_code;
    $sync_response['correct_code'] = $code;
    $filename = sanitize_text_field($params['filename']);
    $last_image = get_transient('qckply_last_image_uploaded');
    if($last_image == $filename) {
        $sync_response['message'] = 'duplicate image';
        $response = new WP_REST_Response( $sync_response, 300 );
    }
    elseif (empty($params['base64'])) {
        $sync_response['message'] = 'no base 64';
        ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 300, $headers );
        return $response;
    }
    else {
        set_transient('qckply_last_image_uploaded',$filename);
        $filedata = base64_decode($params['base64']);
        $newpath = $qckply_site_uploads.'/'.$filename;
        $newurl = $qckply_site_uploads_url.'/'.$filename;
        $saved = file_put_contents($newpath,$filedata);
        $parent_id = isset($params['post_parent']) ? intval($params['post_parent']) : 0;
        $sync_response['message'] = 'saving to '.$newpath .' '.var_export($saved,true);
        $sync_response['sideload_meta'] = qckply_sideload_saved_image($newurl, $parent_id);
        $server_attachment_id = $sync_response['sideload_meta']['attachment_id'];
        if($sync_response['sideload_meta']['attachment_id'] <= $params['top_id'])
        {
        $server_attachment_id = $params['top_id'] + 1;
        $attachment_id = intval($sync_response['sideload_meta']['attachment_id']);
        $sync_response['change_id'] = sprintf('Changing attachment ID from %d to %d (ID on live site)',$attachment_id,$server_attachment_id);
        $sync_response['sideload_meta']['attachment_id'] = $server_attachment_id;
        $wpdb->query($wpdb->prepare("update %i set ID=%d WHERE ID=%d",$wpdb->posts,$server_attachment_id,$attachment_id));
        $wpdb->query($wpdb->prepare("update %i set post_id=%d WHERE post_id=%d",$wpdb->postmeta,$server_attachment_id,$attachment_id));
        }
        $qckply_image_uploads[] = $server_attachment_id;
        update_option('qckply_image_uploads',$qckply_image_uploads);
      ob_clean();
      $headers = [
          "Access-Control-Allow-Origin" => "*",
          "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
          "Access-Control-Allow-Headers" => "Content-Type",
      ];
      $response = new WP_REST_Response( $sync_response, 200, $headers );
    }
    return $response;
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
    ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $profile = $request['profile'];
    $data = $request->get_json_params();
    $data = apply_filters('qckply_saved_custom',$data);
    $savedfile = $qckply_site_uploads.'/quickplayground_custom_'.$profile.'.json';
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    ob_clean();
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;
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
    ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $profile = $request['profile'];
    $savedfile = $qckply_site_uploads.'/quickplayground_prompts_'.$profile.'.json';
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $bytes_written = file_put_contents($savedfile,trim(stripslashes($request->get_body()),'"'));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
        ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
  return $response;
	}
}

class Quick_Playground_Get_Prompts extends WP_REST_Controller {

    /**
     * Registers REST API routes for saving posts.
     */
    public function register_routes() {

	  $namespace = 'quickplayground/v1';

	  $path = 'prompts/(?P<profile>[a-z0-9_]+)';

	  register_rest_route( $namespace, '/' . $path, [

		array(

		  'methods'             => 'GET, POST',

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
	  	return true;
	}

    /**
     * Handles POST requests for saving posts data.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response The response object.
     */
  public function get_items($request) {
    ob_start();
	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $profile = $request['profile'];
    $savedfile = $qckply_site_uploads.'/quickplayground_prompts_'.$profile.'.json';
    $contents = file_get_contents($savedfile);
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $sync_response = json_decode($contents,true);
    $sync_response['found_contents'] = !empty($contents);
    ob_clean();
        ob_clean();
    $headers = [
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Methods" => "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type",
    ];
    $response = new WP_REST_Response( $sync_response, 200, $headers );
    return $response;
	}
}

add_action('rest_api_init', function () {
  $hook = new Quick_Playground_Save_Posts();
	$hook->register_routes();           
  $hook = new Quick_Playground_Save_Settings();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Save_Meta();
	 $hook->register_routes();           
  $hook = new Quick_Playground_Save_Image();
	 $hook->register_routes();
  $hook = new Quick_Playground_Upload_Image();
	 $hook->register_routes();
  $hook = new Quick_Playground_Save_Custom();
	 $hook->register_routes();
  $hook = new Quick_Playground_Save_Prompts();
	 $hook->register_routes();
  $hook = new Quick_Playground_Get_Prompts();
	 $hook->register_routes();
   $hook = new Quick_Playground_Sync_Ids();
   $hook->register_routes();
} );
