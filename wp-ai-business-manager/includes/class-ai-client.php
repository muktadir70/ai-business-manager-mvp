<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_AI_Client {
    private $api_key;

    public function __construct( $api_key = '' ) {
        $this->api_key = $api_key ?: get_option( 'ai_biz_manager_openai_key', '' );
    }

    /**
     * Simple example: ask an LLM to rewrite a product description.
     * You should adapt to your chosen provider's API.
     */
    public function rewrite_description( $text ) {
        if ( empty( $this->api_key ) ) {
            return $text;
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $body = array(
            'model' => 'gpt-4o-mini',
            'messages' => array(
                array( 'role' => 'system', 'content' => 'Rewrite this product description to be SEO-friendly and original.' ),
                array( 'role' => 'user', 'content' => $text ),
            ),
            'max_tokens' => 400,
        );

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return $text;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code === 200 && isset( $body['choices'][0]['message']['content'] ) ) {
            return wp_kses_post( $body['choices'][0]['message']['content'] );
        }

        return $text;
    }
}
