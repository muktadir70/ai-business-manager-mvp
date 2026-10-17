<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Manager_Admin {
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'ai-biz-manager' ) );
        }

        // Save posted settings
        if ( isset( $_POST['ai_biz_manager_settings_nonce'] ) && wp_verify_nonce( $_POST['ai_biz_manager_settings_nonce'], 'ai_biz_manager_save' ) ) {
            update_option( 'ai_biz_manager_openai_key', sanitize_text_field( $_POST['ai_biz_manager_openai_key'] ) );
            update_option( 'ai_biz_manager_api_token', sanitize_text_field( $_POST['ai_biz_manager_api_token'] ) );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $openai_key = esc_attr( get_option( 'ai_biz_manager_openai_key', '' ) );
        $api_token  = esc_attr( get_option( 'ai_biz_manager_api_token', '' ) );
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
                        <td><input name="ai_biz_manager_api_token" id="ai_biz_manager_api_token" type="text" value="<?php echo $api_token; ?>" class="regular-text" /><p class="description">Use this token in your browser extension in header X-AI-BIZ-TOKEN</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
