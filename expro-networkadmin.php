<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Displays and processes the network admin settings page for Quick Playground.
 *
 * Allows the network administrator to specify plugins and themes that should be excluded or required by default
 * for playground sites. Handles saving and displaying options for excluded and default plugins/themes.
 */
function qckply_networkadmin() {
    if((!empty($_POST) || isset($_GET['update']) || isset($_GET['profile']) || isset($_GET['reset'])) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    echo '<p>As network administrator, you can specify plugins and themes that should be excluded or required by default.</p>';

    printf('<form  class="qckply-form"  method="post" action="">%s',esc_attr(admin_url('admin.php?page=qckply_networkadmin')));
    wp_nonce_field('quickplayground','playground',true,true);
    if(isset($_POST['excluded_plugins'])) {
        $excluded_plugins = array_filter($_POST['excluded_plugins']);
        $excluded_themes = array_filter($_POST['excluded_themes']);
        $default_plugins = array_filter($_POST['default_plugins']);
        $default_themes = array_filter($_POST['default_themes']);
        update_blog_option(1,'qckply_excluded_plugins',$excluded_plugins);
        update_blog_option(1,'qckply_excluded_themes',$excluded_themes);
        update_blog_option(1,'qckply_default_plugins',$default_plugins);
        update_blog_option(1,'qckply_default_themes',$default_themes);
    }
    else {
        $excluded_plugins = get_blog_option(1,'qckply_excluded_plugins',array());
        $excluded_themes = get_blog_option(1,'qckply_excluded_themes',array());
        $default_plugins = get_blog_option(1,'qckply_default_plugins',array());
        $default_themes = get_blog_option(1,'qckply_default_themes',array());
    }
    if(!function_exists('get_plugins'))
        require_once(ABSPATH.'/wp-admin/includes/plugins.php');
    $active_plugins = [];
    $all_plugins = [];
    $pluginoptions = '<option value="">Select a plugin</option>';
    $active_pluginoptions = '<option value="">Select a plugin</option>';
    $plugins = get_plugins();//['allowed'=>true]

    foreach($plugins as $dir_file => $header) {
        $parts = preg_split('/[\.\/]/',$dir_file);
        $basename = $parts[0];
        if(in_array($basename,$excluded_plugins) || 'quick-playground' == $basename) {
            continue; // skip excluded plugins
        }
        $all_plugins[$basename] = $header["Name"];
        if(is_plugin_active($dir_file) )
        {
            if(!in_array($basename,$excluded_plugins))
                $active_plugins[] = $basename;
            $active_pluginoptions .= sprintf('<option value="%s">%s (%s)</option>',esc_attr($basename), esc_html($header["Name"]),esc_html__('Active','quick-playground'));
        }
        else
            $pluginoptions .= sprintf('<option value="%s">%s</option>',esc_attr($basename), esc_html($header["Name"]));
    }
    $themeoptions = '<option value="">Select a theme</option>';

    $themes = wp_get_themes(['allowed'=>true]);

    $all_themes = [];

    foreach($themes as $styleslug => $themeobj) {
        if(in_array($styleslug,$excluded_themes))
        if($stylesheet == $styleslug) {
            $current_theme_option = sprintf('<option value="%s">%s</option>',esc_attr($styleslug), esc_html($themeobj->__get('name')));
        }
        $themeoptions .= sprintf('<option value="%s">%s</option>',esc_attr($styleslug), esc_html($themeobj->__get('name')));
    }
    echo '<h2>Excluded Plugins</h2>';
    foreach($excluded_plugins as $p)
        printf('<p><input type="checkbox" name="excluded_plugins[]" value="%s" checked="checked"> %s</p>',esc_attr($p), esc_html($p));
    for($i = 0; $i < 10; $i++) {
    printf('<p>Exclude Plugin: <select name="excluded_plugins[]">%s</select></p>',wp_kses($active_pluginoptions.$pluginoptions, qckply_kses_allowed()));
    }
    echo '<h2>Excluded Themes</h2>';
    foreach($excluded_themes as $p)
        printf('<p><input type="checkbox" name="excluded_themes[]" value="%s" checked="checked"> %s</p>',esc_attr($p), esc_html($p));
    for($i = 0; $i < 10; $i++) {
    printf('<p>Exclude Theme: <select name="excluded_themes[]">%s</select></p>',wp_kses($themeoptions, qckply_kses_allowed()));
    }
    echo '<h2>Default Plugins</h2>';
    foreach($default_plugins as $p)
        printf('<p><input type="checkbox" name="default_plugins[]" value="%s" checked="checked"> %s</p>',esc_attr($p),esc_html($p));
    for($i = 0; $i < 10; $i++) {
    printf('<p>Default Plugin: <select name="default_plugins[]">%s</select></p>',wp_kses($active_pluginoptions.$pluginoptions, qckply_kses_allowed()));
    }
    echo '<h2>Default Themes</h2>';
    foreach($default_themes as $p)
        printf('<p><input type="checkbox" name="default_themes[]" value="%s" checked="checked"> %s</p>',esc_attr($p),esc_html($p));

    for($i = 0; $i < 10; $i++) {
    printf('<p>Default Theme: <select name="default_themes[]">%s</select></p>',wp_kses($themeoptions, qckply_kses_allowed()));
    }
    echo '<button>Save Options</button></form>';

}