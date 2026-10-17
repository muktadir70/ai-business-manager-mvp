<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Manager_Plugin {
    private static $instance = null;
    const REST_NAMESPACE = 'ai-biz-manager/v1';

    public static function init_once() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'init_hooks' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        require_once plugin_dir_path( __FILE__ ) . 'admin-settings.php';
        require_once plugin_dir_path( __FILE__ ) . '../modules/importer/class-importer.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-ai-client.php';
    }

    public static function activate() {
        // Add default options or scheduled events
        if ( ! get_option( 'ai_biz_manager_api_token' ) ) {
            update_option( 'ai_biz_manager_api_token', wp_generate_password( 32, false, false ) );
        }
    }

    public static function deactivate() {
        // Clean scheduled jobs if needed
    }

    public function init_hooks() {
        load_plugin_textdomain( 'ai-biz-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function admin_menu() {
        add_menu_page( 'AI Business Manager', 'AI Biz Manager', 'manage_options', 'ai-biz-manager', array( 'AI_Biz_Manager_Admin', 'render_settings_page' ), 'dashicons-admin-generic', 56 );
    }

    public function register_rest_routes() {
        $ns = self::REST_NAMESPACE;
        register_rest_route( $ns, '/import', array(
            'methods'  => 'POST',
            'callback' => array( 'AI_Biz_Importer', 'handle_import' ),
            'permission_callback' => array( 'AI_Biz_Importer', 'import_permission_check' ),
        ) );
    }
}
