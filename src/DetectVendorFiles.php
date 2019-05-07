<?php

namespace WP2Static;

class DetectVendorFiles {

    public static function detect( $wp_site_url ) {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $vendor_files = array();

        /*
            This is less needed if bulk detecting via DetectPluginAssets
            should be moved into it's own Elementor Add-on, to seamlessly
            handle forms, search and anything else to make seamless Elementor
            to static workflow
        */
        if ( class_exists( '\\Elementor\Api' ) ) {
            $elementor_font_dir = $site_info['plugins_path'] .
                '/elementor/assets/lib/font-awesome';

            $elementor_urls = FilesHelper::getListOfLocalFilesByUrl(
                $elementor_font_dir
            );

            $vendor_files = array_merge( $vendor_files, $elementor_urls );
        }

        if ( defined( 'WPSEO_VERSION' ) ) {
            $yoast_sitemaps = array(
                '/sitemap_index.xml',
                '/post-sitemap.xml',
                '/page-sitemap.xml',
                '/category-sitemap.xml',
                '/author-sitemap.xml',
            );

            $vendor_files = array_merge( $vendor_files, $yoast_sitemaps );
        }

        if ( is_dir( $site_info['plugins_path'] . '/soliloquy/' ) ) {
            $soliloquy_assets = $site_info['plugins_path'] .
                '/soliloquy/assets/css/images/';

            $soliloquy_urls = FilesHelper::getListOfLocalFilesByUrl(
                $soliloquy_assets
            );

            $vendor_files = array_merge( $vendor_files, $soliloquy_urls );
        }

        // cache dir used by Autoptimize and other themes/plugins
        $vendor_cache_dir =
            $site_info['content_path'] . '/cache/';

        if ( is_dir( $vendor_cache_dir ) ) {

            // get difference between home and wp-contents URL
            $prefix = str_replace(
                $site_info['site_url'],
                '/',
                $site_info['content_url']
            );

            $vendor_cache_urls = DetectVendorCache::detect(
                $vendor_cache_dir,
                $site_info['content_path'],
                $prefix
            );

            $vendor_files = array_merge( $vendor_files, $vendor_cache_urls );
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
            global $wpdb;

            $query = "
                SELECT meta_value
                FROM %s
                WHERE meta_key = '%s'
                ";

            $custom_permalinks = array();

            $posts = $wpdb->get_results(
                sprintf(
                    $query,
                    $wpdb->postmeta,
                    'custom_permalink'
                )
            );

            if ( $posts ) {
                foreach ( $posts as $post ) {
                    $custom_permalinks[] = $wp_site_url . $post->meta_value;
                }

                $vendor_files = array_merge(
                    $vendor_files,
                    $custom_permalinks
                );
            }
        }

        if ( class_exists( 'molongui_authorship' ) ) {
            $molongui_path = $wp_site->plugins_path . '/molongui-authorship';

            $molongui_urls = FilesHelper::getListOfLocalFilesByUrl(
                $molongui_path
            );

            $vendor_files = array_merge( $vendor_files, $molongui_urls );
        }

        return $vendor_files;
    }
}
