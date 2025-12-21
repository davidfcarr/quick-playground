<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Creates a blueprint step array for the given step, variables, and options.
 *
 * @param string $step    The step name.
 * @param array  $vars    Optional. Variables for the step.
 * @param array  $options Optional. Options for the step.
 * @return array          Blueprint step array.
 */
function qckply_makeBlueprintItem($step, $vars = array(), $options = array()) {
    if(!empty($vars))
        $bp = array_merge(array('step' => $step), $vars);
    else
        $bp = array('step' => $step);

    if(!empty($options)) {
        $bp['options'] = $options;
    }

    return $bp;
}

/**
 * Creates a blueprint step for installing a plugin.
 *
 * @param string  $slug     The plugin slug.
 * @param bool    $public   Optional. Whether the plugin is public. Default true.
 * @param bool    $activate Optional. Whether to activate the plugin. Default false.
 * @return array            Blueprint step array.
 */
function qckply_makePluginItem($slug, $public = true, $activate = false) {
    return qckply_makeBlueprintItem('installPlugin', array('pluginData'=>qckply_playgroundData($slug, 'plugin', $public)), array('activate'=>$activate));
}

/**
 * Creates a blueprint step for installing a theme.
 *
 * @param string  $slug     The theme slug.
 * @param bool    $public   Optional. Whether the theme is public. Default true.
 * @param bool    $activate Optional. Whether to activate the theme. Default false.
 * @return array            Blueprint step array.
 */
function qckply_makeThemeItem($slug, $public = true, $activate = false) {
    return qckply_makeBlueprintItem('installTheme', array('themeData'=>qckply_playgroundData($slug, 'theme', $public)), array('activate'=>$activate));
}

/**
 * Creates a blueprint step for running custom PHP code.
 *
 * @param string $code The PHP code to run.
 * @return array       Blueprint step array.
 */
function qckply_makeCodeItem($code) {
    if(is_array($code)) {
        die(wp_kses_post(var_export($code, true)));
    }
    $prefix = '';
    if(!strpos($code, '?php')) {
        $prefix = "<?php \n";
    }
    if(!strpos($code, 'wp-load.php')) {
        $prefix .= "require_once('wp-load.php');\n";
    }
    if(!strpos($code, '?>')) {
        $code .= "\n?>";
    }
    return qckply_makeBlueprintItem('runPHP', array('code'=>$prefix.$code));
}

/**
 * Returns plugin or theme data for use in a blueprint step.
 *
 * @param string $slug   The plugin or theme slug.
 * @param string $type   Optional. 'plugin' or 'theme'. Default 'plugin'.
 * @param bool   $public Optional. Whether the resource is public. Default true.
 * @return array         Data array for the resource.
 */
function qckply_playgroundData($slug, $type = 'plugin', $public = true) {
    $qckply_directories = qckply_get_directories();
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $data = array();
    if($public) {
        $data['resource'] = ('plugin' === $type) ? 'wordpress.org/plugins' : 'wordpress.org/themes';
        $data['slug'] = $slug;
    } else {
        $data['resource'] = 'url';
        $data['url'] = rest_url('quickplayground/v1/download/'.$slug.'.zip?t=TIMESTAMP');  //$qckply_uploads_url.'/'.$slug.'.zip?t=TIMESTAMP';
    }
    return $data;
}

