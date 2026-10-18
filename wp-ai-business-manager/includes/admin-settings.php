<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI Biz Manager - Admin settings page
 * Includes toggles and configuration for importer, AI, SEO, SEM, SMM, Messenger, WhatsApp, Desktop Agent, and more.
 */
class AI_Biz_Manager_Admin {
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'ai-biz-manager' ) );
        }

        // Handle form save
        if ( isset( $_POST['ai_biz_manager_settings_nonce'] ) && wp_verify_nonce( $_POST['ai_biz_manager_settings_nonce'], 'ai_biz_manager_save' ) ) {
            // Basic keys
            update_option( 'ai_biz_manager_openai_key', sanitize_text_field( $_POST['ai_biz_manager_openai_key'] ?? '' ) );
            update_option( 'ai_biz_manager_api_token', sanitize_text_field( $_POST['ai_biz_manager_api_token'] ?? '' ) );

            // Site list
            $sites_raw = isset( $_POST['ai_biz_manager_sites'] ) ? wp_unslash( $_POST['ai_biz_manager_sites'] ) : '';
            $lines = array_filter( array_map( 'trim', explode( "\n", $sites_raw ) ) );
            $sites = array();
            foreach ( $lines as $line ) {
                if ( empty( $line ) ) continue;
                $u = esc_url_raw( trim( $line ) );
                if ( strpos( $u, 'http' ) !== 0 ) {
                    $u = 'https://' . ltrim( $u, '/' );
                }
                $sites[] = rtrim( $u, '/' );
            }
            update_option( 'ai_biz_manager_sites', $sites );

            // Features toggles
            update_option( 'ai_biz_enable_importer', isset( $_POST['ai_biz_enable_importer'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_ai_rewrite', isset( $_POST['ai_biz_enable_ai_rewrite'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_seo', isset( $_POST['ai_biz_enable_seo'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_sem', isset( $_POST['ai_biz_enable_sem'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_smm', isset( $_POST['ai_biz_enable_smm'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_messenger', isset( $_POST['ai_biz_enable_messenger'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_whatsapp', isset( $_POST['ai_biz_enable_whatsapp'] ) ? 1 : 0 );
            update_option( 'ai_biz_enable_desktop_agent', isset( $_POST['ai_biz_enable_desktop_agent'] ) ? 1 : 0 );

            // Import / publish defaults
            update_option( 'ai_biz_default_publish_status', sanitize_text_field( $_POST['ai_biz_default_publish_status'] ?? 'draft' ) );
            update_option( 'ai_biz_default_product_category', sanitize_text_field( $_POST['ai_biz_default_product_category'] ?? '' ) );
            update_option( 'ai_biz_price_margin', floatval( $_POST['ai_biz_price_margin'] ?? 40 ) );
            update_option( 'ai_biz_image_sideload', isset( $_POST['ai_biz_image_sideload'] ) ? 1 : 0 );
            update_option( 'ai_biz_auto_publish', isset( $_POST['ai_biz_auto_publish'] ) ? 1 : 0 );
            update_option( 'ai_biz_auto_post_groups', isset( $_POST['ai_biz_auto_post_groups'] ) ? 1 : 0 );

            // Social / OAuth app settings (stored in options; recommend using secrets for production)
            update_option( 'ai_biz_facebook_app_id', sanitize_text_field( $_POST['ai_biz_facebook_app_id'] ?? '' ) );
            update_option( 'ai_biz_facebook_app_secret', sanitize_text_field( $_POST['ai_biz_facebook_app_secret'] ?? '' ) );
            update_option( 'ai_biz_x_client_id', sanitize_text_field( $_POST['ai_biz_x_client_id'] ?? '' ) );
            update_option( 'ai_biz_x_client_secret', sanitize_text_field( $_POST['ai_biz_x_client_secret'] ?? '' ) );

            // Messenger & WhatsApp
            update_option( 'ai_biz_messenger_verify_token', sanitize_text_field( $_POST['ai_biz_messenger_verify_token'] ?? '' ) );
            update_option( 'ai_biz_whatsapp_business_number', sanitize_text_field( $_POST['ai_biz_whatsapp_business_number'] ?? '' ) );

            // AI provider selection
            update_option( 'ai_biz_ai_provider', sanitize_text_field( $_POST['ai_biz_ai_provider'] ?? 'openai' ) );

            // SEO/SEM/SMM defaults
            update_option( 'ai_biz_seo_default_meta_template', sanitize_text_field( $_POST['ai_biz_seo_default_meta_template'] ?? '' ) );
            update_option( 'ai_biz_sem_default_budget', sanitize_text_field( $_POST['ai_biz_sem_default_budget'] ?? '' ) );
            update_option( 'ai_biz_smm_default_caption_template', sanitize_text_field( $_POST['ai_biz_smm_default_caption_template'] ?? '' ) );

            // Advanced
            update_option( 'ai_biz_proxy', sanitize_text_field( $_POST['ai_biz_proxy'] ?? '' ) );
            update_option( 'ai_biz_encrypt_tokens', isset( $_POST['ai_biz_encrypt_tokens'] ) ? 1 : 0 );
            update_option( 'ai_biz_logging_level', sanitize_text_field( $_POST['ai_biz_logging_level'] ?? 'info' ) );

            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // Load current values
        $openai_key = esc_attr( get_option( 'ai_biz_manager_openai_key', '' ) );
        $api_token  = esc_attr( get_option( 'ai_biz_manager_api_token', '' ) );
        $sites = get_option( 'ai_biz_manager_sites', array() );
        $sites_text = implode( "\n", $sites );

        $enable_importer = get_option( 'ai_biz_enable_importer', 1 );
        $enable_ai_rewrite = get_option( 'ai_biz_enable_ai_rewrite', 1 );
        $enable_seo = get_option( 'ai_biz_enable_seo', 1 );
        $enable_sem = get_option( 'ai_biz_enable_sem', 0 );
        $enable_smm = get_option( 'ai_biz_enable_smm', 0 );
        $enable_messenger = get_option( 'ai_biz_enable_messenger', 0 );
        $enable_whatsapp = get_option( 'ai_biz_enable_whatsapp', 0 );
        $enable_desktop_agent = get_option( 'ai_biz_enable_desktop_agent', 0 );

        $default_publish_status = esc_attr( get_option( 'ai_biz_default_publish_status', 'draft' ) );
        $default_product_category = esc_attr( get_option( 'ai_biz_default_product_category', '' ) );
        $price_margin = esc_attr( get_option( 'ai_biz_price_margin', 40 ) );
        $image_sideload = get_option( 'ai_biz_image_sideload', 1 );
        $auto_publish = get_option( 'ai_biz_auto_publish', 0 );
        $auto_post_groups = get_option( 'ai_biz_auto_post_groups', 0 );

        $fb_app_id = esc_attr( get_option( 'ai_biz_facebook_app_id', '' ) );
        $fb_app_secret = esc_attr( get_option( 'ai_biz_facebook_app_secret', '' ) );
        $x_client_id = esc_attr( get_option( 'ai_biz_x_client_id', '' ) );
        $x_client_secret = esc_attr( get_option( 'ai_biz_x_client_secret', '' ) );

        $messenger_verify_token = esc_attr( get_option( 'ai_biz_messenger_verify_token', '' ) );
        $whatsapp_business_number = esc_attr( get_option( 'ai_biz_whatsapp_business_number', '' ) );

        $ai_provider = esc_attr( get_option( 'ai_biz_ai_provider', 'openai' ) );

        $seo_template = esc_attr( get_option( 'ai_biz_seo_default_meta_template', '' ) );
        $sem_budget = esc_attr( get_option( 'ai_biz_sem_default_budget', '' ) );
        $smm_caption = esc_attr( get_option( 'ai_biz_smm_default_caption_template', '' ) );

        $proxy = esc_attr( get_option( 'ai_biz_proxy', '' ) );
        $encrypt_tokens = get_option( 'ai_biz_encrypt_tokens', 0 );
        $logging_level = esc_attr( get_option( 'ai_biz_logging_level', 'info' ) );

        // Enqueue admin JS for copy buttons
        $script_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/settings.js';
        wp_enqueue_script( 'ai-biz-manager-settings', $script_url, array(), '0.2', true );

        ?>
        <div class="wrap">
            <h1>AI Business Manager — Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'ai_biz_manager_save', 'ai_biz_manager_settings_nonce' ); ?>

                <h2>General</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_openai_key">OpenAI API Key</label></th>
                        <td><input name="ai_biz_manager_openai_key" id="ai_biz_manager_openai_key" type="text" value="<?php echo $openai_key; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_api_token">Browser API Token</label></th>
                        <td><input name="ai_biz_manager_api_token" id="ai_biz_manager_api_token" type="text" value="<?php echo $api_token; ?>" class="regular-text" /><p class="description">Use this token in the browser extension as X-AI-BIZ-TOKEN or give to desktop agent.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_manager_sites">Site base URLs (one per line)</label></th>
                        <td>
                            <textarea name="ai_biz_manager_sites" id="ai_biz_manager_sites" rows="4" class="large-text code"><?php echo esc_textarea( $sites_text ); ?></textarea>
                            <p class="description">Add one or more WordPress site URLs where this plugin will run (used to generate redirect URIs).</p>
                        </td>
                    </tr>
                </table>

                <h2>Features</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Importer</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_importer" <?php checked( $enable_importer, 1 ); ?> /> Enable product importer (browser extension / desktop agent)</label></td>
                    </tr>
                    <tr>
                        <th scope="row">AI Rewrite</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_ai_rewrite" <?php checked( $enable_ai_rewrite, 1 ); ?> /> Enable AI rewriting for descriptions & SEO</label></td>
                    </tr>
                    <tr>
                        <th scope="row">SEO Automation</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_seo" <?php checked( $enable_seo, 1 ); ?> /> Enable SEO suggestions and auto-meta generation</label></td>
                    </tr>
                    <tr>
                        <th scope="row">SEM / Ads</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_sem" <?php checked( $enable_sem, 1 ); ?> /> Enable SEM (Google Ads) integration placeholders</label></td>
                    </tr>
                    <tr>
                        <th scope="row">SMM / Social</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_smm" <?php checked( $enable_smm, 1 ); ?> /> Enable Social posting features (Facebook, X, LinkedIn, Instagram)</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Messenger Automation</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_messenger" <?php checked( $enable_messenger, 1 ); ?> /> Enable Facebook Messenger webhook handling</label></td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Business</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_whatsapp" <?php checked( $enable_whatsapp, 1 ); ?> /> Enable WhatsApp Business placeholders (API required)</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Desktop Agent</th>
                        <td><label><input type="checkbox" name="ai_biz_enable_desktop_agent" <?php checked( $enable_desktop_agent, 1 ); ?> /> Enable Desktop Agent features (local agent must be installed)</label></td>
                    </tr>
                </table>

                <h2>Import & Publish Defaults</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_default_publish_status">Default publish status</label></th>
                        <td>
                            <select name="ai_biz_default_publish_status" id="ai_biz_default_publish_status">
                                <option value="draft" <?php selected( $default_publish_status, 'draft' ); ?>>Draft</option>
                                <option value="publish" <?php selected( $default_publish_status, 'publish' ); ?>>Publish</option>
                                <option value="pending" <?php selected( $default_publish_status, 'pending' ); ?>>Pending Review</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_default_product_category">Default product category (slug)</label></th>
                        <td><input name="ai_biz_default_product_category" id="ai_biz_default_product_category" type="text" value="<?php echo $default_product_category; ?>" class="regular-text" /><p class="description">Optional default category slug for imported products.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_price_margin">Default price markup (%)</label></th>
                        <td><input name="ai_biz_price_margin" id="ai_biz_price_margin" type="number" step="0.1" value="<?php echo $price_margin; ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Image sideloading</th>
                        <td><label><input type="checkbox" name="ai_biz_image_sideload" <?php checked( $image_sideload, 1 ); ?> /> Sideload product images into WP media library</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Auto publish</th>
                        <td><label><input type="checkbox" name="ai_biz_auto_publish" <?php checked( $auto_publish, 1 ); ?> /> Automatically publish imported products (use with caution)</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Auto post to Facebook Groups</th>
                        <td><label><input type="checkbox" name="ai_biz_auto_post_groups" <?php checked( $auto_post_groups, 1 ); ?> /> Attempt to post to Facebook groups (requires group admin rights and app review)</label></td>
                    </tr>
                </table>

                <h2>Social / OAuth App Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_facebook_app_id">Facebook App ID</label></th>
                        <td><input name="ai_biz_facebook_app_id" id="ai_biz_facebook_app_id" type="text" value="<?php echo $fb_app_id; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_facebook_app_secret">Facebook App Secret</label></th>
                        <td><input name="ai_biz_facebook_app_secret" id="ai_biz_facebook_app_secret" type="password" value="<?php echo $fb_app_secret; ?>" class="regular-text" /><p class="description">For production store these secrets in GitHub Secrets or a Vault.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_x_client_id">X Client ID</label></th>
                        <td><input name="ai_biz_x_client_id" id="ai_biz_x_client_id" type="text" value="<?php echo $x_client_id; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_x_client_secret">X Client Secret</label></th>
                        <td><input name="ai_biz_x_client_secret" id="ai_biz_x_client_secret" type="password" value="<?php echo $x_client_secret; ?>" class="regular-text" /><p class="description">For production store these secrets in GitHub Secrets or a Vault.</p></td>
                    </tr>
                </table>

                <h2>Messenger & WhatsApp</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_messenger_verify_token">Messenger verify token</label></th>
                        <td><input name="ai_biz_messenger_verify_token" id="ai_biz_messenger_verify_token" type="text" value="<?php echo $messenger_verify_token; ?>" class="regular-text" /><p class="description">Used to verify Facebook webhook setup (store this secret safely).</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_whatsapp_business_number">WhatsApp Business number</label></th>
                        <td><input name="ai_biz_whatsapp_business_number" id="ai_biz_whatsapp_business_number" type="text" value="<?php echo $whatsapp_business_number; ?>" class="regular-text" /><p class="description">Optional: required for WhatsApp Business API integration.</p></td>
                    </tr>
                </table>

                <h2>SEO / SEM / SMM Defaults</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_seo_default_meta_template">SEO meta template</label></th>
                        <td><input name="ai_biz_seo_default_meta_template" id="ai_biz_seo_default_meta_template" type="text" value="<?php echo $seo_template; ?>" class="regular-text" /><p class="description">Use variables like {title}, {brand}, {price} in templates.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_sem_default_budget">SEM default daily budget</label></th>
                        <td><input name="ai_biz_sem_default_budget" id="ai_biz_sem_default_budget" type="text" value="<?php echo $sem_budget; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_smm_default_caption_template">SMM caption template</label></th>
                        <td><input name="ai_biz_smm_default_caption_template" id="ai_biz_smm_default_caption_template" type="text" value="<?php echo $smm_caption; ?>" class="regular-text" /><p class="description">Template for social post captions (variables supported).</p></td>
                    </tr>
                </table>

                <h2>Advanced / Debug</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ai_biz_proxy">Proxy (optional)</label></th>
                        <td><input name="ai_biz_proxy" id="ai_biz_proxy" type="text" value="<?php echo $proxy; ?>" class="regular-text" /><p class="description">Proxy URL for server-side requests (use if scraping protected sites).</p></td>
                    </tr>
                    <tr>
                        <th scope="row">Encrypt stored tokens</th>
                        <td><label><input type="checkbox" name="ai_biz_encrypt_tokens" <?php checked( $encrypt_tokens, 1 ); ?> /> Encrypt OAuth tokens in the DB (simple option; recommend server-side encryption in production)</label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ai_biz_logging_level">Logging level</label></th>
                        <td>
                            <select name="ai_biz_logging_level" id="ai_biz_logging_level">
                                <option value="error" <?php selected( $logging_level, 'error' ); ?>>Error</option>
                                <option value="warning" <?php selected( $logging_level, 'warning' ); ?>>Warning</option>
                                <option value="info" <?php selected( $logging_level, 'info' ); ?>>Info</option>
                                <option value="debug" <?php selected( $logging_level, 'debug' ); ?>>Debug</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2>OAuth Redirect URIs (copy & add to your dev apps)</h2>
            <p>For each site listed above, add these redirect URIs to your Facebook and X developer app settings:</p>
            <div id="ai-biz-redirect-list">
                <?php if ( empty( $sites ) ) : ?>
                    <p><em>No sites configured yet. Add your site URLs above and save to see redirect URIs here.</em></p>
                <?php else : ?>
                    <table class="widefat fixed" cellspacing="0">
                        <thead><tr><th>Site</th><th>Provider</th><th>Redirect URI</th><th>Copy</th></tr></thead>
                        <tbody>
                        <?php foreach ( $sites as $s ) :
                            $fb = esc_url( $s . '/wp-admin/admin.php?page=ai-biz-manager&oauth_provider=facebook' );
                            $x  = esc_url( $s . '/wp-admin/admin.php?page=ai-biz-manager&oauth_provider=x' );
                        ?>
                        <tr>
                            <td rowspan="2"><?php echo esc_html( $s ); ?></td>
                            <td>Facebook</td>
                            <td class="ai-redirect-uri"><?php echo esc_html( $fb ); ?></td>
                            <td><button class="button ai-copy-btn" data-value="<?php echo esc_attr( $fb ); ?>">Copy</button></td>
                        </tr>
                        <tr>
                            <td>X (Twitter)</td>
                            <td class="ai-redirect-uri"><?php echo esc_html( $x ); ?></td>
                            <td><button class="button ai-copy-btn" data-value="<?php echo esc_attr( $x ); ?>">Copy</button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <h2>Connect social accounts</h2>
            <?php
                $facebook_auth_url = AI_Biz_Social_OAuth::get_authorize_url( 'facebook', admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=facebook' ) );
                $x_auth_url = AI_Biz_Social_OAuth::get_authorize_url( 'x', admin_url( 'admin.php?page=ai-biz-manager&oauth_provider=x' ) );
            ?>
            <p><a class="button button-primary" href="<?php echo esc_url( $facebook_auth_url ); ?>">Connect Facebook</a></p>
            <p><a class="button button-secondary" href="<?php echo esc_url( $x_auth_url ); ?>">Connect X</a></p>

        </div>
        <?php
    }
}
