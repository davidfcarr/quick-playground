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

	global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];

    $profile = $request['profile'];
    $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    $data = $request->get_json_params();
    $data = apply_filters('qckply_saved_posts',$data);
    /* not sanitized here, except as valid json. sanitized in the playground if used there */
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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
      return WP_REST_Response( ['error' => 'no data'], 200 );
    }
    $savedfile = $qckply_site_uploads.'/quickplayground_images_'.$profile.'.json';
    $bytes_written = file_put_contents($savedfile,json_encode($data));
    $sync_response['saved'] = $bytes_written;
    $sync_response['file'] = $savedfile;
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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

    if (!empty($params['base64'])) {
        $filedata = base64_decode($params['base64']);
        $filename = sanitize_text_field($params['filename']);
        $newpath = $qckply_site_uploads.'/'.$filename;
        $newurl = $qckply_site_uploads_url.'/'.$filename;
        $saved = file_put_contents($newpath,$filedata);
        $sync_response['message'] = 'saving to '.$newpath .' '.var_export($saved,true);
        $sync_response['sideload_meta'] = qckply_sideload_saved_image($newurl);
        //$result = wp_schedule_single_event( time()+HOUR_IN_SECONDS, 'qckply_sideload_saved_image', array($newurl),true);
        //update_option('qckply_sideload_schedule',var_export($result,true));
    } else {
        $sync_response['message'] = 'no base 64';
    }
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;


    if(empty($_FILES['playground_upload'])) {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }

    $file = $_FILES['playground_upload'];

    // Validate file type/size as needed
    $allowed = array('jpg','jpeg','png','gif','bmp','webp','ico','tiff','tif','avif');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return new WP_Error('invalid_type', 'File type not allowed', array('status' => 400));
    }

    // Use WordPress to handle the upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/post.php');

    $overrides = array('test_form' => false); // Allow uploads outside of form POST
    $response = new WP_REST_Response( array('filename'=>$file['name']), 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
    return $response;

    $movefile = wp_handle_upload($file, $overrides);

    if ($movefile && !isset($movefile['error'])) {
        // Prepare array for sideload
        $sideload = array(
            'name'     => $file['name'],
            'type'     => $movefile['type'],
            'tmp_name' => $movefile['file'],
            'error'    => 0,
            'size'     => filesize($movefile['file']),
        );

        // Create attachment and metadata
        $attachment_id = media_handle_sideload($sideload, 0,'quick_playground_upload');

        if (is_wp_error($attachment_id)) {
            $sync_response = new WP_Error('attachment_error', $attachment_id->get_error_message(), array('status' => 500));
        } else {
            // Get attachment metadata
            $meta = wp_get_attachment_metadata($attachment_id);
            $url = wp_get_attachment_url($attachment_id);

            $sync_response = array(
                'success'        => true,
                'attachment_id'  => $attachment_id,
                'file'           => $movefile['file'],
                'url'            => $url,
                'type'           => $movefile['type'],
                'meta'           => $meta,
            );
        }
    } else {
        $sync_response = new WP_Error('upload_error', $movefile['error'], array('status' => 500));
    }
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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
    $response = new WP_REST_Response( $sync_response, 200 );
    $response->header( "Access-Control-Allow-Origin", "*" );
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
} );
