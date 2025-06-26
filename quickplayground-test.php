<?php
function quickplayground_test() {
    global $wpdb;
    $plugins = quickplayground_plausible_plugins();
    foreach($plugins['active'] as $slug => $name) {
        printf('<p>Active Plugin: %s %s</p>',$slug, $name);
    }
    foreach($plugins['inactive'] as $slug => $name) {
        printf('<p>Inactive Plugin: %s %s</p>',$slug, $name);
    }
}
