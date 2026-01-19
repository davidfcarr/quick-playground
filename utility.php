<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Checks if a plugin or theme exists in the WordPress.org repository.
 *
 * @param string $urlOrSlug The URL or slug of the plugin/theme.
 * @param string $type      'plugin' or 'theme'.
 * @return bool             True if found, false otherwise.
 */
function qckply_repo_check($urlOrSlug, $type = 'plugin') {
    $basename = str_replace('/', '', basename($urlOrSlug));
    if('theme' == $type) {
        require_once( ABSPATH . 'wp-admin/includes/theme.php' );
        $info = themes_api( 'theme_information', array( 'slug' => $basename ) );
    } else {
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        $info = plugins_api( 'plugin_information', array( 'slug' => $basename ) );
    }
    if ( ! $info or is_wp_error( $info ) ) {
        return false;
    }
    return true;
}

/**
 * Adds a toolbar link for the Design Playground in the WordPress admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
 */
add_action( 'admin_bar_menu', 'qckply_toolbar_link',50 );
function qckply_toolbar_link( $wp_admin_bar ) {
    if(get_option('qckply_is_demo',false))
        return;
    $args = array(
        'id'    => 'quick_playground',
        'title' => 'Quick Playground',
        'href'  => admin_url('admin.php?page=quickplayground'),
        'parent' => 'site-name',
        'meta'  => array( 'class' => 'playground' )
    );    
    $wp_admin_bar->add_node( $args );
    if(get_option('is_qckply_clone',false))
    {
    $code = get_option('qckply_sync_code');
    if(empty($code))
        return;
    $origin = get_option('qckply_sync_origin');
    $parts = parse_url($origin);
    $origin_host = $parts['host'];
    $sync_label = __('Sync with','quick-playground').' '.$origin_host;

        $args = array(

            'id'    => 'playground-save',

            'title' => $sync_label,

            'href'  => admin_url('admin.php?page=qckply_save'),

            'parent' => 'quick_playground',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );

        $args = array(

            'id'    => 'playground-prompts',

            'title' => 'Edit Playground Prompts',

            'href'  => admin_url('admin.php?page=qckply_clone_prompts'),

            'parent' => 'quick_playground',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );

        $args = array(
            'id'    => 'playground-import',
            'title' => 'Playground Import Log',
            'href'  => admin_url('admin.php?page=qckply_clone_log'),
            'parent' => 'quick_playground',
            'meta'  => array( 'class' => 'playground' )
        );    
        $wp_admin_bar->add_node( $args );
    }
    else {
        $args = array(
            'id'    => 'qckply-builder',
            'title' => 'Playground Builder',
            'href'  => admin_url('admin.php?page=qckply_builder'),
            'parent' => 'quick_playground',        
            'meta'  => array( 'class' => 'quick_playground' )
        );
        $wp_admin_bar->add_node( $args );
        $args = array(
            'id'    => 'qckply-sync',
            'title' => 'Playground Sync',
            'href'  => admin_url('admin.php?page=qckply_sync'),
            'parent' => 'quick_playground',        
            'meta'  => array( 'class' => 'quick_playground' )
        );
        $wp_admin_bar->add_node( $args );
    }
}

/**
 * Registers admin menu pages for the Design Playground plugin.
 */
add_action('admin_menu', 'qckply_design_qckply_menus');
function qckply_design_qckply_menus() {
    if(qckply_is_playground()) {
        add_menu_page('Quick Playground Client', 'Quick Playground Client', 'manage_options', 'quickplayground', 'qckply_main','data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==', 65);
    if(get_option('qckply_sync_code')) {
        $origin = get_option('qckply_sync_origin');
        $parts = parse_url($origin);
        $origin_host = $parts['host'];
        $sync_label = __('Sync with','quick-playground').' '.$origin_host;
        add_submenu_page('quickplayground',$sync_label, $sync_label, 'manage_options', 'qckply_save', 'qckply_save');
        //add_submenu_page('quickplayground','Save Images', 'Save Images', 'manage_options', 'qckply_upload_images', 'qckply_upload_images');
        add_submenu_page('quickplayground','Edit Playground Prompts', 'Edit Playground Prompts', 'manage_options', 'qckply_clone_prompts', 'qckply_clone_prompts');
    }
    add_submenu_page('quickplayground','Quick Data', 'Quick Data', 'manage_options', 'qckply_data', 'qckply_data');
    add_submenu_page('quickplayground','Import Log', 'Import Log', 'manage_options', 'qckply_clone_log', 'qckply_clone_log');
    }
    else {
        add_menu_page('Quick Playground', 'Quick Playground', 'manage_options', 'quickplayground', 'qckply_main','data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==', 65);
        add_submenu_page('quickplayground','Playground Builder', 'Playground Builder', 'manage_options', 'qckply_builder', 'qckply_builder');
        add_submenu_page('quickplayground','Playground', 'Playground Sync', 'manage_options', 'qckply_sync', 'qckply_sync');
        add_submenu_page('quickplayground','Json Upload', 'Json Upload', 'manage_options', 'qckply_json_upload', 'qckply_json_upload');
        if(is_multisite())
            add_submenu_page('quickplayground','Network Administrator Controls', 'Network Administrator Controls', 'manage_network', 'qckply_networkadmin', 'qckply_networkadmin');
    }
}

/**
 * Retrieves post meta for a list of post IDs.
 *
 * @param array $ids Array of post IDs.
 * @return array     Array of post meta objects.
 */
function qckply_postmeta($ids) {
    global $wpdb;
    $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i where post_id IN ($placeholders) ",$wpdb->postmeta,...$ids));
}

/**
 * Zips a directory and saves it to the uploads directory.
 *
 * @param string $source_dir   Source directory to zip.
 * @param string $uploads_dir  Destination uploads directory.
 * @param string $slug         Optional slug for the zip file name.
 * @return bool                True on success, false on failure.
 */
function qckply_zipToUploads(string $source_dir, string $uploads_dir, $slug = ''): bool
{
    if(!is_dir($source_dir))
        return false;

    if (empty($slug)) {
        $slug = basename($source_dir);
    }

    $zip = new ZipArchive();
    $zip_filename = $slug . '.zip'; // Create a unique filename
    $zip_filepath = $uploads_dir . '/' . $zip_filename;

    if ($zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false; // Zip file creation failed
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($source_dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    return $zip->close(); // Returns true if zip created successfully, false otherwise
}

/**
 * Zips a target directory and saves it to the playground uploads directory.
 *
 * @param string $source_directory The source directory to zip.
 * @return string|bool             Success message or false on failure.
 */
function qckply_zip_target($source_directory) {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Theme zipped successfully! The zip file can be found at: ' . $upload_directory;
    } else {
        return false;
    }
}

/**
 * Zips the current theme and saves it to the playground uploads directory.
 *
 * @return string Success or failure message.
 */
function qckply_zip_self() {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Theme zipped successfully! The zip file can be found at: ' . $qckply_uploads;
    } else {
        return 'Theme zip creation failed.';
    }
}

/**
 * Zips the current theme and saves it to the playground uploads directory.
 *
 * @return string Success or failure message.
 */
function qckply_zip_current_theme() {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    $source_directory = get_theme_root() . '/' . get_stylesheet(); //  Get theme path
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Theme zipped successfully! The zip file can be found at: ' . $upload_directory;
    } else {
        return 'Theme zip creation failed.';
    }
}

/**
 * Zips a theme by stylesheet and saves it to the playground uploads directory.
 *
 * @param string $stylesheet The theme stylesheet slug.
 * @return string Success or failure message.
 */
function qckply_zip_theme($stylesheet) {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    $source_directory = get_theme_root() . '/' . $stylesheet; //  Get theme path
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Theme '.$stylesheet.' zipped successfully! The zip file can be found at: ' . $qckply_uploads;
    } else {
        return 'Theme '.$stylesheet.' zip creation failed.';
    }
}

/**
 * Zips a plugin by slug and saves it to the playground uploads directory.
 *
 * @param string $slug The plugin slug.
 * @return string|bool Success message or false on failure.
 */
function qckply_zip_plugin($slug) {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads = $qckply_directories['uploads'];
    $source_directory = trailingslashit(dirname(plugin_dir_path(__FILE__))) .$slug; //  Get plugin path
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Plugin '.esc_html($slug).' zipped successfully! The zip file can be found at: ' . esc_html($qckply_uploads);
    } else {
        return false;
    }
}

/**
 * Replaces the sync origin URL with the site URL in incoming JSON.
 * Handles image URL replacements from the origin domain to the playground domain.
 * Supports multisite structures with 'sites' folders in the image path.
 *
 * @param string $json The JSON string.
 * @return string      Modified JSON string.
 */
function qckply_json_incoming($json) {

    $sync_origin = get_option('qckply_sync_origin');
    $playground = site_url();
    $data = json_decode($json);
    $json = json_encode($data, JSON_PRETTY_PRINT);
    //simplify by getting rid of some backslashes
    $json = str_replace('\/','/',$json);
    $json = str_replace('http:','https:',$json);
    //replace references to the base url
    $json = str_replace($sync_origin.'"',$playground.'"',$json);
    $json = str_replace($sync_origin.'\"',$playground.'\"',$json);

    $start = $json;

    $images = qckply_get_uploads_images();
    if(isset($_GET['page'])) {
    printf('<p>Images</p><pre>%s</pre>',var_export($images,true));
    }
    $pattern = '/' . preg_quote($sync_origin, '/') . '[^"\'\s]+/';
    $pattern = '/' . preg_quote($sync_origin, '/') . '[^"\'\s]*/';  // Note: */ instead of +/
    
    preg_match_all($pattern,$json,$matches);
    //printf('<pre>%s</pre>',var_export($matches,true));
    foreach($matches[0] as $url) {
        $url = trim($url,'\\');
        if(isset($_GET['page'])) {
        printf('<p>%s</p>',$url);
        }
        if(strpos($url,'wp-content') !== false) {
            preg_match('/\/[\d]{4}\/[\d]{2}\/.+/',$url,$match);
            if(!empty($match[0]) && in_array(trim($match[0]),$images)) {
                $replace = $playground.'/wp-content/uploads'.$match[0];
                $json = str_replace($url,$replace,$json);
                if(isset($_GET['page'])) {
                printf('<p><strong>Replace %s with %s</strong></p>',$url,$replace);
                }
            }
            elseif(isset($_GET['page'])) {
                echo '<p>No image match for wp-content url '.var_export($match,true).'</p>';
            }
        }
        else {
            if($sync_origin == trim($url,'/')) {
            printf('<p><strong>Skip base url %s</p>',$url);
            }
            else {
            $replace = str_replace($sync_origin,$playground,$url);
            printf('<p><strong>Replace non-image_url %s with %s</strong></p>',$url,$replace);
            $json = str_replace($url,$replace,$json);
            }
        }
    }
    $json = str_replace('/','\/',$json);

    if(isset($_GET['page'])) {
        printf('<h2>Starting Json</h2><pre>%s</pre>',htmlentities($start));
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<p>JSON Decode Error: ' . json_last_error_msg().'</p>';
        }
        else
            echo "<p>Json compiled successfully</p>";
        printf('<h2>Altered Json</h2><pre>%s</pre>',htmlentities($json));
    }
return $json;
}

function qckply_playground_path() {
    $url_parts = wp_parse_url(get_option('siteurl'));
    return isset($url_parts['path']) ? $url_parts['path'] : '';
}

/**
 * Replaces the site URL with the sync origin in outgoing JSON, and optionally rewrites image paths.
 *
 * @param string $json      The JSON string.
 * @param string $image_dir Optional image directory to rewrite.
 * @return string           Modified JSON string.
 */
function qckply_json_outgoing($json, $image_dir = '') {
    $sync_origin = get_option('qckply_sync_origin');
    $playground = site_url();
    $json = str_replace($playground,$sync_origin,$json);
    $search = str_replace('/','\/',$playground);
    $replace = str_replace('/','\/',$sync_origin);
    $json = str_replace($search,$replace,$json);
    return $json;
}

/**
 * Generates a fake user array for a given user ID using Faker.
 *
 * @param int $id The user ID.
 * @return array  Fake user data.
 */
$qckply_first_names = $qckply_last_names = array();
function qckply_fake_user($id = 0) {
    global $qckply_first_names, $qckply_last_names;
    if(empty($qckply_first_names) || empty($qckply_last_names)) {
        $qckply_first_names = array(
            'John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Diana', 'Ethan', 'Fiona',
            'George', 'Hannah', 'Ian', 'Julia', 'Kevin', 'Laura', 'Mike', 'Nina',
            'Oscar', 'Paula', 'Quentin', 'Rachel',
            'Samuel', 'Olivia', 'Liam', 'Emma', 'Noah', 'Ava', 'Mason', 'Sophia',
            'Logan', 'Isabella', 'Lucas', 'Mia', 'Jackson', 'Amelia', 'Aiden', 'Harper',
            'Elijah', 'Evelyn', 'Grayson', 'Abigail'
        );
        $qckply_last_names = array(
            'Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller',
            'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White',
            'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez',
            'Clark', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'King',
            'Wright', 'Scott', 'Green', 'Baker', 'Adams', 'Nelson', 'Hill', 'Ramirez',
            'Campbell', 'Mitchell', 'Roberts', 'Carter'
        );
        shuffle($qckply_first_names);
        shuffle($qckply_last_names);
    }
    $first_name = array_pop($qckply_first_names);
    $last_name = array_pop($qckply_last_names);
    $user=array('ID'=>$id,'first_name'=>$first_name,'last_name'=>$last_name);
    $user['display_name'] = $user['first_name'].' '.$user['last_name'];
    $user['user_login'] = preg_replace('/[^a-z0-9]/','',strtolower($user['display_name'])).random_int(1,100);
    $user['user_email'] = $user['user_login'] . '@example.com';
    $user['user_pass'] = wp_generate_password();
    return $user;
}

/**
 * Returns an array of plausible plugins for the playground, excluding certain plugins.
 *
 * @return array Array of plausible plugins.
 */
function qckply_plausible_plugins() {
    if(!function_exists('get_plugins'))
        require_once(ABSPATH.'/wp-admin/includes/plugins.php');
    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
    $plausible = array("active"=>array(), "inactive"=>array(),'active_names'=>array());
    $excluded_plugins = (is_multisite()) ? get_blog_option(1,'qckply_excluded_plugins',array()) : array();
    $exclude = array(
        'akismet/akismet.php',
        'hello.php',
        'hello-dolly/hello.php',
        'playground/playground.php',
        'quick-playground/quick-playground.php',
        'jetpack/jetpack.php',
        'wp-crontrol/wp-crontrol.php',
        'query-monitor/query-monitor.php',
    );
    $filterwords = array(
        'playground',
        'security',
        'spam',
        'cache',
        'caching',
        'comment',
        'admin',
    );    
    foreach($plugins as $plugin_file => $plugin_data) {
        if(in_array($plugin_file, $exclude)) {
            continue;
        }
        $parts = explode('/', $plugin_file);
        $slug = $parts[0];
        if(in_array($slug, $excluded_plugins)) {
            continue; // skip this plugin
        }
        foreach($filterwords as $word) {
            if(strpos($plugin_file, $word) !== false || strpos(strtolower($plugin_data['Name']), $word) !== false) {
                continue 2; // skip this plugin
            }
        }
        $is_active = in_array($plugin_file, $active_plugins);
        if($is_active) {
            $plausible['active'][] = $slug;
            $plausible['active_names'][] = $plugin_data['Name'];
        } else {
            $plausible['inactive'][$slug] = $plugin_data['Name'];
        }
    }
    return $plausible;
}

function qckply_cache_exists($profile = 'default') {
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    return file_exists($savedfile);
}

function qckply_caches($profile = 'default') {
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    $types = ['posts','settings','images','meta','custom','prompts'];
    $caches = [];
    foreach($types as $type) {
        $savedfile = $qckply_site_uploads.'/quickplayground_'.$type.'_'.$profile.'.json';
        if(file_exists($savedfile))
            $caches[] = $type;
    }
    return $caches;
}

function qckply_delete_caches($types,$profile = 'default') {
    if(in_array('all',$types))
        $types = ['posts','settings','images','meta','custom','prompts'];
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    foreach($types as $type) {
        $savedfile = $qckply_site_uploads.'/quickplayground_'.$type.'_'.$profile.'.json';
        if(file_exists($savedfile))
            wp_delete_file($savedfile);
    }
}

function qckply_cache_message($profile, $settings) {
    if(qckply_cache_exists($profile)) {
        if(empty($settings['qckply_no_cache']))
            $cachemessage = sprintf('<p>Cached content from past playground sessions will be displayed, unless you choose to <a href="%s#cachesettings">disable that feature</a>.</p>',esc_attr(admin_url('admin.php?page=qckply_builder')));
        else
            $cachemessage = sprintf('<p>Cached content from past playground sessions will be displayed, but <strong>will not be displayed</strong> unless you choose to <a href="%s#cachesettings">enable that feature</a>. Otherwise, cached content display will be toggled back on the next time you save Playground content.</p>',esc_attr(admin_url('admin.php?page=qckply_builder')));
    }
    else
        $cachemessage = '';
    return $cachemessage;
}

function qckply_is_playground() {
    if(isset($_SERVER['SERVER_NAME']) && ('playground.wordpress.net' == $_SERVER['SERVER_NAME']))
        return true;
    if(get_option('is_qckply_clone'))
        return true;
    return false;
}

function qckply_custom_tables() {
    global $wpdb;
    $core = [$wpdb->options,$wpdb->users,$wpdb->usermeta,$wpdb->posts,$wpdb->postmeta,$wpdb->terms,$wpdb->termmeta,$wpdb->term_relationships,$wpdb->term_taxonomy,$wpdb->comments,$wpdb->commentmeta,$wpdb->links];
    $custom = [];
    $tables = $wpdb->get_results('SHOW TABLES',ARRAY_N);
    foreach($tables as $row) {
        if(!in_array($row[0],$core))
        $custom[] = $row[0];
    }
    return $custom;
}

function qckply_custom_tables_clone($clone = array()) {
global $wpdb;
$custom_tables = qckply_custom_tables();
$clone['custom_tables'] = [];
if(empty($custom_tables))
    return $clone;
foreach($custom_tables as $table) {
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i",$table));
    if(!empty($results))
        $clone['custom_tables'][$table] = $results;
}
return $clone;
}

function qckply_get_prompt_messages() {
    $default_welcome = 'This is a virtual website created with <a href="https://wordpress.org/plugins/quick-playground">Quick Playground for WordPress</a>. You can edit this site without risk to any live website.';
    $data = get_option('qckply_messages',array());
    if(empty($data) || !is_array($data))
        $data = ['welcome'=>''];
    if(empty($data['welcome']))
        $data['welcome'] = $default_welcome;
    return $data;
}

function qckply_kses_allowed() {
    $allowed = wp_kses_allowed_html('post');
    $allowed2 = wp_kses_allowed_html('form');
    $allowed3 = array(
        'form' => array(
            'action' => true,
            'method' => true,
            'enctype' => true,
            'id' => true,
            'class' => true,
            'name' => true,
        ),
        'input' => array(
            'type' => true,
            'name' => true,
            'value' => true,
            'checked' => true,
            'id' => true,
            'class' => true,
            'placeholder' => true,
            'size' => true,
            'maxlength' => true,
            'min' => true,
            'max' => true,
            'step' => true,
            'readonly' => true,
            'disabled' => true,
            'autocomplete' => true,
        ),
        'select' => array(
            'name' => true,
            'id' => true,
            'class' => true,
            'multiple' => true,
            'size' => true,
            'disabled' => true,
        ),
        'script' => array(
            'src' => true,
        ),
        'option' => array(
            'value' => true,
            'selected' => true,
            'label' => true,
            'disabled' => true,
        ),
        'textarea' => array(
            'name' => true,
            'id' => true,
            'class' => true,
            'rows' => true,
            'cols' => true,
            'placeholder' => true,
            'maxlength' => true,
            'readonly' => true,
            'disabled' => true,
        ),
        'button' => array(
            'type' => true,
            'name' => true,
            'value' => true,
            'id' => true,
            'class' => true,
            'disabled' => true,
        ),
        'label' => array(
            'for' => true,
            'id' => true,
            'class' => true,
        ),
        'fieldset' => array(
            'id' => true,
            'class' => true,
            'name' => true,
        ),
        'legend' => array(
            'id' => true,
            'class' => true,
        ),
        'em' => array(),
        'iframe' => array(
            'src' => true,
            'name' => true,
            'id' => true,
            'class' => true,
            'style' => true,
            'width' => true,
            'height' => true,
            'sandbox' => true,
            'frameborder'     => true,
            'allowfullscreen' => true,
            'loading'         => true,
            'title'           => true,
        ),
        'strong' => array(),
        'p' => array('class' => true, 'id' => true),
        'span' => array('class' => true, 'id' => true),
        'br' => array(),
        'div' => array(
            'class' => true,
            'id' => true,
            'style' => true,
        ),
        'a' => array(
            'href' => true,
            'target' => true,
            'class' => true,
            'id' => true,
            'style' => true,
            'onmouseover' => true,
            'onmouseout' => true,
            'title' => true,
            'rel' => true,
        ),
        'svg' => array(
            'fill' => true,
            'height' => true,
            'width' => true,
            'version' => true,
            'id' => true,
            'xmlns' => true,
            'xmlns:xlink' => true,
            'viewBox' => true,
            'xml:space' => true,
        ),
    'path' => array(
        'd'    => true,
        'fill' => true,
    ),
    'g' => array(
        'fill' => true,
    ),
    'rect' => array(
        'x'         => true,
        'y'         => true,
        'width'     => true,
        'height'    => true,
        'transform' => true,
        'fill'      => true,
    ),
    'circle' => array(
        'cx' => true,
        'cy' => true,
        'r'  => true,
        'fill' => true,
    ),
    'title' => array(
        'title' => true,
    )
    );
    return array_merge($allowed, $allowed2, $allowed3);
}

function qckply_posts_related($clone, $debug = false) {
    $post_ids = $clone['ids'];
    global $wpdb;
    if(empty($clone['related']))
        $clone['related'] = [];
    foreach($post_ids as $post_id) {
      $pid = 'p'.intval($post_id);
      if(!empty($clone['related'][$pid]) && empty($_GET['nocache']))
        continue; //don't overwrite cached data if present
    $post = get_post($post_id);
    $clone['related'][$pid]['post_title'] = $post->post_title;
    $clone['related'][$pid]['post_type'] = $post->post_type;
    $clone['related'][$pid]['postmeta'] = $wpdb->get_results($wpdb->prepare("select * from %i where post_id=%d",$wpdb->postmeta,$post_id));
$cat = $wpdb->get_results($wpdb->prepare("SELECT p.ID, p.post_title, p.post_type, tr.*,tt.*, terms.*
  FROM %i AS p 
  LEFT JOIN %i AS tr ON tr.object_id = p.ID
  LEFT JOIN %i AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN %i AS terms ON terms.term_id = tt.term_id
 WHERE p.ID=%d AND tt.taxonomy IS NOT NULL",$wpdb->posts,$wpdb->term_relationships,$wpdb->term_taxonomy,$wpdb->terms,$post_id ));

 $terms = [];
 $tax = [];
        if(!empty($cat))
        foreach($cat as $c) {
            $clone['related'][$pid]['term_join'][$c->taxonomy][] = $c;
            if($c->object_id) {                
            $clone['related'][$pid]['term_relationships'][] = (object) array('object_id'=>$c->object_id,'term_order'=>$c->term_order,'term_taxonomy_id'=>$c->term_taxonomy_id);
            }
            if($c->term_taxonomy_id && !in_array($c->term_taxonomy_id,$tax)) {
            $clone['related'][$pid]['term_taxonomy'][] = (object) array('term_taxonomy_id'=>$c->term_taxonomy_id,'term_id'=>$c->term_id,'taxonomy'=>$c->taxonomy,'description'=>$c->description,'parent'=>$c->parent,'count'=>$c->count);
            $tax[] = $c->term_taxonomy_id;
            }
            if($c->term_id && !in_array($c->term_id,$terms)) 
            {
            $clone['related'][$pid]['terms'][] = (object) array('term_id'=>$c->term_id,'name'=>$c->name,'slug'=>$c->slug);
            $terms[] = $c->term_id;
            }
        }
    }
    return $clone;
}

function qckply_link($args = []) {
    if(empty($args)) {
        return site_url().get_option('qckply_landing','/');
    }
    else
        return add_query_arg($args,site_url());
}

function qckply_sanitize($data) {
    if(empty($data))
        return $data; // nothing to sanitize
    if(is_array($data))
        $data = array_map('qckply_sanitize',$data);
    elseif(strpos($data,'>'))
        $data = wp_kses($data, qckply_kses_allowed());
    elseif(strpos($data,"\n"))
        $data = sanitize_textarea_field($data);
    else
        $data = sanitize_text_field($data);
    return $data;
}

/**
 * Replaces variables in the blueprint with actual values for timestamp, key, and email.
 *
 * @param array  $blueprint The blueprint array.
 * @param string $key       The key value.
 * @param string $email     The email value.
 * @return array            Modified blueprint array.
 */
function qckply_fix_variables($blueprint) {
    $blueprint = apply_filters('qckply_fix_variables',$blueprint);
    $blueprint = json_encode($blueprint);
    $blueprint = apply_filters('qckply_blueprint_json',$blueprint);
    $blueprint = str_replace('TIMESTAMP',time(),$blueprint);
    return json_decode($blueprint, true);
}
add_filter('qckply_blueprint','qckply_fix_variables');

function qckply_get_social_image($sidebar_id) {
    global $wpdb;
    $thumb_id = get_post_thumbnail_id($sidebar_id);
    if(0 == $thumb_id)
        return ['src'=>plugins_url('images/quick-playground.png',__FILE__),'width'=>1544,'height'=>500];
    $post = get_post($thumb_id);
    $choice = [];
    $metadata = wp_get_attachment_metadata($thumb_id);
    if(!empty($metadata['height']) && $metadata['height'] > $metadata['width'])
        return ['src'=>plugins_url('images/quick-playground.png',__FILE__),'width'=>1544,'height'=>500]; // don't want landscape
    $basename = basename($post->guid);
    if(!empty($metadata['height']) && $metadata['width'] >= 1200 && $metadata['width'] < 2000)
    {
        return ['src'=>$post->guid,'width'=>$metadata['width'],'height'=>$metadata['height']];
    }
    else {
        $sizes = qckply_image_largest_smallest($metadata['sizes']);
        foreach($sizes as $label => $s) {
            if($s['height'] > $s['width'])
                break; //no landscape
            if($s['width'] < 2000 && $s['width'] > 800)
            {
                return ['src'=>str_replace($basename,$s['file'],$post->guid),'width'=>$s['width'],'height'=>$s['height']];
            }
        }
    }
    return ['src'=>plugins_url('images/quick-playground.png',__FILE__),'width'=>1544,'height'=>500]; // if nothing else matched, use default
}

function qckply_image_largest_smallest($image_sizes) {
usort($image_sizes, function($a, $b) {
    if($a['filesize'] == $b['filesize']) return 0;
    return $a['filesize'] < $b['filesize'] ? 1 : -1; // PHP 7+ spaceship operator for concise comparison
});
return $image_sizes;
}

function qckply_hits($profile) {
    $hits = get_option('qckply_hits',['default'=>0]);
    $hits[$profile] = isset($hits[$profile]) ? $hits[$profile] + 1 : 1;
    update_option('qckply_hits',$hits);
    return $hits;
}

function qckply_show_hits() {
    $hits = get_option('qckply_hits',['default'=>0]);
    echo '<h3>'.esc_html__('Views','quick-playground').'</h3><ul>';
    foreach($hits as $profile => $count) {
        printf('<li>%s: %d</li>',esc_html($profile),intval($count));
    }
    echo '</ul>';
}

/**
 * Get images (attachments + featured + content-embedded) for a post and their sizes.
 *
 * @param int $post_id Post ID.
 * @return array Array of images with keys: ID (may be 0 for external), file, url, metadata, sizes (name => [file,url,width,height])
 */
$qckply_embedded_checked = [];
$qckply_image_urls = [];
function qckply_get_post_images_with_sizes( $post, $debug =false, $get_all_attachments = false, $omit_unused_sizes = false ) {
    global $qckply_embedded_checked, $qckply_image_urls, $qckply_attachments_per_post_limit, $qckply_skipped_attachment_urls;
    $uploads = wp_get_upload_dir();

    if(is_array($post)) {
        $post = (object) $post;
    }
    $images = array();
    if ( empty( $post ) || empty($post->ID) ) {
        return $images;
    }
    $post_id = $post->ID;
    //if($debug) printf('<p>checking images for %s</p>',$post->post_title);

    $title = get_the_title($post_id);
    $upload = wp_upload_dir();

    // Helper to add an attachment once
    $added = array();
    $add_attachment = function( $att_id ) use ( &$images, &$added, $upload ) {

        if ( ! $att_id || isset( $added[ $att_id ] ) ) {
            return;
        }
        $added[ $att_id ] = true;

        $meta = wp_get_attachment_metadata( $att_id );
        $file_rel = isset( $meta['file'] ) ? $meta['file'] : _wp_relative_upload_path( get_attached_file( $att_id ) );
        $base_dir = untrailingslashit( $upload['basedir'] );
        $base_url = untrailingslashit( $upload['baseurl'] );

        $full_file = $file_rel ? $base_dir . '/' . $file_rel : get_attached_file( $att_id );
        $url = wp_get_attachment_url( $att_id );

        $sizes = array();
        if ( ! empty( $meta['sizes'] ) && ! empty( $file_rel ) ) {
            $dir = dirname( $file_rel );
            foreach ( $meta['sizes'] as $size_name => $s ) {
                if ( empty( $s['file'] ) ) {
                    continue;
                }
                $sizes[ $size_name ] = array(
                    'file'   => $base_dir . '/' . $dir . '/' . $s['file'],
                    'url'    => $base_url . '/' . $dir . '/' . $s['file'],
                    'width'  => isset( $s['width'] ) ? intval( $s['width'] ) : 0,
                    'height' => isset( $s['height'] ) ? intval( $s['height'] ) : 0,
                );
            }
        }

        $images[] = array(
            'ID'       => (int) $att_id,
            'file'     => $full_file,
            'url'      => $url,
            'metadata' => $meta,
            'sizes'    => $sizes,
        );
    };

    // 1) Attached images (attachment post_parent = $post_id)
    if($get_all_attachments) {
    $args = array(
        'post_parent'    => $post_id,
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'numberposts'    => -1,
        'post_status'    => 'any',
    );
    $attachments = get_posts( $args );
    if ( $attachments ) {
        foreach ( $attachments as $att ) {
            $add_attachment( $att->ID );
        }
    }

    if ( $post && ! empty( $post->post_content ) ) {
    // Images embedded in post content (may not be attached to parent post). Use regex to find src and map to attachment IDs.
    preg_match_all('/<!--\swp:image\s{\s*"id"\s*:\s*([0-9]+)\s*,/i', $post->post_content, $m);    
        if ( !empty( $m ) && ! empty( $m[1] ) ) {
            $image_block_ids = array_unique( $m[1] );
            foreach ( $image_block_ids as $img_id ) {
                //get attachment url from id
                $url = wp_get_attachment_url( $img_id );
                if(empty($url))
                    continue;
                if(in_array($url,$qckply_image_urls))
                    continue;//already checked
                $qckply_image_urls[] = $url;
                $add_attachment( $img_id );
            }
        }
        if ( preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $m ) ) {
            $urls = array_unique( $m[1] );
            foreach ( $urls as $img_url ) {
                if(in_array($img_url,$qckply_image_urls))
                    continue;//already checked
                $qckply_image_urls[] = $img_url;
                // Try to find attachment ID from URL
                $att_id = attachment_url_to_postid( $img_url );
                if ( $att_id ) {
                    $add_attachment( $att_id );
                    continue;
                }
                // Not in wp-uploads or not an attachment
                if(strpos($img_url,$uploads['baseurl']) !== false)
                {
                //local file, no attachment
                $images[] = array(
                    'ID'       => 0,
                    'file'     => str_replace($uploads['baseurl'],$uploads['basedir'],$img_url),
                    'url'      => $img_url,
                    'metadata' => array(),
                    'sizes'    => array(),
                );
                }
            }
        }
        if ( preg_match_all( '/{"ref":(\d+)}/i', $post->post_content, $m ) ) {
            $embedded_posts = array_unique( $m[1] );
            foreach($embedded_posts as $epost_id) {
                if(!in_array($epost_id,$qckply_embedded_checked)) {
                    $qckply_embedded_checked[] = $epost_id;
                    $more_images = qckply_get_post_images_with_sizes( $epost_id, $debug =false );
                    if($debug) printf('<p>%s checking for images in embedded post %d %s</p>',$title,$epost_id,var_export($moreimages,true));
                    $images = array_merge($images,$more_images);
                }
            }
        }
    }
    

    }// end get_all_attachments

    // 2) Featured image (post meta '_thumbnail_id')
    $thumb_id = get_post_thumbnail_id( $post_id );
    if ( $thumb_id ) {
        if($debug) printf('<p>post %d thumb %d</p>',$post_id,$thumb_id);
        $add_attachment( $thumb_id );
    }

    $qckply_uploaded_images = get_option('qckply_uploaded_images',[]);

    //images saved from playground, may not be part of live content
    if(!empty($qckply_uploaded_images)) {
        foreach($qckply_uploaded_images as $upid)
            if(empty($added[$upid])) {
                $add_attachment( $upid );                
            }
    }

    $all_images = [];
    foreach($images as $index => $img) {
        if ( ! empty( $img['sizes'] ) ) {
            if($img['ID'] == $thumb_id)
            {
                if($debug) printf('<p>including thumbnail %s</p>',$img['url']);
                continue;//skip size filter
            }
            elseif(in_array($img['ID'],$qckply_uploaded_images)) {
                //if($debug) printf('<p>including %s</p>',$img['url']);
                continue;//uploaded from playground
            }
            else {
                if($omit_unused_sizes) {
                if(!empty($urls) && !in_array($img['url'],$urls)) {
                    $images[$index]['skip'] = 1;//skip download of full size version
                }
                foreach ( $img['sizes'] as $name => $info ) {
                    if($info['ID'] == $thumb_id)
                        continue;
                    if(!empty($urls) && in_array($info['url'],$urls)) {
                    //if($debug) printf('<p>found %s %s in<br>%s</p>',$info['url'],$name,var_export($urls,true));
                    }
                    else {
                    //if($debug) printf('<p><strong>not found %s %s</strong> against <br>%s</p>',$info['url'],$name,var_export($urls,true));
                    unset($images[$index]['sizes'][$name]);
                    }
                }

                }                
            }

        }
    }

    return $images;
}

function qckply_get_site_images( $profile ='default',$debug =false ) {
    if($debug) echo '<p>checking site logo and site icon</p>';

    $images = array();
    // Helper to add an attachment once
    $added = array();
    $upload = wp_upload_dir();
    $add_attachment = function( $att_id ) use ( &$images, &$added, $upload ) {

        if ( ! $att_id || isset( $added[ $att_id ] ) ) {
            return;
        }
        $added[ $att_id ] = true;
        $meta = wp_get_attachment_metadata( $att_id );
        $file_rel = isset( $meta['file'] ) ? $meta['file'] : _wp_relative_upload_path( get_attached_file( $att_id ) );
        $base_dir = untrailingslashit( $upload['basedir'] );
        $base_url = untrailingslashit( $upload['baseurl'] );

        $full_file = $file_rel ? $base_dir . '/' . $file_rel : get_attached_file( $att_id );
        $url = wp_get_attachment_url( $att_id );

        $sizes = array();
        if ( ! empty( $meta['sizes'] ) && ! empty( $file_rel ) ) {
            $dir = dirname( $file_rel );
            foreach ( $meta['sizes'] as $size_name => $s ) {
                if ( empty( $s['file'] ) ) {
                    continue;
                }
                $sizes[ $size_name ] = array(
                    'file'   => $base_dir . '/' . $dir . '/' . $s['file'],
                    'url'    => $base_url . '/' . $dir . '/' . $s['file'],
                    'width'  => isset( $s['width'] ) ? intval( $s['width'] ) : 0,
                    'height' => isset( $s['height'] ) ? intval( $s['height'] ) : 0,
                );
            }
        }

        $images[] = array(
            'ID'       => (int) $att_id,
            'file'     => $full_file,
            'url'      => $url,
            'metadata' => $meta,
            'sizes'    => $sizes,
        );
    };
    $site_logo = get_option('site_logo');
    if(!empty($site_logo)) {
        $add_attachment( $site_logo );
    }
    $site_icon = get_option('site_icon');
    if(!empty($site_icon)) {
        $add_attachment( $site_icon );
    }
    $profile_images = get_option('qckply_profile_images_'.$profile,[]);
    if(!empty($profile_images)) {
        foreach($profile_images as $img_id) {
            $add_attachment( $img_id );
        }
    }
    return $images;
}

/**
 * Zips all images for a profile and saves to uploads/quick-playground directory.
 *
 * @param string $profile The profile name.
 * @return string|bool    Success message or false on failure.
 */
function qckply_zip_images($profile,$clone,$debug = false) {
    $qckply_directories = qckply_get_directories();
    $get_all_attachments = get_option('qckply_get_all_attachments',false);
    $qckply_uploads = $qckply_directories['site_uploads'];
    $upload = wp_get_upload_dir();
    $att_ids = [];
    $all_images = [];
    if($debug) printf('<p>Running zip_images function for %d posts</p>',empty($clone['posts']) ? 0 : sizeof($clone['posts']));
    foreach($clone['posts'] as $post) {
        $images = qckply_get_post_images_with_sizes( $post, $debug, $get_all_attachments );
        if(is_array($post))
            $post = (object) $post;
        if(!empty($images) && sizeof($images) > 1)
        {
            if($debug) printf('<p>Post %s %d has %d images</p>',$post->post_title,$post->ID,sizeof($images));
        }
        foreach($images as $img) {
            $att_ids[] = $img['ID'];
            //if($debug) printf('<p>Post %s %d image %s</p>',$post->post_title,$post->ID,$img['url']);
            if(empty($img['skip'])) {
                $all_images[] = $img['file'];
            }
            if ( ! empty( $img['sizes'] ) ) {
                foreach ( $img['sizes'] as $name => $info ) {
                    if(!empty($info['file']))
                        $all_images[] = $info['file'];
                }
            }
        }
    }

    $featured_posts = get_option('qckply_featured_posts_'.$profile,[]);
    $front_page_id = get_option('page_on_front');
    if(!in_array($front_page_id,$featured_posts))
        $featured_posts[] = $front_page_id;
    foreach($featured_posts as $post_id) {
        $post = get_post($post_id);
        $images = qckply_get_post_images_with_sizes( $post, $debug, true );
        if(is_array($post))
            $post = (object) $post;
        if(!empty($images) && sizeof($images) > 1)
        {
            if($debug) printf('<p>Post %s %d has %d images</p>',$post->post_title,$post->ID,sizeof($images));
        }
        foreach($images as $img) {
            $att_ids[] = $img['ID'];
            //if($debug) printf('<p>Post %s %d image %s</p>',$post->post_title,$post->ID,$img['url']);
            if(empty($img['skip'])) {
                $all_images[] = $img['file'];
            }
            else {
                if($debug) printf('<p>Marked as "skip" %s</p>',var_export($img,true));
            }
            if ( ! empty( $img['sizes'] ) ) {
                foreach ( $img['sizes'] as $name => $info ) {
                    if(!empty($info['file']))
                        $all_images[] = $info['file'];
                }
            }
        }
    }

    $images = qckply_get_site_images( $profile, $debug );
    foreach($images as $img) {
        $att_ids[] = $img['ID'];
        if(!in_array($img['file'],$all_images));
            $all_images[] = $img['file'];
        //if($debug) printf('<p>including %s + %d sizes</p>',$img['url'],empty($img['sizes']) ? 0 : sizeof($img['sizes']));
        if ( ! empty( $img['sizes'] ) ) {
            foreach ( $img['sizes'] as $name => $info ) {
                if(!empty($info['file']) && !in_array($info['file'],$all_images))
                    $all_images[] = $info['file'];
            }
        }
    }

    if(empty($all_images)) {
        return false; // No images to zip
    }

    // Get WordPress upload directory info
    $upload = wp_upload_dir();
    $base_dir = untrailingslashit( $upload['basedir'] );

    // Create zip file
    $zip = new ZipArchive();
    $zip_filename = sanitize_file_name($profile) . '_images.zip';
    $zip_filepath = $qckply_uploads . '/' . $zip_filename;

    if ($zip->open($zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        error_log(sprintf('<p>Zip creation failed for %s</p>',$zip_filepath));
        return false; // Zip file creation failed
    }

    // Add each image file to zip with /wp-content/uploads relative path
    $added = [];
    $notfound = [];
    foreach ($all_images as $file_path) {
        if(empty($file_path))
            continue;
        if(strpos($file_path,$base_dir) === false) {
            $file_path = $base_dir . $file_path;
        }
        if (in_array($file_path, $added)) {
            continue;
        }
        if (!file_exists($file_path)) {
            $notfound[] = 'not found '.$file_path;
            continue;
        }
        $added[] = $file_path;

        // Build the relative path: wp-content/uploads/...
        $relative_path = str_replace($base_dir . '/', '', $file_path);
        $relative_path = 'wp-content/uploads/' . ltrim($relative_path, '/');

        //if($debug) printf('<p>adding %s</p>',$file_path);
        $result = $zip->addFile($file_path, $relative_path);
        if(!$result || $debug) {
        $error = sprintf('<p>file path %s<br />relative %s<br />%s</p>',$file_path,$relative_path,var_export($result,true));
        error_log($error);
        if($debug)
            echo $error;
        }

    }

    if (!$zip->close()) {
        if (method_exists($zip, 'getStatusString')) error_log('zip status text: ' . $zip->getStatusString());
        return false; // Failed to close zip
    }

    foreach($att_ids as $id) {
        if(!$id)
            continue;
        $post = get_post($id);
        if(empty($post)) {
            $clone['missing_attachments'][] = $id;
            continue;
        }
        $clone['posts'][] = $post;
        $clone['ids'][] = $id;
    }
    $site_url = get_site_url();
    foreach($added as $index => $file) {
        $added[$index] = str_replace($base_dir, '/wp-content/uploads', $file);
    }
    $clone['added_images'] = $added;
    $clone['not_found'] = $notfound;
    $clone['images_zip'] = sprintf(
        __('Images zipped successfully! %d files added. Zip file: %s', 'quick-playground'),
        count($added),
        esc_html($zip_filename)
    );
    return $clone;
}

function qckply_client_images($clone) {
    foreach($clone['posts'] as $post) {
        $images = qckply_get_post_images_with_sizes( $post, $debug );
        if(!empty($images))
        foreach($images as $img) {
            $clone['ids'][] = $img['ID'];
        }
    }
   return $clone;
}

//disable because we've limited the number of sizes downloaded to playground
add_filter( 'wp_calculate_image_srcset', function( $attr ) {
    if(qckply_is_playground()) {
        return false;
    }
    return $attr;
}, 10, 3 );

function qckply_theme_template_tracking_key($post_id, $saved) {
    $pid = 'p'.$id;
    if(!is_array($saved) || empty($saved['related']) || empty($saved['related'][$pid]) || empty($saved['related'][$pid]['term_join']) || empty($saved['related'][$pid]['term_join']['wp_theme']) )
        return;
    ksort($saved['related'][$pid]['term_join']);
    $track = $saved_post['post_title'];
    foreach($saved['related'][$pid]['term_join'] as $taxonomy => $values_array) {
        foreach($values_array as $values) {
            $track .= '/'.$taxonomy.':'.$values['name'];
        }
    }
    return $track;
}

/**
 * Recursively scans the wp-content/uploads directory and returns an array of all image file paths.
 *
 * @return array Array of relative file paths starting from wp-content for all images found in the uploads directory.
 */
function qckply_get_uploads_images() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $images = array();
    
    // Supported image extensions
    $image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'ico' );
    
    if ( ! is_dir( $base_dir ) ) {
        return $images;
    }
    
    // Recursive directory iterator
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $base_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ( $iterator as $file ) {
        if ( $file->isFile() ) {
            $extension = strtolower( pathinfo( $file->getPathname(), PATHINFO_EXTENSION ) );
            if ( in_array( $extension, $image_extensions, true ) ) {
                $filepath = $file->getPathname();
                // Strip everything before and including wp-content
                if ( strpos( $filepath, 'wp-content' ) !== false ) {
                    $filepath = substr( $filepath, strpos( $filepath, 'wp-content' ) + 18 );
                    $images[] = $filepath;
                }
            }
        }
    }
    
    return $images;
}

