// Site-specific extractor rules included for Daraz, AliExpress, Taobao.
// The extractor is primarily used by the browser extension content script but kept here
// as a reference for server-side scraping if needed in future.

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Biz_Extractors {
    // Parse common product fields from HTML string using DOMDocument
    public static function parse_html_product( $html, $url ) {
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( $html );
        $xpath = new DOMXPath( $dom );

        $host = parse_url( $url, PHP_URL_HOST );
        $data = array();

        if ( strpos( $host, 'daraz.' ) !== false ) {
            // Daraz generic selectors (may vary by country)
            $title = self::xpath_text( $xpath, "//h1[contains(@class,'pdp-mod-product-badge-title')]|//h1[contains(@class,'pdp-product-title')]" );
            $price = self::xpath_text( $xpath, "//span[contains(@class,'pdp-price')]|//span[contains(@class,'price')]|//span[@data-price]" );
            $img = self::xpath_attr( $xpath, "//div[contains(@class,'pdp-gallery') ]//img[1]", 'src' );
        } elseif ( strpos( $host, 'aliexpress.' ) !== false ) {
            $title = self::xpath_text( $xpath, "//h1[contains(@class,'product-title-text')]|//h1" );
            $price = self::xpath_text( $xpath, "//span[contains(@class,'product-price-value')]|//span[contains(@class,'price')]|//meta[@itemprop='price']/@content" );
            $img = self::xpath_attr( $xpath, "//img[contains(@class,'magnifier-image')]|//img[@id='icImg']", 'src' );
        } elseif ( strpos( $host, 'taobao.' ) !== false || strpos( $host, 'tmall.' ) !== false ) {
            $title = self::xpath_text( $xpath, "//div[contains(@class,'tb-detail-hd')]//h1|//h3[@class='tb-main-title' ]|//meta[@property='og:title']/@content" );
            $price = self::xpath_text( $xpath, "//em[@class='tb-rmb-num']|//meta[@property='product:price:amount']/@content" );
            $img = self::xpath_attr( $xpath, "//img[@id='J_ImgBooth']|//img[contains(@class,'tb-img')]", 'src' );
        } else {
            // Generic: JSON-LD
            $jsonld = self::extract_jsonld( $dom );
            if ( $jsonld ) {
                if ( isset( $jsonld['name'] ) ) $title = $jsonld['name'];
                if ( isset( $jsonld['description'] ) ) $desc = $jsonld['description'];
                if ( isset( $jsonld['image'] ) ) $img = is_array( $jsonld['image'] ) ? $jsonld['image'][0] : $jsonld['image'];
                if ( isset( $jsonld['offers']['price'] ) ) $price = $jsonld['offers']['price'];
            }
        }

        $data['title'] = trim( $title ?? '' );
        $data['price'] = trim( $price ?? '' );
        $data['image'] = $img ?? '';
        $data['description'] = trim( $desc ?? '' );

        return $data;
    }

    private static function xpath_text( $xpath, $query ) {
        $node = $xpath->query( $query );
        if ( $node && $node->length ) {
            return trim( $node->item(0)->textContent );
        }
        return '';
    }

    private static function xpath_attr( $xpath, $query, $attr ) {
        $node = $xpath->query( $query );
        if ( $node && $node->length ) {
            $val = $node->item(0)->getAttribute( $attr );
            return $val;
        }
        return '';
    }

    private static function extract_jsonld( $dom ) {
        $script_tags = $dom->getElementsByTagName('script');
        foreach ( $script_tags as $s ) {
            if ( $s->getAttribute('type') === 'application/ld+json' ) {
                $txt = trim( $s->textContent );
                $j = json_decode( $txt, true );
                if ( $j && ( isset( $j['@type'] ) && ( $j['@type'] === 'Product' || ( is_array( $j['@type'] ) && in_array( 'Product', $j['@type'] ) ) ) ) ) {
                    return $j;
                }
                // If it's an array
                if ( is_array( $j ) ) {
                    foreach ( $j as $item ) {
                        if ( isset( $item['@type'] ) && ( $item['@type'] === 'Product' ) ) return $item;
                    }
                }
            }
        }
        return null;
    }
}
