<?php



function makeBlueprintItem($step,$vars = array(), $options = array()) {

    if(!empty($vars))

        $bp = array_merge(array('step' => $step), $vars);

    else

        $bp = array('step' => $step);

    if(!empty($options)) {

        $bp['options'] = $options;

    }

    return $bp;

}



function makePluginItem($slug, $public = true, $activate = false) {

    return makeBlueprintItem('installPlugin', array('pluginData'=>playgroundData($slug, 'plugin', $public)), array('activate'=>$activate));

}

function makeThemeItem($slug, $public = true, $activate = false) {

    return makeBlueprintItem('installTheme', array('themeData'=>playgroundData($slug, 'theme', $public)), array('activate'=>$activate));

}

function makeCodeItem($code) {

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
    printf('makeCodeItem(%s)', htmlentities($code));

    return makeBlueprintItem('runPHP', array('code'=>$prefix.$code));

}

function playgroundData($slug, $type = 'plugin', $public = true) {

    global $playground_uploads_url;

    $data = array();

    if($public) {

        $data['resource'] = ('plugin' === $type) ? 'wordpress.org/plugins' : 'wordpress.org/themes';

        $data['slug'] = $slug;

    } else {

        $data['resource'] = 'url';

        $data['url'] = $playground_uploads_url.'/'.$slug.'.zip?x='.time();

    }

    return $data;

}

function ProPlaygroundData($key) {
    $email = get_option('playground_premium_email');
    $data['resource'] = 'url';
    $data['url'] = 'https://davidfcarr.com/wp-json/quickplayground/v1/playground_pro_download?email=david@carrcommunications.com&key=ze3HICuGY05Vvc0zhDaURT1bGLiqEqSdN3TPTlv5gDnKyg4RuN&x='.time();
    return $data;
}
