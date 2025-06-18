<?php

function quickplayground_repo_check($urlOrSlug, $type = 'plugin') {

    $basename = str_replace('/', '', basename($urlOrSlug));

    if('theme' == $type) {

        require_once( ABSPATH . 'wp-admin/includes/theme-install.php' );

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



add_action( 'admin_bar_menu', 'quickplayground_toolbar_link', 9999 );

function quickplayground_toolbar_link( $wp_admin_bar ) {



    if(get_option('is_playground_clone',false))

    {

        $args = array(

            'id'    => 'playground',

            'title' => 'Design Playground (Clone)',

            'href'  => admin_url('admin.php?page=quickplayground_clone_page'),

            'parent' => 'site-name',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );
        $args = array(

            'id'    => 'playground-log',

            'title' => 'Design Playground Clone Log',

            'href'  => admin_url('admin.php?page=quickplayground_clone_log'),

            'parent' => 'site-name',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );
    }

    else {

        $args = array(

            'id'    => 'playground',

            'title' => 'Design Playground',

            'href'  => admin_url('admin.php?page=quickplayground'),

            'parent' => 'site-name',        

            'meta'  => array( 'class' => 'playground' )

        );
	$wp_admin_bar->add_node( $args );

    }

}

add_action('admin_menu', 'quickplayground_design_playground_menus');

function quickplayground_design_playground_menus() {

    if(get_option('is_playground_clone',false)) {
        add_menu_page('Quick Playground (Clone)', 'Quick Playground (Clone)', 'manage_options', 'quickplayground_clone_page', 'quickplayground_clone_page','data:image/svg+xml;base64, PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==',61);
        add_submenu_page('quickplayground_clone_page','Import Log', 'Import Log', 'manage_options', 'quickplayground_clone_log', 'quickplayground_clone_log');
    }
    else {
        add_menu_page('Quick Playground', 'Quick Playground', 'manage_options', 'quickplayground', 'quickplayground','data:image/svg+xml;base64, PHN2ZyBmaWxsPSIjRkZGRkZGIiBoZWlnaHQ9IjgwMHB4IiB3aWR0aD0iODAwcHgiIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIAoJIHZpZXdCb3g9IjAgMCA1MTIuMDAxIDUxMi4wMDEiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik01MDEuMzM1LDE3MC41ODdoLTM1MnYtMjEuMzMzaDEwLjY2N2M0LjE4MSwwLDcuOTc5LTIuNDUzLDkuNzI4LTYuMjUxYzEuNzI4LTMuODE5LDEuMDY3LTguMjk5LTEuNjg1LTExLjQzNQoJCQlMOTMuMzc3LDQ2LjIzNWMtNC4wNTMtNC42NTEtMTIuMDExLTQuNjUxLTE2LjA2NCwwTDIuNjQ3LDEzMS41NjljLTIuNzUyLDMuMTU3LTMuNDM1LDcuNjE2LTEuNjg1LDExLjQzNQoJCQljMS43MjgsMy43OTcsNS41MjUsNi4yNTEsOS43MDcsNi4yNTFoMTAuNjY3djMwOS4zMzNjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N3MxMC42NjctNC43NzksMTAuNjY3LTEwLjY2N3YtMTAuNjY3CgkJCWg4NS4zMzN2MTAuNjY3YzAsNS44ODgsNC43NzksMTAuNjY3LDEwLjY2NywxMC42NjdzMTAuNjY3LTQuNzc5LDEwLjY2Ny0xMC42NjdWMTkxLjkyMWg2NFYzMDAuNTUKCQkJYy0xMi4zOTUsNC40MTYtMjEuMzMzLDE2LjE0OS0yMS4zMzMsMzAuMDM3YzAsMTcuNjQzLDE0LjM1NywzMiwzMiwzMnMzMi0xNC4zNTcsMzItMzJjMC0xMy44ODgtOC45MzktMjUuNjIxLTIxLjMzMy0zMC4wMzcKCQkJVjE5MS45MjFoODUuMzMzdjg3LjI5NmMtMTIuMzk1LDQuNDE2LTIxLjMzMywxNi4xNDktMjEuMzMzLDMwLjAzN2MwLDE3LjY0MywxNC4zNTcsMzIsMzIsMzJjMTcuNjQzLDAsMzItMTQuMzU3LDMyLTMyCgkJCWMwLTEzLjg4OC04LjkzOS0yNS42MjEtMjEuMzMzLTMwLjAzN3YtODcuMjk2aDY0djI2Ni42NjdjMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3CgkJCVYxOTEuOTIxaDQyLjY2N3YyMy41NzNjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDcKCQkJYy02LjQsMi42NjctMTAuNjY3LDcuNTMxLTEwLjY2NywxMy43NnM0LjI2NywxMS4wOTMsMTAuNjY3LDEzLjc2djMxLjE0N2MtNi40LDIuNjY3LTEwLjY2Nyw3LjUzMS0xMC42NjcsMTMuNzYKCQkJYzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYzMS4xNDdjLTYuNCwyLjY2Ny0xMC42NjcsNy41MzEtMTAuNjY3LDEzLjc2YzAsNi4yMjksNC4yNjcsMTEuMDkzLDEwLjY2NywxMy43NnYxOC4yNAoJCQljMCw1Ljg4OCw0Ljc3OSwxMC42NjcsMTAuNjY3LDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3di0xOC4yNGM2LjQtMi42NjcsMTAuNjY3LTcuNTMxLDEwLjY2Ny0xMy43NgoJCQljMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDdjNi40LTIuNjY3LDEwLjY2Ny03LjUzMSwxMC42NjctMTMuNzZjMC02LjIyOS00LjI2Ny0xMS4wOTMtMTAuNjY3LTEzLjc2di0zMS4xNDcKCQkJYzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2YzAtNi4yMjktNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMzEuMTQ3YzYuNC0yLjY2NywxMC42NjctNy41MzEsMTAuNjY3LTEzLjc2CgkJCXMtNC4yNjctMTEuMDkzLTEwLjY2Ny0xMy43NnYtMjMuNTczaDEwLjY2N2M1Ljg4OCwwLDEwLjY2Ny00Ljc3OSwxMC42NjctMTAuNjY3UzUwNy4yMjMsMTcwLjU4Nyw1MDEuMzM1LDE3MC41ODd6CgkJCSBNMTI4LjAwMSw0MjYuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1Y0MjYuNTg3eiBNMTI4LjAwMSwzNjIuNTg3SDQyLjY2OHYtNDIuNjY3aDg1LjMzM1YzNjIuNTg3eiBNMTI4LjAwMSwyOTguNTg3SDQyLjY2OAoJCQl2LTQyLjY2N2g4NS4zMzNWMjk4LjU4N3ogTTEyOC4wMDEsMjM0LjU4N0g0Mi42Njh2LTg1LjMzM2g4NS4zMzNWMjM0LjU4N3ogTTM0LjE3NywxMjcuOTIxbDUxLjE1Ny01OC40NzVsNTEuMTU3LDU4LjQ3NUgzNC4xNzd6CgkJCSBNMjI0LjAwMSwzNDEuMjU0Yy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3czEwLjY2Nyw0Ljc3OSwxMC42NjcsMTAuNjY3CgkJCVMyMjkuODg5LDM0MS4yNTQsMjI0LjAwMSwzNDEuMjU0eiBNMzMwLjY2OCwzMTkuOTIxYy01Ljg4OCwwLTEwLjY2Ny00Ljc3OS0xMC42NjctMTAuNjY3czQuNzc5LTEwLjY2NywxMC42NjctMTAuNjY3CgkJCXMxMC42NjcsNC43NzksMTAuNjY3LDEwLjY2N1MzMzYuNTU2LDMxOS45MjEsMzMwLjY2OCwzMTkuOTIxeiIvPgoJPC9nPgo8L2c+Cjwvc3ZnPg==', 61);
        add_submenu_page('quickplayground','Playground Builder', 'Playground Builder', 'manage_options', 'quickplayground_builder', 'quickplayground_builder');
        add_submenu_page('quickplayground','Playground Sync', 'Playground Sync', 'manage_options', 'quickplayground_sync', 'quickplayground_sync');
        $cap = is_multisite() ? 'manage_network' : 'manage_options';
        add_submenu_page('quickplayground','Playground Pro', 'Playground Pro', $cap, 'quickplayground_pro', 'quickplayground_pro');
        if(is_multisite())
            add_submenu_page('quickplayground','Network Administrator Controls', 'Network Administrator Controls', 'manage_network', 'quickplayground_networkadmin', 'quickplayground_networkadmin');
    }
}

function quickplayground_postmeta($ids) {
    global $wpdb;
    $sql = "SELECT * FROM $wpdb->postmeta where post_id IN (".implode(',',$ids).") ";
    return $wpdb->get_results($sql);
}


function quickplayground_zipToUploads(string $source_dir, string $uploads_dir, $slug = ''): bool
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



function quickplayground_playground_zip_target($source_directory) {

    global $playground_uploads;

    if (quickplayground_zipToUploads($source_directory, $playground_uploads)) {

        return 'Theme zipped successfully! The zip file can be found at: ' . $upload_directory;

    } else {
        error_log('zip creation failed for '.$source_directory);
        return false;

    }

}



function quickplayground_playground_zip_self() {

// Example usage:

    global $playground_uploads;

    if (quickplayground_zipToUploads($source_directory, $playground_uploads)) {

        return 'Theme zipped successfully! The zip file can be found at: ' . $playground_uploads;

    } else {

        return 'Theme zip creation failed.';

    }

}



function quickplayground_playground_zip_current_theme() {

    global $playground_uploads;

    // Example usage:

    $source_directory = get_theme_root() . '/' . get_stylesheet(); //  Get theme path

    if (quickplayground_zipToUploads($source_directory, $playground_uploads)) {

        return 'Theme zipped successfully! The zip file can be found at: ' . $upload_directory;

    } else {

        return 'Theme zip creation failed.';

    }

}



function quickplayground_playground_zip_theme($stylesheet) {

    global $playground_uploads;

    // Example usage:

    $source_directory = get_theme_root() . '/' . $stylesheet; //  Get theme path

    error_log($source_directory." to ".$playground_uploads );

    if (quickplayground_zipToUploads($source_directory, $playground_uploads)) {

        return 'Theme '.$stylesheet.' zipped successfully! The zip file can be found at: ' . $playground_uploads;

    } else {

        return 'Theme '.$stylesheet.' zip creation failed.';

    }

}



function quickplayground_playground_zip_plugin($slug) {

    global $playground_uploads;

    $source_directory = trailingslashit(dirname(__DIR__)) .$slug; //  Get plugin path

    error_log($source_directory." to ".$playground_uploads );

    if (quickplayground_zipToUploads($source_directory, $playground_uploads)) {
        return 'Plugin '.$slug.' zipped successfully! The zip file can be found at: ' . $playground_uploads;
    } else {
        error_log('Plugin '.$slug.' zip creation failed for '.$source_directory.' '.$playground_uploads);
        return false;
    }
}

//add_filter('quickplayground_blueprint','quickplayground_blueprint_debug_filter',10,1);
function quickplayground_blueprint_debug_filter($blueprint) {
    foreach($blueprint['steps'] as $index => $s) {
        if($s['step'] == 'defineWpConfigConsts')
            $blueprint['steps'][$index] = null;
    }
    $blueprint['steps'] = array_values(array_filter($blueprint['steps']));
    return $blueprint;
}