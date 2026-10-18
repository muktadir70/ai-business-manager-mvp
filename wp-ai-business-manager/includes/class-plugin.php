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
        add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );

        require_once plugin_dir_path( __FILE__ ) . 'admin-settings.php';
        require_once plugin_dir_path( __FILE__ ) . '../modules/importer/class-importer.php';
        require_once plugin_dir_path( __FILE__ ) . '../modules/social/class-oauth.php';
        require_once plugin_dir_path( __FILE__ ) . '../modules/messenger/class-messenger.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-ai-client.php';
    }

    public static function activate() {
        if ( ! get_option( 'ai_biz_manager_api_token' ) ) {
            update_option( 'ai_biz_manager_api_token', wp_generate_password( 32, false, false ) );
        }
        if ( ! get_option( 'ai_biz_manager_price_margin', false ) ) {
            update_option( 'ai_biz_manager_price_margin', 40 );
        }
        if ( ! get_option( 'ai_biz_manager_ai_provider', false ) ) {
            update_option( 'ai_biz_manager_ai_provider', 'openai' );
        }
    }

    public static function deactivate() {
        // Clean scheduled jobs if needed
    }

    public function init_hooks() {
        load_plugin_textdomain( 'ai-biz-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function admin_menu() {
        add_menu_page(
            'AI Business Manager',
            'AI Biz Manager',
            'manage_options',
            'ai-biz-manager',
            array( 'AI_Biz_Manager_Admin', 'render_settings_page' ),
            'dashicons-admin-generic',
            56
        );
    }

    public function register_rest_routes() {
        $ns = self::REST_NAMESPACE;
        register_rest_route( $ns, '/import', array(
            'methods'  => 'POST',
            'callback' => array( 'AI_Biz_Importer', 'handle_import' ),
            'permission_callback' => array( 'AI_Biz_Importer', 'import_permission_check' ),
        ) );

        register_rest_route( $ns, '/oauth/connect', array(
            'methods'  => 'POST',
            'callback' => array( 'AI_Biz_Social_OAuth', 'connect_handler' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( $ns, '/social/post', array(
            'methods'  => 'POST',
            'callback' => array( 'AI_Biz_Social_OAuth', 'post_to_account_handler' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    public function handle_oauth_callback() {
        if ( ! isset( $_GET['page'] ) || 'ai-biz-manager' !== $_GET['page'] ) {
            return;
        }
        $provider = isset( $_GET['oauth_provider'] ) ? sanitize_text_field( wp_unslash( $_GET['oauth_provider'] ) ) : '';
        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        if ( empty( $provider ) || empty( $code ) ) {
            return;
        }

        $redirect_uri = admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=' . rawurlencode( $provider ) );
        $result = AI_Biz_Social_OAuth::exchange_code( $provider, $code, $redirect_uri );
        if ( $result ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>OAuth connected successfully.</p></div>';
            } );
        } else {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>OAuth connection failed. Check app settings and redirect URI.</p></div>';
            } );
        }
    }
}
