<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Price {
    // Calculate selling price from source cost and markup percentage (e.g., 40 -> +40%)
    public static function calc_price( $cost, $markup_percent = null ) {
        $cost = floatval( $cost );
        if ( is_null( $markup_percent ) ) {
            $markup_percent = floatval( get_option( 'ai_biz_manager_price_margin', 40 ) );
        }
        $mult = 1 + ( floatval( $markup_percent ) / 100.0 );
        return round( $cost * $mult, 2 );
    }
}
