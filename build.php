<?php
/**
 * Builds a playground blueprint and settings array based on provided variables and profile.
 *
 * @param array  $postvars Array of POST variables for the playground build.
 * @param string $profile  The profile name (default: 'default').
 * @return array           Array containing the blueprint and settings.
 */
function quickplayground_build($postvars, $profile = 'default') {
    $site_origin = rtrim(get_option('siteurl'),'/');
    $default_plugins = is_multisite() ? get_blog_option(1,'playground_default_plugins',array()) : array();
    $excluded_plugins = is_multisite() ? get_blog_option(1,'playground_excluded_plugins',array()) : array();
    $default_themes = is_multisite() ? get_blog_option(1,'playground_default_themes',array()) : array();
    $slugs = ['quick-playground'];
    $themeslugs = [];
    if(!empty($excluded_plugins))
        $slugs = array_merge($slugs,$excluded_plugins);
    if(!empty($default_plugins)) {
        foreach($default_plugins as $slug) {
        $postvars['add_plugin'][] = $slug;
        $postvars['activate_plugin'][] = 1;
        $postvars['ziplocal_plugin'][] = false;
        }
    }
    if(isset($postvars['all_active_plugins'])) {
        $active_plugins = explode(',',$postvars['all_active_plugins']);
        foreach($active_plugins as $slug) {
            printf('<p>active plugins %s</p>',esc_html($slug));
            $postvars['add_plugin'][] = $slug;
            $postvars['activate_plugin'][] = 1;
            $postvars['ziplocal_plugin'][] = false;
        }
    }

    $settings = get_option('playground_clone_settings_'.$profile,array());
    unset($settings['demo_pages']);
    unset($settings['demo_posts']);
    unset($settings['demo_rsvpmakers']);
    unset($settings['post_types']);
    if(!empty($postvars['settings'])) 
    {
        foreach($postvars['settings'] as $key => $value)
            $settings[$key] = stripslashes($value);
        if(empty($settings['copy_pages'])) {
            $settings['copy_pages'] = 0;
        }
        if(empty($settings['copy_events'])) {
            $settings['copy_events'] = 0;
        }
        $settings['is_playground_clone']=true;
        $settings['playground_profile']=$profile;
        $settings['playground_sync_origin']=$site_origin;
        $settings['playground_site_dir'] = is_multisite() ? '/sites/'.get_current_blog_id() : '';
        $settings['demo_pages'] = [];
        $settings['key_pages'] = empty($settings['key_pages']) ? 0 : 1; // 0 if not checked
        if(isset($postvars['post_types']))
            $settings['post_types'] = $postvars['post_types'];
        if(isset($postvars['demo_pages']) && is_array($postvars['demo_pages'])) {
            foreach($postvars['demo_pages'] as $i => $page_id) {
                if(!empty($page_id)) {
                    $settings['demo_pages'][] = intval($page_id);
                }
            }
        }
        if(isset($postvars['demo_posts']) && is_array($postvars['demo_posts'])) {
            $settings['demo_posts'] = [];
            foreach($postvars['demo_posts'] as $i => $id) {
                if(!empty($id)) {
                    $settings['demo_posts'][] = intval($id);
                }
            }
        }
        $settings = apply_filters('quickplayground_new_settings',$settings, $postvars);
    }

    if(isset($postvars['logerrors']))
        $steps[] = array('step'=>'defineWpConfigConsts',"consts"=>array(
            'WP_DEBUG' => true,
            'WP_DEBUG_LOG' => true,
            'WP_DEBUG_DISPLAY' => false,
            'SCRIPT_DEBUG' => true,
        )
    );
    $steps[] = makeBlueprintItem('login');

    if(!empty($postvars['repo'])) {
        $urls = explode("\n",$postvars['repo']);
        foreach($urls as $url) {
            if(strpos($url,'wordpress.org/plugins/'))
                $steps[] = makePluginItem(basename($url),true,true);
            elseif(strpos($url,'wordpress.org/themes/'))
                $steps[] = makeThemeItem(basename($url),true,false);
        }
    }

    if(isset($postvars['add_theme'])) {
        foreach($postvars['add_theme'] as $i => $slug) {
            $slug = trim($slug);
            if(empty($slug) || in_array($slug, $themeslugs)) {
                continue; // skip duplicate themes
            }
            $themeslugs[] = $slug; // add to slugs to avoid duplicates

                $public = true;

                $ziplocal = isset($postvars['ziplocal_theme'][$i]) ? intval($postvars['ziplocal_theme'][$i]) : 0;

                if($ziplocal) {
                    $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                    printf('<p>Adding local theme %s</p>',esc_html($slug));
                    quickplayground_playground_zip_theme($slug);
                } else {
                    if(quickplayground_repo_check($slug,'theme')) {
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Adding public theme %s</p>',esc_html($slug));
                    } else {
                        $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Public theme %s not found, adding as local zip</p>',esc_html($slug));
                        quickplayground_playground_zip_theme($slug);
                    }
                }
            if($i < 1) {
                if(isset($_POST['show_details']) || isset($_GET['reset']))
                printf('<p>Default theme %s</p>',esc_html($slug));
                $settings['clone_stylesheet'] = $slug;
                $themetest = wp_get_theme($slug);
                $parent_theme = $themetest->parent();
                if(!empty($parent_theme)) {
                    $parent = $parent_theme->get_stylesheet();
                    $steps[] = makeThemeItem($parent, false, false);
                }
            }
            $steps[] = makeThemeItem($slug, $public, $i < 1);
        }
    }

    foreach($default_themes as $slug) {
        if(in_array($slug, $themeslugs)) {
            continue; // skip duplicate themes
        }
        $activate = empty($themeslugs); //if no other has been assigned
        $themeslugs[] = $slug; // add to slugs to avoid duplicates

        $public = true;

        if(quickplayground_repo_check($slug,'theme')) {
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                printf('<p>Adding public theme %s</p>',esc_html($slug));
        } else {
            $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset'])) 
                printf('<p>Public theme %s not found, adding as local zip</p>',esc_html($slug));
            quickplayground_playground_zip_theme($slug);
        }
        $steps[] = makeThemeItem($slug, $public, $activate);
    }

    if(isset($postvars['add_plugin'])) {

        foreach($postvars['add_plugin'] as $i => $slug) {

            if(!empty($slug) && !in_array($slug, $slugs)) { // check if slug is not empty and not already added
                $slugs[] = $slug; // add to slugs to avoid duplicates

                $public = true;

                $ziplocal = isset($postvars['ziplocal_plugin'][$i]) ? intval($postvars['ziplocal_plugin'][$i]) : 0;

                if($ziplocal) {
                    $public = false;
                    $did_zip = quickplayground_playground_zip_plugin($slug);
                    if(!$did_zip) {
                    if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Unable to add as local plugin %s</p>',esc_html($slug));
                    continue;
                    }
                } else {

                    if(quickplayground_repo_check($slug,'plugin')) {

                    if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Adding public plugin %s</p>',esc_html($slug));
                    } else {
                        $public = false;
                        $did_zip = quickplayground_playground_zip_plugin($slug);
                        if(!$did_zip) {
                        if(isset($_POST['show_details']) || isset($_GET['reset']))
                            printf('<p>Unable to add as local plugin %s</p>',esc_html($slug));
                        continue;
                        }
                        if(isset($_POST['show_details']) || isset($_GET['reset']))
                            printf('<p>Public plugin %s not found, adding as local zip</p>',esc_html($slug));
                    }

                }
                $steps[] = makePluginItem($slug, $public, isset($postvars['activate_plugin'][$i]) && $postvars['activate_plugin'][$i] == 1);
            }

        }
    }

    if(!empty($postvars['add_code'])) {

        foreach($postvars['add_code'] as $i => $code) {

            if(!empty($code)) {
                $steps[] = makeCodeItem(stripslashes($code));
            }
        }
    }
    if(isset($postvars['json_steps'])) {
        if(!empty($postvars['json_steps'])) {
        $postvars['json_steps'] = stripslashes($postvars['json_steps']);
        }
        update_option('json_steps_'.$profile, $postvars['json_steps']);
    }
    if(!empty($postvars['json_steps'])) {
        $json = sanitize_textarea_field($postvars['json_steps']);
        if(!strpos($json,']'))
            $json = '['.$json.']';
        $addsteps = json_decode($json);
        foreach($addsteps as $add) {
            $steps[] = $add;
        }
    }
    $settings['origin_stylesheet'] = get_stylesheet();
    $settings_to_copy = apply_filters('playground_settings_to_copy',array('timezone_string'));
    foreach($settings_to_copy as $setting)
        $settings[$setting] = get_option($setting);
    $steps[] = makeBlueprintItem('setSiteOptions',null, $settings);    
    quickplayground_playground_zip_plugin("quick-playground");
    $enabled = is_multisite() ? get_blog_option(1,'playground_premium_enabled') : get_option('playground_premium_enabled');
    if($enabled) {
        $plugindata = ProPlaygroundData();
        $steps[] = makeBlueprintItem('installPlugin', array('pluginData'=>$plugindata), array('activate'=>true));
    }
    $steps[] = makePluginItem("quick-playground", false, true);

    $blueprint = array('features'=>array('networking'=>true),'steps'=>$steps);
    $blueprint = apply_filters('quickplayground_new_blueprint',$blueprint);

    update_option('playground_blueprint_'.$profile, $blueprint);
    update_option('playground_clone_settings_'.$profile, $settings);
    printf('<div class="notice notice-success"><p>Blueprint saved for profile %s with %d steps defined.</p></div>',esc_html($profile),sizeof($blueprint['steps']));
    if(!empty($postvars['show_blueprint'])) {
    echo '<pre>'.esc_html(json_encode($blueprint, JSON_PRETTY_PRINT)).'</pre>';
    }
    return array($blueprint, $settings);
}

/**
 * Swaps the active theme in the blueprint to the specified slug.
 *
 * @param array  $blueprint The current blueprint array.
 * @param string $slug      The theme slug to activate.
 * @return array            Modified blueprint array.
 */
function quickplayground_swap_theme($blueprint, $slug) {
    $slug = trim($slug);
    if(empty($slug)) {
        return $blueprint;
    }
    $public = true;
    if(!quickplayground_repo_check($slug,'theme')) {
        $public = false;
        quickplayground_playground_zip_theme($slug);
    }
    $steps = [];
    $themetest = wp_get_theme($slug);
    $parent_theme = $themetest->parent();
    if(!empty($parent_theme)) {
        $parent = $parent_theme->get_stylesheet();
        quickplayground_playground_zip_theme($parent);
        $steps[] = makeThemeItem($parent, false, false);
    }
    $match = false;
    foreach($blueprint['steps'] as $step) {
        if($step['step'] == 'installTheme') {
            if(strpos($step['themeData']['resource'],'themes')) {
                $themeslug = $step['themeData']['slug'];
            } else {
                //zip
                $themeslug = preg_replace('/\.zip.+/','',basename($step['themeData']['url']));
            }
            if($themeslug == $slug) {
            $step['options']['activate'] = true;
            $match = true;
            }
            else {
            $step['options']['activate'] = false;
            }
        }
        $steps[] = $step;
    }
    if(!$match)
        $steps[] = makeThemeItem($slug, $public, true);
    $steps[] = makeBlueprintItem('setSiteOptions',null, ['clone_stylesheet' => $slug]);
    $blueprint['steps'] = $steps;
    return $blueprint;
}

/**
 * Updates the site options in the blueprint with new settings.
 *
 * @param array $blueprint The current blueprint array.
 * @param array $settings  Associative array of settings to update.
 * @return array           Modified blueprint array.
 */
function quickplayground_change_blueprint_setting($blueprint, $settings) {
    $public = true;
    $steps = [];
    $match = false;
    foreach($blueprint['steps'] as $step) {
        if(!is_array($step)) {
            $step = (array) $step; // skip non-array steps
        }
        if($step['step'] == 'setSiteOptions') {
            foreach($settings as $key => $value)
                $step['options'][$key] = $value;
        }
        $steps[] = $step;
    }
    $blueprint['steps'] = $steps;
    return $blueprint;
}