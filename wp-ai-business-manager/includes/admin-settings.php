<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Manager_Admin {
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'ai-biz-manager' ) );
        }

        if ( isset( $_POST['ai_biz_manager_settings_nonce'] ) && wp_verify_nonce( $_POST['ai_biz_manager_settings_nonce'], 'ai_biz_manager_save' ) ) {
            update_option( 'ai_biz_manager_openai_key', sanitize_text_field( $_POST['ai_biz_manager_openai_key'] ) );
            update_option( 'ai_biz_manager_api_token', sanitize_text_field( $_POST['ai_biz_manager_api_token'] ) );
            update_option( 'ai_biz_manager_price_margin', floatval( $_POST['ai_biz_manager_price_margin'] ) );
            update_option( 'ai_biz_manager_ai_provider', sanitize_text_field( $_POST['ai_biz_manager_ai_provider'] ) );
            update_option( 'ai_biz_manager_facebook_app_id', sanitize_text_field( $_POST['ai_biz_manager_facebook_app_id'] ) );
            update_option( 'ai_biz_manager_facebook_app_secret', sanitize_text_field( $_POST['ai_biz_manager_facebook_app_secret'] ) );
            update_option( 'ai_biz_manager_x_client_id', sanitize_text_field( $_POST['ai_biz_manager_x_client_id'] ) );
            update_option( 'ai_biz_manager_x_client_secret', sanitize_text_field( $_POST['ai_biz_manager_x_client_secret'] ) );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $openai_key = esc_attr( get_option( 'ai_biz_manager_openai_key', '' ) );
        $api_token  = esc_attr( get_option( 'ai_biz_manager_api_token', '' ) );
        $price_margin = esc_attr( get_option( 'ai_biz_manager_price_margin', 40 ) );
        $ai_provider = esc_attr( get_option( 'ai_biz_manager_ai_provider', 'openai' ) );
        $facebook_app_id = esc_attr( get_option( 'ai_biz_manager_facebook_app_id', '' ) );
        $facebook_app_secret = esc_attr( get_option( 'ai_biz_manager_facebook_app_secret', '' ) );
        $x_client_id = esc_attr( get_option( 'ai_biz_manager_x_client_id', '' ) );
        $x_client_secret = esc_attr( get_option( 'ai_biz_manager_x_client_secret', '' ) );

        $facebook_auth_url = AI_Biz_Social_OAuth::get_authorize_url( 'facebook', admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=facebook' ) );
        $x_auth_url = AI_Biz_Social_OAuth::get_authorize_url( 'x', admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=x' ) );
        ?>
        <div class="wrap">
            <h1>AI Business Manager Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'ai_biz_manager_save', 'ai_biz_manager_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_openai_key">OpenAI API Key</label></th>
                        <td><input name="ai_biz_manager_openai_key" id="ai_biz_manager_openai_key" type="text" value="<?php echo $openai_key; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_api_token">Browser API Token</label></th>
                        <td><input name="ai_biz_manager_api_token" id="ai_biz_manager_api_token" type="text" value="<?php echo $api_token; ?>" class="regular-text" /><p class="description">Use this token in the browser extension as X-AI-BIZ-TOKEN.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_price_margin">Default Price Markup (%)</label></th>
                        <td><input name="ai_biz_manager_price_margin" id="ai_biz_manager_price_margin" type="number" step="0.1" value="<?php echo $price_margin; ?>" class="small-text" /><p class="description">Used to compute selling price from source price.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_ai_provider">AI Provider</label></th>
                        <td>
                            <select name="ai_biz_manager_ai_provider" id="ai_biz_manager_ai_provider">
                                <option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>OpenAI</option>
                                <option value="azure" <?php selected( $ai_provider, 'azure' ); ?>>Azure OpenAI</option>
                                <option value="anthropic" <?php selected( $ai_provider, 'anthropic' ); ?>>Anthropic</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_facebook_app_id">Facebook App ID</label></th>
                        <td><input name="ai_biz_manager_facebook_app_id" id="ai_biz_manager_facebook_app_id" type="text" value="<?php echo $facebook_app_id; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_facebook_app_secret">Facebook App Secret</label></th>
                        <td><input name="ai_biz_manager_facebook_app_secret" id="ai_biz_manager_facebook_app_secret" type="password" value="<?php echo $facebook_app_secret; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_x_client_id">X Client ID</label></th>
                        <td><input name="ai_biz_manager_x_client_id" id="ai_biz_manager_x_client_id" type="text" value="<?php echo $x_client_id; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_x_client_secret">X Client Secret</label></th>
                        <td><input name="ai_biz_manager_x_client_secret" id="ai_biz_manager_x_client_secret" type="password" value="<?php echo $x_client_secret; ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2>Connect social accounts</h2>
            <p><a class="button button-primary" href="<?php echo esc_url( $facebook_auth_url ); ?>">Connect Facebook</a></p>
            <p><a class="button button-secondary" href="<?php echo esc_url( $x_auth_url ); ?>">Connect X</a></p>
        </div>
        <?php
    }
}
