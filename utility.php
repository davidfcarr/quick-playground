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
    if(get_option('is_qckply_clone',false))
    {
        if(get_option('qckply_is_demo',false))
            return;

        $args = array(
            'id'    => 'playground',
            'title' => 'Playground',
            'href'  => admin_url('admin.php?page=qckply_clone_page'),
            'parent' => 'site-name',
            'meta'  => array( 'class' => 'playground' )
        );    
        $wp_admin_bar->add_node( $args );
        $args = array(
            'id'    => 'playground-import',
            'title' => 'Playground Import Log',
            'href'  => admin_url('admin.php?page=qckply_clone_log'),
            'parent' => 'site-name',
            'meta'  => array( 'class' => 'playground' )
        );    
        $wp_admin_bar->add_node( $args );
    }
    else {
        $args = array(
            'id'    => 'qckply',
            'title' => 'Quick Playground',
            'href'  => admin_url('admin.php?page=quickplayground'),
            'parent' => 'site-name',        
            'meta'  => array( 'class' => 'qckply' )
        );
        $wp_admin_bar->add_node( $args );
        $args = array(
            'id'    => 'qckply-builder',
            'title' => 'Playground Builder',
            'href'  => admin_url('admin.php?page=qckply_builder'),
            'parent' => 'qckply',        
            'meta'  => array( 'class' => 'qckply' )
        );
        $wp_admin_bar->add_node( $args );
    }
}

/**
 * Registers admin menu pages for the Design Playground plugin.
 */
add_action('admin_menu', 'qckply_design_qckply_menus');
function qckply_design_qckply_menus() {
    if(get_option('is_qckply_clone',false)) {
        if(get_option('qckply_is_demo',false))
            return;
        add_menu_page('Quick Playground', 'Quick Playground', 'manage_options', 'qckply_clone_page', 'qckply_clone_page','data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==',61);
        add_submenu_page('qckply_clone_page','Import Log', 'Import Log', 'manage_options', 'qckply_clone_log', 'qckply_clone_log');
    }
    else {
        add_menu_page('Quick Playground', 'Quick Playground', 'manage_options', 'quickplayground', 'quickplayground','data:image/svg+xml;base64,PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==', 61);
        add_submenu_page('quickplayground','Playground Builder', 'Playground Builder', 'manage_options', 'qckply_builder', 'qckply_builder');
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
    $sql = "SELECT * FROM $wpdb->postmeta where post_id IN (".implode(',',$ids).") ";
    return $wpdb->get_results($sql);
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

    error_log('source dir for zip iterator '.$source_dir);
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
        error_log('zip creation failed for '.$source_directory);
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
    error_log($source_directory." to ".$qckply_uploads );
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
    error_log($source_directory." to ".$qckply_uploads );
    if (qckply_zipToUploads($source_directory, $qckply_uploads)) {
        return 'Plugin '.esc_html($slug).' zipped successfully! The zip file can be found at: ' . esc_html($qckply_uploads);
    } else {
        error_log('Plugin '.esc_html($slug).' zip creation failed for '.esc_html($source_directory.' '.$qckply_uploads));
        return false;
    }
}

/**
 * Debug filter for blueprints, removes steps of type 'defineWpConfigConsts'.
 *
 * @param array $blueprint The blueprint array.
 * @return array           Filtered blueprint.
 */
function playground_blueprint_debug_filter($blueprint) {
    foreach($blueprint['steps'] as $index => $s) {
        if($s['step'] == 'defineWpConfigConsts')
            $blueprint['steps'][$index] = null;
    }
    $blueprint['steps'] = array_values(array_filter($blueprint['steps']));
    return $blueprint;
}

/**
 * Replaces the sync origin URL with the site URL in incoming JSON.
 *
 * @param string $json The JSON string.
 * @return string      Modified JSON string.
 */
function qckply_json_incoming($json) {
    $sync_origin = 'href=\"'.str_replace("/","\/",get_option('qckply_sync_origin'));
    $mysite_url = 'href=\"'.str_replace("/","\/",rtrim(get_option('siteurl'),'/'));
    return str_replace($sync_origin,$mysite_url,$json);
}

/**
 * Replaces the site URL with the sync origin in outgoing JSON, and optionally rewrites image paths.
 *
 * @param string $json      The JSON string.
 * @param string $image_dir Optional image directory to rewrite.
 * @return string           Modified JSON string.
 */
function qckply_json_outgoing($json, $image_dir = '') {
    $sync_origin = str_replace("/","\/",get_option('qckply_sync_origin'));
    $mysite_url = str_replace("/","\/",rtrim(get_option('siteurl'),'/'));
    if($image_dir) {
        $pattern = '~'.str_replace("\\","\\\\",$mysite_url.'\/wp-content\/uploads').'([^"]+\.[A-Za-z0-9/]{3,4})~ix'; //+
        $replacement = str_replace("/","\/",$image_dir).'$1';
        $json = preg_replace($pattern, $replacement, $json);
    }
    return str_replace($mysite_url,$sync_origin,$json);
}

/**
 * Generates a fake user array for a given user ID using Faker.
 *
 * @param int $id The user ID.
 * @return array  Fake user data.
 */
$first_names = $last_names = array();
function qckply_fake_user($id = 0) {
    global $first_names, $last_names;
    if(empty($first_names) || empty($last_names)) {
        $first_names = array(
            'John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Diana', 'Ethan', 'Fiona',
            'George', 'Hannah', 'Ian', 'Julia', 'Kevin', 'Laura', 'Mike', 'Nina',
            'Oscar', 'Paula', 'Quentin', 'Rachel',
            'Samuel', 'Olivia', 'Liam', 'Emma', 'Noah', 'Ava', 'Mason', 'Sophia',
            'Logan', 'Isabella', 'Lucas', 'Mia', 'Jackson', 'Amelia', 'Aiden', 'Harper',
            'Elijah', 'Evelyn', 'Grayson', 'Abigail'
        );
        $last_names = array(
            'Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller',
            'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White',
            'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez',
            'Clark', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'King',
            'Wright', 'Scott', 'Green', 'Baker', 'Adams', 'Nelson', 'Hill', 'Ramirez',
            'Campbell', 'Mitchell', 'Roberts', 'Carter'
        );
        shuffle($first_names);
        shuffle($last_names);
    }
    $first_name = array_pop($first_names);
    $last_name = array_pop($last_names);
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
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    foreach($types as $type) {
        $savedfile = $qckply_site_uploads.'/quickplayground_'.sanitize_text_field($type).'_'.$profile.'.json';
        if(file_exists($savedfile))
            unlink($savedfile);
    }
    return $caches;
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
    if(('playground.wordpress.net' == $_SERVER['SERVER_NAME']))
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
    $results = $wpdb->get_results("SELECT * FROM $table");
    if(!empty($results))
        $clone['custom_tables'][$table] = $results;
}
return $clone;
}

function qckply_get_prompts($profile) {
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    $baseurl = get_option('qckply_sync_origin');
    $file = $qckply_site_uploads.'/qckply_prompts_'.$profile.'.json';
    $json = file_get_contents($file);
    $prompts = ['welcome'=>'','admin-welcome'=>''];
    if(!empty($json))
    {
        $data = json_decode($json,true);
        if(is_array($data) || !empty($data))
            $prompts = $data;
    }
    return $prompts;
}

function qckply_set_prompts($prompts,$profile) {
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads']; 
    $file = $qckply_site_uploads.'/qckply_prompts_'.$profile.'.json';
    $json = json_encode($prompts);
    $return = file_put_contents($file,$json);
    return $return;
}

function qckply_get_prompts_remote($profile) {
    $origin_directories = get_option('qckply_origin_directories');
    $url = $origin_directories['site_uploads_url'].'/quickplayground_prompts_'.$profile.'.json?t='.time();
    $response = wp_remote_get($url);
    if(is_wp_error($response)) {
        echo '<p>Error: '.esc_html($response->get_error_message()).$url.'</p>';
    } else {
        $promptjson = $response['body'];
    }
    if(!empty($promptjson))
        $data = json_decode($promptjson,true);
    if(empty($data) || !is_array($data))
        $data = ['welcome'=>'','admin-welcome'=>''];
    if(!isset($data['welcome']))
        $data['welcome'] = '';
    if(!isset($data['admin-welcome']))
        $data['admin-welcome'] = '';
    return $data;
}

function qckply_kses_allowed() {
    return array(
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
        'g' => array(),
        'path' => array(
        'd' => true,
        ),
    );
}

function qckply_posts_related($post_ids) {
    global $wpdb;
    $related = [];
    foreach($post_ids as $post_id) {
      $pid = 'p'.intval($post_id);
        $sql = "SELECT p.ID, p.post_title, p.post_type, tr.*,tt.*, terms.*
  FROM $wpdb->posts AS p 
  LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = p.ID
  LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN $wpdb->terms AS terms ON terms.term_id = tt.term_id
 WHERE p.ID=".intval($post_id)." AND p.post_status='publish'";
  
$cat = $wpdb->get_results($sql);

 $terms = [];
 $tax = [];
        if(!empty($cat))
        foreach($cat as $c) {
            $related[$pid]['post_title'] = $c->post_title;
            $related[$pid]['post_type'] = $c->post_type;
            $related[$pid]['postmeta'] = $wpdb->get_results("select * from $wpdb->postmeta where post_id=".intval($post_id));
            if($c->object_id)
            $related[$pid]['term_relationships'][] = (object) array('object_id'=>$c->object_id,'term_order'=>$c->term_order,'term_taxonomy_id'=>$c->term_taxonomy_id);
            if($c->term_taxonomy_id && !in_array($c->term_taxonomy_id,$tax)) {
            $related[$pid]['term_taxonomy'][] = (object) array('term_taxonomy_id'=>$c->term_taxonomy_id,'term_id'=>$c->term_id,'taxonomy'=>$c->taxonomy,'description'=>$c->description,'parent'=>$c->parent,'count'=>$c->count);
            $tax[] = $c->term_taxonomy_id;
            }
            if($c->term_id && !in_array($c->term_id,$terms)) 
            {
            $related[$pid]['terms'][] = (object) array('term_id'=>$c->term_id,'name'=>$c->name);
            $terms[] = $c->term_id;
            }
        }
    }
    return $related;
}

function qckply_link($args = []) {
    if(is_admin()) {
    $urlparts = parse_url(sanitize_text_field($_SERVER['REQUEST_URI']));
    $basename = basename($urlparts['path']);
    $get = $_GET; //get the current admin query parameters
    foreach($get as $key => $value) {
        $get[$key] = sanitize_text_field($value);
    }
        if(isset($args['qckply_clone']))
            $get['qckply_clone'] = $args['qckply_clone'];
        else {
            unset($get['qckply_clone']);
        }
        $link = add_query_arg($get,admin_url($basename));
    }
    else {
        $link = add_query_arg($args,get_permalink());
    }
    return $link;
}

function qckply_sanitize_clone($data) {
    if(is_array($data))
        $data = array_map('qckply_sanitize_clone',$data);
    elseif(strpos($data,'>'))
        $data = wp_kses_post($data);
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
    $key = empty($_GET['key']) ? '' : sanitize_text_field($_GET['key']);
    $email = apply_filters('qckply_blueprint_email',get_option('admin_email'));
    if($key)
        $blueprint = qckply_change_blueprint_setting($blueprint, array('qckply_is_demo'=>false,'playground_premium_enabled'=>$key,'playground_premium_expiration'=>4070908800));
    $blueprint = json_encode($blueprint);
    $blueprint = str_replace('TIMESTAMP',time(),$blueprint);
    $blueprint = str_replace('PROCODE',$key,$blueprint);
    $blueprint = str_replace('PROEMAIL',$email,$blueprint);
    return json_decode($blueprint, true);
}
