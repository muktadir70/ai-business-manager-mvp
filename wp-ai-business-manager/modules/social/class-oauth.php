<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Social_OAuth {
    public static function get_authorize_url( $provider, $redirect_uri = '' ) {
        $redirect_uri = $redirect_uri ?: admin_url( 'admin.php?page=ai-biz-manager' );
        $provider = strtolower( (string) $provider );

        if ( 'facebook' === $provider ) {
            $app_id = get_option( 'ai_biz_manager_facebook_app_id', '' );
            if ( empty( $app_id ) ) {
                return '#';
            }
            $query = http_build_query(array(
                'client_id' => $app_id,
                'redirect_uri' => $redirect_uri,
                'scope' => 'pages_manage_posts,pages_read_engagement,pages_show_list,pages_messaging,pages_manage_metadata',
                'response_type' => 'code',
            ));
            return 'https://www.facebook.com/v19.0/dialog/oauth?' . $query;
        }

        if ( 'x' === $provider ) {
            $client_id = get_option( 'ai_biz_manager_x_client_id', '' );
            if ( empty( $client_id ) ) {
                return '#';
            }
            $query = http_build_query(array(
                'response_type' => 'code',
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'scope' => 'tweet.write users.read offline.access',
                'state' => wp_generate_uuid4(),
            ));
            return 'https://twitter.com/i/oauth2/authorize?' . $query;
        }

        return '#';
    }

    public static function exchange_code( $provider, $code, $redirect_uri ) {
        $provider = strtolower( (string) $provider );
        $code = sanitize_text_field( $code );
        if ( empty( $code ) ) {
            return false;
        }

        if ( 'facebook' === $provider ) {
            $app_id = get_option( 'ai_biz_manager_facebook_app_id', '' );
            $app_secret = get_option( 'ai_biz_manager_facebook_app_secret', '' );
            if ( empty( $app_id ) || empty( $app_secret ) ) {
                return false;
            }
            $body = array(
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'redirect_uri' => $redirect_uri,
                'code' => $code,
            );
            $response = wp_remote_post( 'https://graph.facebook.com/v19.0/oauth/access_token', array(
                'timeout' => 30,
                'body' => $body,
            ) );
            if ( is_wp_error( $response ) ) {
                return false;
            }
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data['access_token'] ) ) {
                self::store_token( 'facebook', $data );
                return true;
            }
            return false;
        }

        if ( 'x' === $provider ) {
            $client_id = get_option( 'ai_biz_manager_x_client_id', '' );
            $client_secret = get_option( 'ai_biz_manager_x_client_secret', '' );
            if ( empty( $client_id ) || empty( $client_secret ) ) {
                return false;
            }
            $auth = base64_encode( $client_id . ':' . $client_secret );
            $response = wp_remote_post( 'https://api.twitter.com/2/oauth2/token', array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => array(
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect_uri,
                ),
            ) );
            if ( is_wp_error( $response ) ) {
                return false;
            }
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data['access_token'] ) ) {
                self::store_token( 'x', $data );
                return true;
            }
            return false;
        }

        return false;
    }

    public static function store_token( $provider, $token_data ) {
        update_option( 'ai_biz_manager_token_' . sanitize_key( $provider ), $token_data );
    }

    public static function get_token( $provider ) {
        return get_option( 'ai_biz_manager_token_' . sanitize_key( $provider ), array() );
    }

    public static function connect_handler( $request ) {
        $params = $request->get_json_params();
        $provider = sanitize_text_field( $params['provider'] ?? '' );
        $redirect = sanitize_text_field( $params['redirect_uri'] ?? admin_url( 'admin.php?page=ai-biz-manager' ) );
        $url = self::get_authorize_url( $provider, $redirect );
        return rest_ensure_response( array( 'redirect_url' => $url ) );
    }

    public static function post_to_account_handler( $request ) {
        $params = $request->get_json_params();
        $provider = sanitize_text_field( $params['provider'] ?? '' );
        $message = sanitize_text_field( $params['message'] ?? '' );

        if ( empty( $provider ) || empty( $message ) ) {
            return new WP_Error( 'invalid_post', 'Provider and message are required.', array( 'status' => 400 ) );
        }

        $token = self::get_token( $provider );
        if ( empty( $token['access_token'] ) ) {
            return new WP_Error( 'missing_token', 'No OAuth token found for provider.', array( 'status' => 400 ) );
        }

        // Placeholder: provider-specific posting logic; this is where real API publication hooks go.
        return rest_ensure_response( array( 'success' => true, 'provider' => $provider, 'message' => $message ) );
    }
}
