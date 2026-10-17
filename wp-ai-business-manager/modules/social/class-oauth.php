<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Basic OAuth scaffolding for social platforms (FB Pages, X). Implement per-provider in future.
class AI_Biz_Social_OAuth {
    public static function get_redirect_url( $provider ) {
        return admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=' . rawurlencode( $provider ) );
    }

    public static function store_token( $provider, $token_data ) {
        $opt = 'ai_biz_manager_token_' . $provider;
        update_option( $opt, $token_data );
    }

    public static function get_token( $provider ) {
        return get_option( 'ai_biz_manager_token_' . $provider );
    }

    // Placeholder: Exchange code for token — provider-specific implementation required
    public static function exchange_code( $provider, $code ) {
        // Implement provider-specific token exchange here.
        return false;
    }
}
