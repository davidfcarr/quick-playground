<?php 
include 'premium.php';
function quickplayground_builder() {
?>
<h1>Blueprint Builder</h1>
<p>This is where you define which themes and plugins you want each WordPress Playground to include. You can create several Playground profiles, associated with different themes, plugins, and options.</p>
<?php
    global $wpdb, $current_user, $playground_uploads, $playground_uploads_url;
    $profile = isset($_REQUEST['profile']) ? preg_replace('/[^a-z0-9]+/','_',strtolower(sanitize_text_field($_REQUEST['profile']))) : 'default';
    $playground_api_url = rest_url('quickplayground/v1/blueprint/'.$profile).'?x='.time().'&user_id='.$current_user->ID;
    $clone_api_url = rest_url('quickplayground/v1/playground_clone/'.$profile);
    $origin_url = rtrim(get_option('siteurl'),'/');
    $stylesheet = get_stylesheet();
    blueprint_settings_init($profile,$stylesheet);
    $blueprint = get_option('playground_blueprint_'.$profile, array());
    $settings = get_option('playground_clone_settings_'.$profile,array());
    $stylesheet = $settings['clone_stylesheet'] ?? $stylesheet;

printf('<form method="post" action="%s"> %s <input type="hidden" name="build_profile" value="1">',admin_url('admin.php?page=quickplayground_builder'), wp_nonce_field('quickplayground','playground',true,false));
echo '<p><button>Refresh</button></p>';
echo '<h2>Customization Options</h2>';
printf('<p>Loading saved blueprint for profile %s with %d steps defined. You can add or modify themes, plugins, and settings below.</p>',htmlentities($profile),sizeof($blueprint['steps']));

$saved_plugins = $saved_themes = [];
$saved_code = '';

$themeslots = playground_premium_enabled() ? 10 + sizeof($saved_themes) : 1;
quickplayground_theme_options($blueprint, $stylesheet, $themeslots);
quickplayground_plugin_options($blueprint, !playground_premium_enabled());

if(!playground_premium_enabled()) {
    printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogname',$settings['blogname']);
    printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogdescription',$settings['blogdescription']);

    printf('<p><input type="checkbox" name="settings[key_pages]" value="1" %s > Include key pages and posts (linked to from the home page or menu)</p>',(!isset($settings['key_pages']) || $settings['key_pages']) ? ' checked="checked" ' : '');
    printf('<input type="hidden" name="profile" value="%s" />',$profile);
    echo '<p><button>Submit</button></p>';
    echo '</form>';
    return;
}

echo '<p>Additional themes or plugins from the WordPress repository<br /><textarea name="repo" cols="100" rows="3"></textarea><br>Example:<br /><em>https://wordpress.org/themes/oceanwp/</em><br /><em>https://wordpress.org/plugins/rsvpmaker/</em></p>';

if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
            if('runPHP' == $step['step']) {
                if(!strpos($step['code'],'quickplayground_clone')) {
                $saved_code = $step['code'];
                }
            }
        }
}

printf('<p>Custom PHP code *<br /><textarea name="add_code[]" cols="100" rows="3">%s</textarea></p>',htmlentities($saved_code));

printf('<p>Custom Step (JSON)<br /><textarea name="json_steps" cols="100" rows="3">%s</textarea></p><p>Example from the <a href="https://wordpress.github.io/wordpress-playground/blueprints/examples/">documentation</a>:</p><pre>%s</pre>','','
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

echo '<br /><code>&lt;?php require_once \'wordpress/wp-load.php\';</code> will be added automatically to enable WordPress and plugin functions.
</p>';

if(!empty($settings['page_on_front'])) {
    $page_on_front = $settings['page_on_front'];
}

$front = $page_options = $post_options = '';
$pages = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='page' AND (post_status='publish' OR post_status='draft') ORDER BY post_status, post_title ");
foreach($pages as $page) {
    $status = ('draft' == $page->post_status) ? '(Draft)' : '';
    $opt = sprintf('<option value="%d" >%s %s</option>',$page->ID,$page->post_title,$status);
    if($settings['page_on_front'] == $page->ID)
        $front = $opt;
    $page_options .= $opt;
}
$demoposts = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type='post' AND (post_status='publish' OR post_status='draft') ORDER BY ID DESC ");
foreach($demoposts as $p) {
    $status = ('draft' == $p->post_status) ? '(Draft)' : '';
    $opt = sprintf('<option value="%d" >%s %s</option>',$p->ID,$p->post_title,$status);
    $post_options .= $opt;
}

echo '<h2>'.__('Content to Include','design-plugin-playground').'</h2>';
$front .= '<option value="">Blog Listing</option>';

printf('<p>Front Page: <select name="settings[page_on_front]">%s</select></p>',$front.$page_options);

printf('<p><input type="checkbox" name="settings[key_pages]" value="1" %s > Include key pages and posts (linked to from the home page or menu)</p>',(!isset($settings['key_pages']) || $settings['key_pages']) ? ' checked="checked" ' : '');
//quickplayground_key_pages_checkboxes();

$rsvp_checked = (!isset($settings['copy_events']) || $settings['copy_events']) ? ' checked="checked" ' : '';
$rsvp_prompt = (function_exists('get_rsvpmaker_event_table')) ? '<input type="checkbox" name="settings[copy_events]" value="1" '.$rsvp_checked.' > Future RSVPMaker events ' : ''; 

printf('<p>Copy <input type="checkbox" name="settings[copy_pages]" value="1" %s > all published pages <input style="width:5em" type="number" name="settings[copy_blogs]" value="%d" size="3" > latest blog posts '.$rsvp_prompt.'</p>',!empty($settings['copy_pages']) ? ' checked="checked" ' : '',(!isset($settings['copy_blogs']) || $settings['copy_blogs']) ? intval($settings['copy_blogs']) : 10);

if(!empty($settings['demo_pages']) && is_array($settings['demo_pages'])) {
    foreach($settings['demo_pages'] as $page_id) {
        if(!empty($page_id)) {
            printf('<p>Keep Demo Page <input type="checkbox" name="demo_pages[]" value="%d" checked="checked" /> %s</p>'."\n",$page_id,get_the_title($page_id));
        }
    }
}
for($i = 0; $i < 10; $i++) {
$classAndID = ($i > 0) ? ' class="hidden_item page" id="page_'.$i.'" ' : ' class="page" id="page_'.$i.'" ';
printf('<p%s>Demo Page: <select class="select_with_hidden" name="demo_pages[]">%s</select></p>'."\n",$classAndID,'<option value="">Choose Page</option>'.$page_options);
}
printf('<p><input type="checkbox" name="settings[make_menu]" value="1" %s > Make menu from selected pages</p>',empty($settings['make_menu']) ? '' : 'checked="checked"');

if(!empty($settings['demo_posts']) && is_array($settings['demo_posts'])) {
    foreach($settings['demo_posts'] as $id) {
        if(!empty($id)) {
            printf('<p>Keep Demo Post <input type="checkbox" name="demo_posts[]" value="%d" checked="checked" /> %s</p>'."\n",$id,get_the_title($id));
        }
    }
}
for($i = 0; $i < 10; $i++) {
$classAndID = ($i > 0) ? ' class="hidden_item post" id="post_'.$i.'" ' : ' class="post" id="post_'.$i.'" ';
printf('<p%s>Demo Blog Post: <select class="select_with_hidden" name="demo_posts[]">%s</select></p>'."\n",$classAndID,'<option value="">Choose Blog Post</option>'.$post_options);
}

do_action('quickplayground_form_demo_content');

$types = get_post_types(array(
   'public'   => true,
   '_builtin' => false
),'objects','and');

if(!empty($types)) {
echo '<h2>'.__('Additional Content Types','design-plugin-playground').'</h2>';
echo '<p>'.__('Content types added by plugins','design-plugin-playground').'</p>';
    foreach($types as $type) {
        if(strpos($type->name,'rsvp') !== false)
            continue;
        $checked = (!empty($settings['post_types']) && is_array($settings['post_types']) && in_array($type->name,$settings['post_types'])) ? ' checked="checked" ' : '';
        printf('<p><input type="checkbox" name="post_types[]" value="%s" %s > %s (%s)</p>',$type->name,$checked,$type->label,$type->name);
    }
}

printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogname',$settings['blogname']);
printf('<p>Option %s <br /><input name="settings[%s]" type="text" value="%s" /></p>','Site Name','blogdescription',$settings['blogdescription']);

echo '<p><input type="checkbox" name="show_details" value="1" /> Show Detailed Output</p>';
echo '<p><input type="checkbox" name="show_blueprint" value="1" /> Show Blueprint JSON</p>';
echo '<p><input type="checkbox" name="logerrors" value="1" /> Log Errors in Playground</p>';
printf('<input type="hidden" name="profile" value="%s" />',$profile);
if(playground_premium_enabled())
    do_action('quickplayground_additional_setup_form_fields');
echo '<p><button>Submit</button></p>';
echo '</form>';

printf('<h3>For Testing</h3><p>Blueprint API URL: <a href="%s" target="_blank">%s</a></p>',esc_url($playground_api_url),esc_html($playground_api_url));
printf('<p>Clone API URL: <a href="%s" target="_blank">%s</a></p>',esc_url($clone_api_url),esc_html($clone_api_url));
printf('<p>Playground button code</p><p><textarea cols="100" rows="5">%s</textarea></p>',htmlentities(quickplayground_get_button($profile)));
}

function quickplayground_plugin_options($blueprint, $active_only = true) {
    if(!function_exists('get_plugins'))
        require_once(ABSPATH.'/wp-admin/includes/plugins.php');

    $excluded_plugins = (is_multisite()) ? get_blog_option(1,'playground_excluded_plugins',array()) : array();
    $active_plugins = [];
    $all_plugins = [];
    $pluginoptions = '<option value="">Select a plugin</option>';
    $active_pluginoptions = '<option value="">Select a plugin</option>';
    $plugins = get_plugins();//['allowed'=>true]

    foreach($plugins as $dir_file => $header) {
        $parts = preg_split('/[\.\/]/',$dir_file);
        $basename = $parts[0];
        if(in_array($basename,$excluded_plugins) || 'design-plugin-playground' == $basename) {
            continue; // skip excluded plugins
        }
        $all_plugins[$basename] = $header["Name"];
        if(is_plugin_active($dir_file) )
        {
            if(!in_array($basename,$excluded_plugins))
                $active_plugins[] = $basename;
            $active_pluginoptions .= sprintf('<option value="%s">%s (%s)</option>',$basename, $header["Name"],__('Active','theme-plugin-playground'));
        }
        elseif(!$active_only)
            $pluginoptions .= sprintf('<option value="%s">%s</option>',$basename, $header["Name"]);
    }

    if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
        if(is_array($step)) {
            if('installPlugin' == $step['step']) {
                if(isset($_GET['update']) && $step['pluginData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['pluginData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',$matches[1]);
                        quickplayground_playground_zip_plugin($matches[1]);
                    }
                $saved_plugins[] = $step;
            }
        }
    }
}

echo '<h2>Choose Plugins for Your Playground</h2>';
$plausible_plugins = quickplayground_plausible_plugins();
if(!empty($plausible_plugins['active'])) {
echo '<p><input type="checkbox" name="all_active_plugins" value="'.implode(',',$plausible_plugins['active']).'"> Include these active plugins ('.implode(', ',$plausible_plugins['active_names']).')</p>';
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
    $active = (!empty($saved_plugins[$i]['options']['activate']) ) ? ' checked="checked" ' : '';
    if(!empty($slug))
    printf('<p>Keep Plugin: <input type="checkbox" name="add_plugin[]" value="%s" checked="checked" /> %s <input type="checkbox" name="ziplocal_plugin[%d]" value="1" %s /> Local Zip <input type="checkbox" name="activate_plugin[%d]" value="1" %s /> Activate </p>',$slug, $slug, $i,$local ? ' checked="checked" ' : '',$i,$active);
} 
else {
    $classAndID = ($i > 0 + sizeof($saved_plugins)) ? ' class="hidden_item plugin" id="plugin_'.$i.'" ' : ' class="plugin" id="plugin_'.$i.'" ';
    printf('<p%s>Add Plugin: <select class="select_with_hidden" name="add_plugin[]">%s</select>  <input type="radio" name="ziplocal_plugin[%d]" value="0" checked="checked" /> WordPress.org <input type="radio" name="ziplocal_plugin[%d]" value="1" /> Local Zip <br /><input type="radio" name="activate_plugin[%d]" value="1" checked="checked" /> Activate <input type="radio" name="activate_plugin[%d]" value="0" /> Do Not Activate  </p>',$classAndID,$active_pluginoptions.$pluginoptions,$i,$i,$i,$i);
    }
}
echo "<p class=\"fineprint\">Make a selection, and another will be revealed</p>\n";

}

function quickplayground_theme_options($blueprint, $stylesheet, $themeslots = 1) {
$excluded_themes = (is_multisite()) ? get_blog_option(1,'playground_excluded_themes',array()) : array();
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
            $current_theme_option = sprintf('<option value="%s">%s</option>',$styleslug, $themeobj->__get('name'));
        }
        $themeoptions .= sprintf('<option value="%s">%s</option>',$styleslug, $themeobj->__get('name'));
    }

if(!empty($blueprint)) {
    foreach($blueprint['steps'] as $step) {
        if(is_array($step)) {
            if('installTheme' == $step['step']) {
                if(isset($_GET['update']) && $step['themeData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['themeData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',$matches[1]);
                        quickplayground_playground_zip_theme($matches[1]);
                    }
                $saved_themes[] = $step;
            }
            elseif('installPlugin' == $step['step']) {
                if(isset($_GET['update']) && $step['pluginData']['resource'] == 'url')
                    {
                        preg_match('/([a-z0-9-_]+)\.zip/',$step['pluginData']['url'],$matches);
                        printf('<p>Zip update for %s</p>',$matches[1]);
                        quickplayground_playground_zip_plugin($matches[1]);
                    }
                $saved_plugins[] = $step;
            }
            elseif('runPHP' == $step['step']) {
                if(!strpos($step['code'],'quickplayground_clone')) {
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
    printf('<p>Keep %s: <input type="checkbox" name="add_theme[]" value="%s" checked="checked" /> %s <input type="checkbox" name="ziplocal_theme[%d]" value="1" %s /> Local Zip</p>',$label,$slug, $slug, $i, $local ? ' checked="checked" ' : '');
} 
else {
$default_option = ($i == 0) ? $current_theme_option : '';
$hideafter = (empty($saved_themes)) ? 1 : sizeof($saved_themes);
$classAndID = ($i > $hideafter ) ? ' class="hidden_item theme" id="theme_'.$i.'" ' : ' class="theme" id="theme_'.$i.'" ';
printf('<p%s>%s: <select class="select_with_hidden" name="add_theme[]">%s</select> <input type="radio" name="ziplocal_theme[%d]" value="0" checked="checked" /> WordPress.org <input type="radio" name="ziplocal_theme[%d]" value="1" /> Local Zip WordPress.org</p>',$classAndID,$label,$default_option.$themeoptions,$i,$i,$i );
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
    printf('<p>Keep %s: <input type="checkbox" name="add_theme[]" value="%s" checked="checked" /> %s <input type="checkbox" name="ziplocal_theme[%d]" value="1" %s /> Local Zip</p>',$label,$slug, $slug, $i, $local ? ' checked="checked" ' : '');
} 

}

}