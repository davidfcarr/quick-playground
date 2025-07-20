<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Displays the Quick Playground Blueprint Builder admin page and handles form output.
 */
function qckply_builder() {
global $wpdb, $current_user, $qckply_uploads, $qckply_uploads_url;
$profile = isset($_REQUEST['profile']) ? preg_replace('/[^a-z0-9]+/','_',strtolower(sanitize_text_field($_REQUEST['profile']))) : 'default';
$stylesheet = get_stylesheet();
printf('<h1>%s: %s</h1>', esc_html(get_bloginfo('name')), esc_html($profile));
blueprint_settings_init($profile);
$qckply_api_url = rest_url('quickplayground/v1/blueprint/'.$profile).'?x='.time().'&user_id='.$current_user->ID;
$qckply_clone_api_url = rest_url('quickplayground/v1/clone_posts/'.$profile);
$origin_url = rtrim(get_option('siteurl'),'/');
$blueprint = get_option('playground_blueprint_'.$profile, array());
$settings = get_option('quickplay_clone_settings_'.$profile,array());
$stylesheet = $settings['qckply_clone_stylesheet'] ?? $stylesheet;

?>
<h2>Blueprint Builder</h2>
<p>This is where you define which themes and plugins you want each WordPress Playground to include. You can create several Playground profiles, associated with different themes, plugins, and options.</p>
<?php

printf('<form class="qckply-form" method="post" action="%s"> %s <input type="hidden" name="build_profile" value="1">',esc_attr(admin_url('admin.php?page=qckply_builder')), wp_nonce_field('quickplayground','playground',true,false));
echo '<p><button>Refresh</button></p>';
echo '<h2>Customization Options</h2>';
printf('<p>Loading saved blueprint for profile %s with %d steps defined. You can add or modify themes, plugins, and settings below.</p>',esc_html($profile),sizeof($blueprint['steps']));

$saved_plugins = $saved_themes = [];
$saved_code = '';

$themeslots = playground_premium_enabled() ? 10 + sizeof($saved_themes) : 1;
qckply_theme_options($blueprint, $stylesheet, $themeslots);
qckply_plugin_options($blueprint, !playground_premium_enabled());

if(!playground_premium_enabled()) {
    printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogname',esc_attr($settings['blogname']));
    printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Description','blogdescription',esc_attr($settings['blogdescription']));

    printf('<p><input type="checkbox" name="settings[key_pages]" value="1" %s > Include key pages and posts (linked to from the home page or menu)</p>',(!isset($settings['key_pages']) || $settings['key_pages']) ? ' checked="checked" ' : '');
    printf('<input type="hidden" name="profile" value="%s" />',esc_attr($profile));
    echo '<p><button>Submit</button></p>';
    echo '</form>';
    $upgrade_url = admin_url('admin.php?page=qckply_pro');

    echo '<div class="qckply-doc">';

    echo "<h2>Upgrade Option</h2><p>Quick Playground allows you to test themes, plugins, design ideas, and configuration settings on a virtual WordPress Playground copy of your website, without worrying about breaking your live site.</p>";
    echo "<p>The Pro version allows you greater freedom to customize the themes and plugins installed, including custom themes and plugins that are not in the WordPress.org repository, and provides more tools for developers to create demo environments.</p>";
    printf('<p><a href="%s">Upgrade</a> for access to these features.</p>
    <ul class="qckplypro">
    <li>Install and active Themes and Plugins that are different from those on your live website</li>
    <li>Demonstrate Themes and Plugins that are not in the WordPress repository (served as zip files from your server instead)</li>
    <li>Specify a different front page from the one used your live website</li>
    <li>Publish additional demo pages to the playground, based on draft or published pages of your website</li>
    <li>Extend the plugin with custom filters and actions</li>
    <li>Call custom PHP code in the playground.</li>
    </ul>
    <p><strong>For a limited time, you can get the Pro version just by signing up for the Quick Playground Email List. <a href="%s">Upgrade Now</a>.</strong></p>
    ',esc_attr($upgrade_url),esc_html($upgrade_url));
    echo '</div>';

    return;
}

echo '<p>Additional themes or plugins from the WordPress repository<br /><textarea name="repo" cols="100" rows="3"></textarea><br>Example:<br /><em>https://wordpress.org/themes/oceanwp/</em><br /><em>https://wordpress.org/plugins/rsvpmaker/</em></p>';

if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
        if(!is_array($step))
            $step = (array) $step;
            if('runPHP' == $step['step']) {
                if(!strpos($step['code'],'qckply_clone')) {
                $saved_code = $step['code'];
                }
            }
        }
}

printf('<p>Custom PHP code *<br /><textarea name="add_code[]" cols="100" rows="3">%s</textarea></p>',esc_html($saved_code));
echo '<p><code>&lt;?php require_once \'wordpress/wp-load.php\';</code> will be added automatically to enable WordPress and plugin functions.</p>';

printf('<p>Custom Step (JSON)<br /><textarea name="json_steps" cols="100" rows="3">%s</textarea></p><p>Example from the <a href="https://wordpress.github.io/wordpress-playground/blueprints/examples/">documentation</a>:</p><pre>%s</pre>',esc_html(get_option('json_steps_'.$profile)),'
{
    "step": "installPlugin",
    "pluginData": {
        "resource": "wordpress.org/plugins",
        "slug": "coblocks"
    }
},
{
    "step": "installTheme",
    "themeData": {
        "resource": "wordpress.org/themes",
        "slug": "pendant"
    }
}');


if(!empty($settings['page_on_front'])) {
    $page_on_front = $settings['page_on_front'];
}

$front = $page_options = $post_options = '';
$pages = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='page' AND (post_status='publish' OR post_status='draft') ORDER BY post_status, post_title ");
foreach($pages as $page) {
    $status = ('draft' == $page->post_status) ? '(Draft)' : '';
    $opt = sprintf('<option value="%d" >%s %s</option>',intval($page->ID),esc_html($page->post_title),esc_html($status));
    if($settings['page_on_front'] == $page->ID)
        $front = $opt;
    $page_options .= $opt;
}
$demoposts = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='post' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC ");
foreach($demoposts as $p) {
    $status = ('draft' == $p->post_status) ? '(Draft)' : '';
    $opt = sprintf('<option value="%d" >%s %s</option>',intval($p->ID),esc_html($p->post_title),esc_html($status));
    $post_options .= $opt;
}

echo '<h2>'.esc_html__('Content to Include','quick-playground').'</h2>';
$front .= '<option value="">Blog Listing</option>';

printf('<p>%s: <select name="settings[page_on_front]">%s</select></p>',esc_html__('Front Page','quick-playground'),wp_kses($front.$page_options, qckply_kses_allowed()));

printf('<p><input type="checkbox" name="settings[key_pages]" value="1" %s > Include key pages and posts (linked to from the home page or menu)</p>',(!isset($settings['key_pages']) || $settings['key_pages']) ? ' checked="checked" ' : '');
//qckply_key_pages_checkboxes();

printf('<p>Copy <input type="checkbox" name="settings[copy_pages]" value="1" %s > all published pages <input style="width:5em" type="number" name="settings[copy_blogs]" value="%d" size="3" > latest blog posts</p>',!empty($settings['copy_pages']) ? ' checked="checked" ' : '',(!isset($settings['copy_blogs']) || $settings['copy_blogs']) ? intval($settings['copy_blogs']) : 10);

if(!empty($settings['demo_pages']) && is_array($settings['demo_pages'])) {
    foreach($settings['demo_pages'] as $page_id) {
        if(!empty($page_id)) {
            printf('<p>%s <input type="checkbox" name="demo_pages[]" value="%d" checked="checked" /> %s</p>', esc_html__('Keep Demo Page', 'quick-playground'), intval($page_id), esc_html(get_the_title($page_id)));
        }
    }
}

printf('<p>%s <input type="text" name="landingPage" value="%s" style="width: 200px" /><br /><em>%s</em></p>',esc_html__('Landing Page (optional)','quick-playground'),empty($blueprint['landingPage']) ? '' : esc_attr(str_replace('qckply_clone=1','',$blueprint['landingPage'])),esc_html__('If you want the user to start somewhere other than the home page, enter the path. Example "/wp-admin/" or "/demo-instructions/"','quick-playground'));

for($i = 0; $i < 10; $i++) {
$classAndID = ($i > 0) ? ' class="hidden_item page" id="page_'.$i.'" ' : ' class="page" id="page_'.$i.'" ';
printf('<p%s>Demo Page: <select class="select_with_hidden" name="demo_pages[]">%s</select></p>'."\n",wp_kses($classAndID, qckply_kses_allowed()),'<option value="">Choose Page</option>'.wp_kses($page_options, qckply_kses_allowed()));
}
printf('<p><input type="checkbox" name="settings[make_menu]" value="1" %s > Make menu from selected pages</p>',empty($settings['make_menu']) ? '' : 'checked="checked"');

if(!empty($settings['demo_posts']) && is_array($settings['demo_posts'])) {
    foreach($settings['demo_posts'] as $id) {
        if(!empty($id)) {
            printf('<p>Keep Demo Post <input type="checkbox" name="demo_posts[]" value="%d" checked="checked" /> %s</p>'."\n",intval($id),esc_html(get_the_title($id)));
        }
    }
}
for($i = 0; $i < 10; $i++) {
    $classAndID = ($i > 0) ? ' class="hidden_item post" id="post_' . esc_attr($i) . '" ' : ' class="post" id="post_' . esc_attr($i) . '" ';
    printf(
        '<p%s>%s: <select class="select_with_hidden" name="demo_posts[]">%s</select></p>' . "\n",
        wp_kses($classAndID, qckply_kses_allowed()),
        esc_html__('Demo Blog Post', 'quick-playground'),
        '<option value="">' . esc_html__('Choose Blog Post', 'quick-playground') . '</option>' . wp_kses($post_options, qckply_kses_allowed())
    );
}

do_action('qckply_form_demo_content',$settings);

$types = get_post_types(array(
   'public'   => true,
   '_builtin' => false
),'objects','and');

if(!empty($types)) {
echo '<h2>'.esc_html__('Additional Content Types','quick-playground').'</h2>';
echo '<p>'.esc_html__('Content types added by plugins','quick-playground').'</p>';
    foreach($types as $type) {
        if(strpos($type->name,'rsvp') !== false)
            continue;
        $checked = (!empty($settings['post_types']) && is_array($settings['post_types']) && in_array($type->name,$settings['post_types'])) ? ' checked="checked" ' : '';
        printf('<p><input type="checkbox" name="post_types[]" value="%s" %s > %s (%s)</p>',esc_attr($type->name),$checked,esc_html($type->label),esc_html($type->name));
    }
}

printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogname',esc_attr($settings['blogname']));
printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Description','blogdescription',esc_attr($settings['blogdescription']));

echo '<h2>'.esc_html__('Content Saved from Past Sessions','quick-playground').'</h2>';
$caches = qckply_caches($profile);
if(empty($caches)) {
echo '<p>'.esc_html__('none','quick-playground').'</p>';
}
else {
    foreach($caches as $cache) {
        printf('<p><input type="checkbox" name="reset_cache[]" value="%s" /> %s %s</p>',esc_attr($cache),esc_html__('Reset','quick-playground'),esc_html(ucfirst($cache)));
    }
    echo '<p>'.esc_html__('Resetting will force the playground to fetch fresh content.','quick-playground').'</p>';
}

//printf('<p id="cachesettings"><input name="settings[%s]" type="radio" value="1" %s /> %s ','qckply_no_cache',empty($settings['qckply_no_cache']) ? '' : ' checked="checked" ', esc_html__('Use live website content','quick-playground'));
//printf('<input name="settings[%s]" type="radio" value="0" %s /> %s</p>','qckply_no_cache',!empty($settings['qckply_no_cache']) ? '' : ' checked="checked" ', esc_html__('Use cached playground content','quick-playground'));

echo '<p><input type="checkbox" name="show_details" value="1" /> Show Detailed Output</p>';
echo '<p><input type="checkbox" name="show_blueprint" value="1" /> Show Blueprint JSON</p>';
echo '<p><input type="checkbox" name="logerrors" value="1" /> Log Errors in Playground</p>';
printf('<input type="hidden" name="profile" value="%s" />', esc_attr($profile));
do_action('qckply_additional_setup_form_fields');
echo '<p><button>Submit</button></p>';
echo '</form>';
$key = playground_premium_enabled();
$qckply_api_url = get_qckply_api_url(['profile'=>$profile,'key'=>$key]);

$taxurl = rest_url('quickplayground/v1/clone_taxonomy/'.$profile.'?t='.time());
$imgurl = rest_url('quickplayground/v1/clone_images/'.$profile.'?t='.time());

printf('<h3>For Testing</h3><p>Blueprint API URL: <a href="%s" target="_blank">%s</a></p>',esc_url($qckply_api_url),esc_html($qckply_api_url));
printf('<p>Blueprint, No Cache: <a href="%s&nocache=1" target="_blank">%s&nocache=1</a></p>',esc_url($qckply_api_url),esc_html($qckply_api_url));
printf('<p>Clone Posts API URL: <br /><a href="%s" target="_blank">%s</a></p>',esc_url($qckply_clone_api_url),esc_html($qckply_clone_api_url));
printf('<p>Clone Metadata/Taxonomy API URL: <br /><a href="%s" target="_blank">%s</a></p>',esc_url($taxurl),esc_html($taxurl));
printf('<p>Clone Images API URL: <br /><a href="%s" target="_blank">%s</a></p>',esc_url($imgurl),esc_html($imgurl));
printf('<p>Demo playground button code</p><p><textarea cols="100" rows="5">%s</textarea></p>',esc_html(qckply_get_button(['profile',$profile,'is_demo'=>1])));
qckply_get_blueprint_link(['profile'=>$profile,'is_demo'=>1]);
qckply_print_button_shortcode(['profile'=>$profile,'is_demo'=>1]);

$pages = qckply_find_key_pages();
print_r($pages);

}

/**
 * Outputs plugin selection options for the playground blueprint form.
 *
 * @param array $blueprint   The current blueprint array.
 * @param bool  $active_only Whether to show only active plugins (default true).
 */
function qckply_plugin_list($blueprint) {
    $saved_plugins = [];
    foreach($blueprint['steps'] as $step) {
        if(is_array($step)) {
            if('installPlugin' == $step['step']) {
                if($step['pluginData']['resource'] == 'wordpress.org/plugins')
                {
                    $slug = $step['pluginData']['slug'];
                }
                else {
                $slug = $step['pluginData']['url'];
                if(strpos($slug,'playground'))
                    continue; 
                preg_match('/\/([a-z0-9\-]+)\.zip/',$slug,$match);
                if(empty($match[1]))
                    continue;
                $slug = $match[1];
                }
            if(strpos($slug,'playground'))
                continue; 
            if(!empty($step['options']['activate']) )
                $slug .= ' (active)';
            $saved_plugins[] = $slug;
            }
        }
}
    return $saved_plugins;
}

/**
 * Outputs plugin selection options for the playground blueprint form.
 *
 * @param array $blueprint   The current blueprint array.
 * @param bool  $active_only Whether to show only active plugins (default true).
 */
function qckply_plugin_options($blueprint, $active_only = true) {
    $plausible_plugins = qckply_plausible_plugins();
    $default_plugins = is_multisite() ? get_blog_option(1,'qckply_default_plugins',array()) : array();
    $pluginoptions = '<option value="">Select a plugin</option>';
    foreach($plausible_plugins['active'] as $index => $slug) {
        $name = $plausible_plugins['active_names'][$index];
        $pluginoptions .= sprintf('<option value="%s">%s (%s)</option>',esc_attr($slug), esc_html($name),esc_html__('Active','quick-playground'));
    }
    if(!$active_only) {
        foreach($plausible_plugins['inactive'] as $slug => $name) {
            $pluginoptions .= sprintf('<option value="%s">%s</option>',esc_attr($slug), esc_html($name));
        }
    }    
    if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
        if(is_array($step)) {
            if('installPlugin' == $step['step']) {
                if(isset($_GET['update']) && $step['pluginData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['pluginData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',esc_html($matches[1]));
                        qckply_zip_plugin($matches[1]);
                    }
                $saved_plugins[] = $step;
            }
        }
    }
}

echo '<h2>Choose Plugins for Your Playground</h2>';
if(!empty($default_plugins)) {
    echo '<p>The network administrator has specified that these will be included automatically: '.esc_html(implode(',',$default_plugins)).'</p>';
}
if(!empty($plausible_plugins['active'])) {
echo '<p><input type="checkbox" name="all_active_plugins" value="'.esc_attr(implode(',',$plausible_plugins['active'])).'"> Include these active plugins ('.esc_html(implode(', ',$plausible_plugins['active_names'])).')</p>';
}
for($i = 0; $i < 10 + sizeof($saved_plugins); $i++) {
if(!empty($saved_plugins[$i])) {
    if($saved_plugins[$i]['pluginData']['resource'] == 'wordpress.org/plugins') {
        $slug = $saved_plugins[$i]['pluginData']['slug'];
        $local = 0;
    }
    else {
        $slug = $saved_plugins[$i]['pluginData']['url'];
        preg_match('/\/([a-z0-9\-]+)\.zip/',$slug,$match);
        if(empty($match[1]))
            continue;
        $slug = $match[1];
        $local = 1;
    }
    if($slug == 'quick-playground')
        continue; // skip this plugin, it is already included in the playground
    if(!empty($slug)) {
    //printf('<p>Keep Plugin: <input type="checkbox" name="add_plugin[]" value="%s" checked="checked" /> %s <input type="checkbox" name="zip[%s]" value="1" %s /> Local Zip <input type="checkbox" name="activate[%s]" value="1" %s /> Activate </p>',esc_attr($slug), esc_attr($slug), esc_attr($slug),$local ? ' checked="checked" ' : '',esc_attr($slug),$active);
    $opt = sprintf('<option value="%s">%s</option><option value="">Remove %s</option>',$slug,$slug,$slug);
    $activate = (!empty($saved_plugins[$i]['options']['activate']) ) ? '<input type="radio" name="activate_plugin['.intval($i).']" value="1" checked="checked" /> Activate <input type="radio" name="activate_plugin['.intval($i).']" value="0" /> Do Not Activate' : '<input type="radio" name="activate_plugin['.intval($i).']" value="1" /> Activate <input type="radio" name="activate_plugin['.intval($i).']" value="0" checked="checked" /> Do Not Activate';
    $zip = ($local) ? '<input type="radio" name="ziplocal_plugin['.intval($i).']" value="0" /> WordPress.org <input type="radio" name="ziplocal_plugin['.intval($i).']" value="1"  checked="checked" /> Local Zip ' : '<input type="radio" name="ziplocal_plugin['.intval($i).']" value="0" checked="checked" /> WordPress.org <input type="radio" name="ziplocal_plugin['.intval($i).']" value="1" /> Local Zip ';
    $classAndID = ' class="plugin" id="plugin_'.esc_attr($i).'" ';
    printf('<p%s>Plugin: <select class="select_with_hidden" name="add_plugin[]">%s</select> %s <br />%s  </p>',wp_kses($classAndID, qckply_kses_allowed()),wp_kses($opt.$pluginoptions, qckply_kses_allowed()),wp_kses($zip, qckply_kses_allowed()),wp_kses($activate, qckply_kses_allowed()));
    }
} 
else {
    $classAndID = ($i > 0 + sizeof($saved_plugins)) ? ' class="hidden_item plugin" id="plugin_'.esc_attr($i).'" ' : ' class="plugin" id="plugin_'.esc_attr($i).'" ';
    printf('<p%s>Plugin: <select class="select_with_hidden" name="add_plugin[]">%s</select>  <input type="radio" name="ziplocal_plugin[%d]" value="0" checked="checked" /> WordPress.org <input type="radio" name="ziplocal_plugin[%d]" value="1" /> Local Zip <br /><input type="radio" name="activate_plugin[%d]" value="1" checked="checked" /> Activate <input type="radio" name="activate_plugin[%d]" value="0" /> Do Not Activate  </p>',wp_kses($classAndID, qckply_kses_allowed()),wp_kses($pluginoptions, qckply_kses_allowed()),$i,$i,$i,$i);
    }
}
echo "<p class=\"fineprint\">Make a selection, and another will be revealed</p>\n";

}

/**
 * Outputs theme selection options for the playground blueprint form.
 *
 * @param array  $blueprint   The current blueprint array.
 * @param string $stylesheet  The current theme stylesheet.
 * @param int    $themeslots  Number of theme slots to display (default 1).
 */
function qckply_theme_options($blueprint, $stylesheet, $themeslots = 1) {
$excluded_themes = (is_multisite()) ? get_blog_option(1,'qckply_excluded_themes',array()) : array();
$saved_plugins = $saved_themes = [];
$saved_code = '';
    $themeoptions = '<option value="">Select a theme</option>';

    $themes = wp_get_themes(['allowed'=>true]);

    $all_themes = [];

    foreach($themes as $styleslug => $themeobj) {

        if(in_array($styleslug,$excluded_themes)) {
            continue; // skip excluded themes
        }

        if($stylesheet == $styleslug) {
            $current_theme_option = sprintf('<option value="%s">%s</option>',esc_attr($styleslug), esc_html($themeobj->__get('name')));
        }
        $themeoptions .= sprintf('<option value="%s">%s</option>',esc_attr($styleslug), esc_html($themeobj->__get('name')));
    }

if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
        if(is_array($step)) {
            if('installTheme' == $step['step']) {
                if(isset($_GET['update']) && $step['themeData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['themeData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',esc_html($matches[1]));
                        qckply_zip_theme($matches[1]);
                    }
                $saved_themes[] = $step;
            }
            elseif('installPlugin' == $step['step']) {
                if(isset($_GET['update']) && $step['pluginData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['pluginData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',esc_html($matches[1]));
                        qckply_zip_plugin($matches[1]);
                    }
                $saved_plugins[] = $step;
            }
            elseif('runPHP' == $step['step']) {
                if(!strpos($step['code'],'qckply_clone')) {
                $saved_code = $step['code'];
                }
            }
        }
    }
}

echo ($themeslots > 1) ? '<h2>Themes for Your Playground</h2>' : '<h2>Theme for Your Playground</h2>';

if($themeslots > 1)
    $themeslots += sizeof($saved_themes);

for($i = 0; $i < $themeslots; $i++) {
$label = ($i == 0) ? 'Active Theme' : 'Additional Theme';
if(!empty($saved_themes[$i]) && $i > 0) {
    if($saved_themes[$i]['themeData']['resource'] == 'wordpress.org/themes') {
        $slug = $saved_themes[$i]['themeData']['slug'];
        $local = 0;
    }
    else {
        $slug = $saved_themes[$i]['themeData']['url'];
        preg_match('/\/([a-z0-9\-]+)\.zip/',$slug,$match);
        $slug = $match[1];
        $local = 1;
    }
    printf('<p>Keep %s: <input type="checkbox" name="add_theme[]" value="%s" checked="checked" /> %s <input type="checkbox" name="zip[%s]" value="1" %s /> Local Zip</p>',esc_html($label),esc_attr($slug), esc_attr($slug), esc_attr($slug), $local ? ' checked="checked" ' : '');
} 
else {
$default_option = ($i == 0) ? $current_theme_option : '';
$hideafter = (empty($saved_themes)) ? 1 : sizeof($saved_themes);
$classAndID = ($i > $hideafter ) ? ' class="hidden_item theme" id="theme_'.esc_attr($i).'" ' : ' class="theme" id="theme_'.esc_attr($i).'" ';
printf('<p%s>%s: <select class="select_with_hidden" name="add_theme[]">%s</select> <input type="radio" name="ziplocal_theme[%d]" value="0" checked="checked" /> WordPress.org <input type="radio" name="ziplocal_theme[%d]" value="1" /> Local Zip WordPress.org</p>',wp_kses($classAndID, qckply_kses_allowed()),esc_html($label),wp_kses($default_option.$themeoptions, qckply_kses_allowed()),intval($i),intval($i),intval($i) );
}

}
if($themeslots > 1)
    echo "<p class=\"fineprint\">Make a selection, and another will be revealed</p>\n";

if($themeslots == 1 && sizeof($saved_themes) > 1) {
    array_shift($saved_themes); // don't include active theme
    foreach($saved_themes as $si => $saved_theme) {
    $i = $si + 1;
    if($saved_theme['themeData']['resource'] == 'wordpress.org/themes') {
        $slug = $saved_theme['themeData']['slug'];
        $local = 0;
    }
    else {
        $slug = $saved_theme['themeData']['url'];
        preg_match('/\/([a-z0-9\-]+)\.zip/',$slug,$match);
        $slug = $match[1];
        $local = 1;
    }
    printf('<p>Keep %s: <input type="checkbox" name="add_theme[]" value="%s" checked="checked" /> %s <input type="checkbox" name="ziplocal_theme[%d]" value="1" %s /> Local Zip</p>',esc_html($label),esc_attr($slug), esc_attr($slug), intval($i), $local ? ' checked="checked" ' : '');
} 

}
}

