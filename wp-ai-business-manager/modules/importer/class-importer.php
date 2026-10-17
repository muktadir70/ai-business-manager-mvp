<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Importer {
    public static function import_permission_check( $request ) {
        // Allow if logged-in user with capability OR correct API token header is provided
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }
        $headers = getallheaders();
        $token = '';
        if ( isset( $headers['X-AI-BIZ-TOKEN'] ) ) {
            $token = $headers['X-AI-BIZ-TOKEN'];
        } elseif ( isset( $headers['x-ai-biz-token'] ) ) {
            $token = $headers['x-ai-biz-token'];
        }
        $stored = get_option( 'ai_biz_manager_api_token', '' );
        return hash_equals( $stored, $token );
    }

    public static function handle_import( $request ) {
        $params = $request->get_json_params();

        $title       = sanitize_text_field( $params['title'] ?? '' );
        $description = wp_kses_post( $params['description'] ?? '' );
        $price       = isset( $params['price'] ) ? floatval( $params['price'] ) : 0;
        $images      = is_array( $params['images'] ?? array() ) ? $params['images'] : array();

        if ( empty( $title ) ) {
            return new WP_Error( 'missing_title', 'Missing product title', array( 'status' => 400 ) );
        }

        // If WooCommerce present, create product
        if ( function_exists( 'wc_get_product' ) ) {
            $post_id = wp_insert_post( array(
                'post_title'   => $title,
                'post_content' => $description,
                'post_status'  => 'draft',
                'post_type'    => 'product',
            ) );

            if ( is_wp_error( $post_id ) ) {
                return new WP_Error( 'create_failed', 'Could not create product', array( 'status' => 500 ) );
            }

            // Set price using WooCommerce CRUD
            if ( class_exists( 'WC_Product' ) ) {
                $product = wc_get_product( $post_id );
                if ( $product ) {
                    $product->set_regular_price( $price );
                    $product->save();
                }
            }

            // Sideload images and set first as featured
            $first_att_id = 0;
            foreach ( $images as $i => $img_url ) {
                $img_id = self::sideload_image( esc_url_raw( $img_url ), $post_id );
                if ( $i === 0 && $img_id ) {
                    set_post_thumbnail( $post_id, $img_id );
                }
            }

            return rest_ensure_response( array( 'success' => true, 'product_id' => $post_id ) );
        } else {
            // For non-WooCommerce site, create a custom post type or post
            $post_id = wp_insert_post( array(
                'post_title'   => $title,
                'post_content' => $description,
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ) );

            return rest_ensure_response( array( 'success' => true, 'post_id' => $post_id ) );
        }
    }

    private static function sideload_image( $url, $post_id ) {
        if ( empty( $url ) ) {
            return 0;
        }
        // Use WP media sideload helper
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            return 0;
        }

        $file = array(
            'name'     => basename( $url ),
            'tmp_name' => $tmp,
        );

        $overrides = array(
            'test_form' => false,
            'mimes'     => null,
        );

        $results = wp_handle_sideload( $file, $overrides );
        if ( ! empty( $results['error'] ) ) {
            @unlink( $tmp );
            return 0;
        }

        $attachment = array(
            'post_mime_type' => $results['type'],
            'post_title'     => sanitize_file_name( $results['file'] ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $results['file'], $post_id );
        if ( ! is_wp_error( $attach_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attach_id, $results['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            return $attach_id;
        }

        return 0;
    }
}
