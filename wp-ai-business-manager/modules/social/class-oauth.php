<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Social OAuth + posting helpers for Facebook and X (Twitter)
class AI_Biz_Social_OAuth {
    private static function env_or_option( $env_name, $option_name ) {
        $val = getenv( $env_name );
        if ( $val !== false && $val !== '' ) return $val;
        return get_option( $option_name, '' );
    }

    public static function get_authorize_url( $provider, $redirect_uri = '' ) {
        $redirect_uri = $redirect_uri ?: admin_url( 'admin.php?page=ai-biz-manager' );
        $provider = strtolower( (string) $provider );

        if ( 'facebook' === $provider ) {
            $app_id = self::env_or_option( 'FB_APP_ID', 'ai_biz_facebook_app_id' );
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
            $client_id = self::env_or_option( 'X_CLIENT_ID', 'ai_biz_x_client_id' );
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
            $app_id = self::env_or_option( 'FB_APP_ID', 'ai_biz_facebook_app_id' );
            $app_secret = self::env_or_option( 'FB_APP_SECRET', 'ai_biz_facebook_app_secret' );
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
            if ( empty( $data['access_token'] ) ) {
                return false;
            }

            // Optionally exchange for long-lived token
            $short_token = $data['access_token'];
            $long = wp_remote_get( add_query_arg(array(
                'grant_type' => 'fb_exchange_token',
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'fb_exchange_token' => $short_token,
            ), 'https://graph.facebook.com/v19.0/oauth/access_token' ), array( 'timeout' => 30 ) );
            if ( ! is_wp_error( $long ) ) {
                $longdata = json_decode( wp_remote_retrieve_body( $long ), true );
                if ( ! empty( $longdata['access_token'] ) ) {
                    $data = array_merge( $data, $longdata );
                }
            }

            // Store user access token
            self::store_token( 'facebook', $data );

            // Fetch pages for which the user is admin and store page access tokens
            if ( ! empty( $data['access_token'] ) ) {
                self::fetch_and_store_facebook_pages( $data['access_token'] );
            }

            return true;
        }

        if ( 'x' === $provider ) {
            $client_id = self::env_or_option( 'X_CLIENT_ID', 'ai_biz_x_client_id' );
            $client_secret = self::env_or_option( 'X_CLIENT_SECRET', 'ai_biz_x_client_secret' );
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
            if ( empty( $data['access_token'] ) ) {
                return false;
            }
            // Store token
            self::store_token( 'x', $data );
            return true;
        }

        return false;
    }

    private static function fetch_and_store_facebook_pages( $user_access_token ) {
        $resp = wp_remote_get( add_query_arg( 'access_token', $user_access_token, 'https://graph.facebook.com/v19.0/me/accounts' ), array( 'timeout' => 30 ) );
        if ( is_wp_error( $resp ) ) return;
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) return;
        $pages = array();
        foreach ( $data['data'] as $p ) {
            if ( isset( $p['id'] ) && isset( $p['access_token'] ) ) {
                $pages[ $p['id'] ] = array(
                    'id' => $p['id'],
                    'name' => $p['name'] ?? '',
                    'access_token' => $p['access_token'],
                );
            }
        }
        if ( ! empty( $pages ) ) {
            update_option( 'ai_biz_facebook_pages', $pages );
        }
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
        $target = sanitize_text_field( $params['target'] ?? '' ); // page id or account id

        if ( empty( $provider ) || empty( $message ) ) {
            return new WP_Error( 'invalid_post', 'Provider and message are required.', array( 'status' => 400 ) );
        }

        if ( 'facebook' === $provider ) {
            $res = self::post_to_facebook( $message, $target );
            return rest_ensure_response( $res );
        }

        if ( 'x' === $provider ) {
            $res = self::post_to_x( $message );
            return rest_ensure_response( $res );
        }

        return new WP_Error( 'unsupported', 'Provider not supported.', array( 'status' => 400 ) );
    }

    public static function post_to_facebook( $message, $page_id = '' ) {
        // Choose a page: if page_id provided use it, else take first stored page
        $pages = get_option( 'ai_biz_facebook_pages', array() );
        if ( empty( $page_id ) ) {
            if ( empty( $pages ) ) {
                return array( 'success' => false, 'error' => 'No Facebook pages available. Connect app and grant page permissions.' );
            }
            $page = reset( $pages );
            $page_id = $page['id'];
            $page_token = $page['access_token'];
        } else {
            if ( empty( $pages[ $page_id ] ) ) {
                return array( 'success' => false, 'error' => 'Requested page not found in stored pages.' );
            }
            $page_token = $pages[ $page_id ]['access_token'];
        }

        $endpoint = "https://graph.facebook.com/" . $page_id . "/feed";
        $body = array(
            'message' => $message,
            'access_token' => $page_token,
        );
        $resp = wp_remote_post( $endpoint, array( 'body' => $body, 'timeout' => 30 ) );
        if ( is_wp_error( $resp ) ) {
            return array( 'success' => false, 'error' => $resp->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( isset( $data['id'] ) ) {
            return array( 'success' => true, 'post_id' => $data['id'] );
        }
        return array( 'success' => false, 'response' => $data );
    }

    public static function post_to_x( $message ) {
        $token = self::get_token( 'x' );
        if ( empty( $token['access_token'] ) ) {
            return array( 'success' => false, 'error' => 'No X (Twitter) access token found. Connect app.' );
        }
        $access_token = $token['access_token'];
        $endpoint = 'https://api.twitter.com/2/tweets';
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( array( 'text' => $message ) ),
            'timeout' => 30,
        );
        $resp = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $resp ) ) {
            return array( 'success' => false, 'error' => $resp->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( isset( $data['data'] ) && isset( $data['data']['id'] ) ) {
            return array( 'success' => true, 'tweet_id' => $data['data']['id'], 'response' => $data );
        }
        return array( 'success' => false, 'response' => $data );
    }
}
