<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'ai-biz-manager/v1', '/messenger-webhook', array(
        'methods' => WP_REST_Server::ALLMETHODS,
        'callback' => array( 'AI_Biz_Messenger', 'handle_webhook' ),
        'permission_callback' => '__return_true',
    ) );
} );

class AI_Biz_Messenger {
    public static function handle_webhook( $request ) {
        if ( $request->get_method() === 'GET' ) {
            $hub_mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
            $hub_challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';
            $hub_verify_token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
            $stored = get_option( 'ai_biz_manager_messenger_verify_token', '' );
            if ( $hub_verify_token && $hub_verify_token === $stored ) {
                header( 'HTTP/1.1 200 OK' );
                echo $hub_challenge;
                exit;
            }
            return new WP_Error( 'invalid_verify', 'Invalid verify token', array( 'status' => 403 ) );
        }

        $body = json_decode( $request->get_body(), true );
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[AI Biz Messenger] webhook: ' . print_r( $body, true ) );
        }

        return rest_ensure_response( array( 'received' => true ) );
    }
}
