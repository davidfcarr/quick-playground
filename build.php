<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Builds a playground blueprint and settings array based on provided variables and profile.
 *
 * @param array  $postvars Array of POST variables for the playground build.
 * @param string $profile  The profile name (default: 'default').
 * @return array           Array containing the blueprint and settings.
 */
function qckply_build($postvars, $profile = 'default') {
    $site_origin = rtrim(get_option('siteurl'),'/');
    $default_plugins = is_multisite() ? get_blog_option(1,'qckply_default_plugins',array()) : array();
    $excluded_plugins = is_multisite() ? get_blog_option(1,'qckply_excluded_plugins',array()) : array();
    $default_themes = is_multisite() ? get_blog_option(1,'qckply_default_themes',array()) : array();
    $slugs = ['quick-playground'];
    $themeslugs = [];
    if(!empty($excluded_plugins))
        $slugs = array_merge($slugs,$excluded_plugins);
    if(!empty($default_plugins)) {
        foreach($default_plugins as $slug) {
        $postvars['add_plugin'][] = sanitize_text_field($slug);
        $postvars['activate_plugin'][] = 1;
        $postvars['ziplocal_plugin'][] = false;
        }
    }
    if(isset($postvars['all_active_plugins'])) {
        $active_plugins = explode(',',sanitize_text_field($postvars['all_active_plugins']));
        foreach($active_plugins as $slug) {
            printf('<p>active plugins %s</p>',esc_html($slug));
            $postvars['add_plugin'][] = $slug;
            $postvars['activate_plugin'][] = 1;
            $postvars['ziplocal_plugin'][] = false;
        }
    }

    $settings = get_option('quickplay_clone_settings_'.$profile,array());
    unset($settings['demo_pages']);
    unset($settings['demo_posts']);
    unset($settings['demo_rsvpmakers']);
    unset($settings['post_types']);
    if(!empty($postvars['settings'])) 
    {
        foreach($postvars['settings'] as $key => $value)
            $settings[$key] = is_string($value) ? sanitize_text_field(stripslashes($value)) : qckply_sanitize_clone($value);
        if(empty($settings['copy_pages'])) {
            $settings['copy_pages'] = 0;
        }
        if(empty($settings['copy_events'])) {
            $settings['copy_events'] = 0;
        }
        $settings['is_qckply_clone']=true;
        $settings['show_on_front'] = empty($settings['page_on_front']) ? 'posts' : 'page';
        $settings['qckply_profile']=$profile;
        $settings['qckply_sync_origin']=$site_origin;
        $settings['qckply_site_dir'] = is_multisite() ? '/sites/'.get_current_blog_id() : '';
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
        $settings = apply_filters('qckply_new_settings',$settings, $postvars);
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
            $url = sanitize_text_field($url);
            if(strpos($url,'wordpress.org/plugins/'))
                $steps[] = makePluginItem(basename($url),true,true);
            elseif(strpos($url,'wordpress.org/themes/'))
                $steps[] = makeThemeItem(basename($url),true,false);
        }
    }

    if(isset($postvars['add_theme'])) {
        foreach($postvars['add_theme'] as $i => $slug) {
            $slug = sanitize_text_field(trim($slug));
            if(empty($slug) || in_array($slug, $themeslugs)) {
                continue; // skip duplicate themes
            }
            $themeslugs[] = $slug; // add to slugs to avoid duplicates

                $public = true;

                if(isset($postvars['ziplocal_theme'][$i]))
                    $ziplocal = boolval($postvars['ziplocal_theme'][$i]);
                elseif(isset($postvars['zip'][$slug]))
                    $ziplocal = boolval($postvars['zip'][$slug]);
                else
                    $ziplocal = false;

                if($ziplocal) {
                    $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                    printf('<p>Adding local theme %s</p>',esc_html($slug));
                    qckply_zip_theme($slug);
                } else {
                    if(qckply_repo_check($slug,'theme')) {
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Adding public theme %s</p>',esc_html($slug));
                    } else {
                        $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Public theme %s not found, adding as local zip</p>',esc_html($slug));
                        qckply_zip_theme($slug);
                    }
                }
            if($i < 1) {
                if(isset($_POST['show_details']) || isset($_GET['reset']))
                printf('<p>Default theme %s</p>',esc_html($slug));
                $settings['qckply_clone_stylesheet'] = $slug;
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
        $slug = sanitize_text_field($slug);
        if(in_array($slug, $themeslugs)) {
            continue; // skip duplicate themes
        }
        $activate = empty($themeslugs); //if no other has been assigned
        $themeslugs[] = $slug; // add to slugs to avoid duplicates

        $public = true;

        if(qckply_repo_check($slug,'theme')) {
            if(isset($_POST['show_details']) || isset($_GET['reset']))
                printf('<p>Adding public theme %s</p>',esc_html($slug));
        } else {
            $public = false;
            if(isset($_POST['show_details']) || isset($_GET['reset'])) 
                printf('<p>Public theme %s not found, adding as local zip</p>',esc_html($slug));
            qckply_zip_theme($slug);
        }
        $steps[] = makeThemeItem($slug, $public, $activate);
    }

    if(isset($postvars['add_plugin'])) {

        foreach($postvars['add_plugin'] as $i => $slug) {
            $slug = sanitize_text_field(trim($slug));

            if(!empty($slug) && !in_array($slug, $slugs)) { // check if slug is not empty and not already added
                $slugs[] = $slug; // add to slugs to avoid duplicates

                $public = true;

                if(isset($postvars['ziplocal_plugin'][$i]))
                    $ziplocal = boolval($postvars['ziplocal_plugin'][$i]);
                elseif(isset($postvars['zip'][$slug]))
                    $ziplocal = boolval($postvars['zip'][$slug]);
                else
                    $ziplocal = false;

                if($ziplocal) {
                    $public = false;
                    $did_zip = qckply_zip_plugin($slug);
                    if(!$did_zip) {
                    if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Unable to add as local plugin %s</p>',esc_html($slug));
                    continue;
                    }
                } else {

                    if(qckply_repo_check($slug,'plugin')) {

                    if(isset($_POST['show_details']) || isset($_GET['reset']))
                        printf('<p>Adding public plugin %s</p>',esc_html($slug));
                    } else {
                        $public = false;
                        $did_zip = qckply_zip_plugin($slug);
                        if(!$did_zip) {
                        if(isset($_POST['show_details']) || isset($_GET['reset']))
                            printf('<p>Unable to add as local plugin %s</p>',esc_html($slug));
                        continue;
                        }
                        if(isset($_POST['show_details']) || isset($_GET['reset']))
                            printf('<p>Public plugin %s not found, adding as local zip</p>',esc_html($slug));
                    }

                }
                $steps[] = makePluginItem($slug, $public, true); // activate all imported plugins
            }

        }
    }

    if(!empty($postvars['add_code'])) {

        foreach($postvars['add_code'] as $i => $code) {
            $code = sanitize_textarea_field(trim(stripslashes($code)));
            if(!empty($code)) {
                $steps[] = makeCodeItem(stripslashes($code));
            }
        }
    }
    if(isset($postvars['json_steps'])) {
        if(!empty($postvars['json_steps'])) {
        $postvars['json_steps'] = $json = sanitize_textarea_field(stripslashes($postvars['json_steps']));
        if(!strpos($json,']'))
            $json = '['.$json.']';
        $addsteps = json_decode($json);
        foreach($addsteps as $add) {
            $steps[] = $add;
        }
        }
        update_option('json_steps_'.$profile, $postvars['json_steps']);
    }
    $settings['origin_stylesheet'] = get_stylesheet();
    $settings['origin_template'] = get_template();
    $settings['qckply_origin_directories'] = qckply_get_directories();
    $settings_to_copy = apply_filters('qckply_settings_to_copy',array('timezone_string'));
    foreach($settings_to_copy as $setting) {
        $data = get_option($setting);
        $data = apply_filters('qckply_settings_content',$data,$setting);
        $settings[$setting] = $data;
    }
    $steps[] = makeBlueprintItem('setSiteOptions',null, $settings);    
    qckply_zip_plugin("quick-playground");
    if(function_exists('ProPlaygroundData')) {
        $plugindata = ProPlaygroundData();
        $steps[] = makeBlueprintItem('installPlugin', array('pluginData'=>$plugindata), array('activate'=>true));
    }
    $steps[] = makePluginItem("quick-playground", false, true);
    $steps[] = makeCodeItem('qckply_clone("posts");');

    $blueprint = array('features'=>array('networking'=>true),'steps'=>$steps);
    if(!empty($postvars['landingPage'])) {
        $landingpage = sanitize_text_field($postvars['landingPage']);
        update_option('qckply_landing_page_'.$profile,$landingpage);
        if(strpos($landingpage,'//'))
        {
            $parsed = parse_url($landingpage);
            if(!empty($parsed['path']))
                $landingpage = $parsed['path'];
        }
        $blueprint['landingPage'] = add_query_arg('qckply_clone',1,$landingpage);
    }
    else
        $blueprint['landingPage'] = add_query_arg('qckply_clone',1,'/');

    $blueprint = apply_filters('qckply_new_blueprint',$blueprint);

    update_option('playground_blueprint_'.$profile, $blueprint);
    update_option('quickplay_clone_settings_'.$profile, $settings);
    printf('<div class="notice notice-success"><p>Blueprint saved for profile %s with %d steps defined.</p></div>',esc_html($profile),esc_html(sizeof($blueprint['steps'])));
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
function qckply_swap_theme($blueprint, $slug) {
    $slug = trim($slug);
    if(empty($slug)) {
        return $blueprint;
    }
    $public = true;
    if(!qckply_repo_check($slug,'theme')) {
        $public = false;
        qckply_zip_theme($slug);
    }
    $steps = [];
    $themetest = wp_get_theme($slug);
    $parent_theme = $themetest->parent();
    if(!empty($parent_theme)) {
        $parent = $parent_theme->get_stylesheet();
        qckply_zip_theme($parent);
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
    $steps[] = makeBlueprintItem('setSiteOptions',null, ['qckply_clone_stylesheet' => $slug]);
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
function qckply_change_blueprint_setting($blueprint, $settings) {
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