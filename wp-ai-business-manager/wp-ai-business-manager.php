<?php
/**
 * Plugin Name: AI Business Manager
 * Plugin URI:  https://example.com/
 * Description: Modular plugin to import products, connect social, automate marketing (MVP scaffold).
 * Version:     0.1
 * Author:      Copilot
 * Text Domain: ai-biz-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'AI_Biz_Manager_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AI_Biz_Manager_Plugin', 'deactivate' ) );

AI_Biz_Manager_Plugin::init_once();
