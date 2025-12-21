<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('api.php');
require_once('blueprint-builder.php');
require_once('blueprint-settings-init.php');
require_once('build.php');
require_once('clone.php');
require_once('filters.php');
require_once("key_pages.php");
require_once('makeBlueprintItem.php');
require_once('quickplayground_design_clone.php');
require_once('quickplayground-updates.php');
require_once('utility.php');
require_once('qckply-iframe.php');
require_once('qckply-loading.php');
require_once('qckply/qckply.php');

add_action('plugins_loaded','quickplayground_expro_includes');

function quickplayground_expro_includes() {
    if ( !is_plugin_active( 'quick-playground-pro/quick-playground-pro.php' ) ) {
        //plugin isctivated
        require_once plugin_dir_path( __FILE__ ) . 'expro-quickplayground-sync.php';
        require_once plugin_dir_path( __FILE__ ) . 'expro-api.php';
        require_once plugin_dir_path( __FILE__ ) . 'expro-filters.php';
        //require_once plugin_dir_path( __FILE__ ) . 'premium.php';
        if(is_multisite())
            require_once plugin_dir_path( __FILE__ ) . 'expro-networkadmin.php';
    }

    if(qckply_is_playground()) {
        require_once plugin_dir_path( __FILE__ ) . 'client.php';
        require_once plugin_dir_path( __FILE__ ) . 'client-save-playground.php';
        require_once plugin_dir_path( __FILE__ ) . 'client-demo-filters.php';
        require_once plugin_dir_path( __FILE__ ) . 'client-prompts.php';
        require_once plugin_dir_path( __FILE__ ) . 'client-qckply_data.php';
        require_once plugin_dir_path( __FILE__ ) . 'client-menu.php';
    }
}

add_action('admin_notices','quickplayground_expro_notice');
function quickplayground_expro_notice() {
    if ( is_plugin_active( 'quick-playground-pro/quick-playground-pro.php' ) ) {
        deactivate_plugins( 'quick-playground-pro/quick-playground-pro.php' );
?>
<div class="notice notice-warning is-dismissible">
<p><?php esc_html_e( 'Deactivating Quick Playground Pro (2025 version), which is no longer required for save and sync functions.', 'quick-playground' ); ?></p>
</div>
<?php
    }
}
