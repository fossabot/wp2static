<?php

namespace WP2Static;

use Exception;

class ConvertToDocumentRelativeURL {
    /**
     * Convert absolute URL to document-relative.
     * Required for offline URLs
     */
    public static function convert(
        string $url,
        string $page_url,
        string $site_url,
        bool $offline_mode = false
    ) : string {
        $current_page_path_to_root = '';
        $current_page_path = parse_url( $page_url, PHP_URL_PATH );

        if ( ! $current_page_path ) {
            return $url;
        }

        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        $page_url_without_domain = str_replace(
            $site_url,
            '',
            $page_url
        );

        if ( $page_url_without_domain === '' ) {
            $err = 'Warning: empty $page_url_without_domain encountered ' .
                "url: {$url} \\n page_url: $page_url \\n " .
                "site_url: {$site_url} \\n offline mode: $offline_mode";
            WsLog::l( $err );

            return $url;
        }

        /*
            For target URLs at the same level or higher level as the current
            page, strip the current page from the target URL

            Match current page in target URL to determine
        */
        if (
            // when homepage of site, page url without domain will be empty
            $page_url_without_domain &&
            strpos( $url, $page_url_without_domain ) !== false
        ) {
            $rewritten_url = str_replace(
                $page_url_without_domain,
                '',
                $url
            );

            // TODO: into one array or match/replaces
            $rewritten_url = str_replace(
                $site_url,
                '',
                $rewritten_url
            );

            $offline_url = $rewritten_url;
        } else {
            /*
                For target URLs not below the current page's hierarchy
                build the document relative path from current page
            */
            for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
                $current_page_path_to_root .= '../';
            }

            $rewritten_url = str_replace(
                $site_url,
                '',
                $url
            );

            $offline_url = $current_page_path_to_root . $rewritten_url;

            /*
                Cover case of root relative URLs incorrectly ending as
                ..//some/path by replacing double slashes with /../
            */
            $offline_url = str_replace(
                '..//',
                '../../',
                $offline_url
            );
        }

        /*
            We must address the case where the WP site uses a URL such as
            `/some-page`, which is valid and will work outside offline
            use cases.

            For offline usage, we need to force any detected HTML content paths
            to have a trailing slash, allowing for easily appending `index.html`
            for proper offline usage compatibility.

            We can risk using file path detection here, as images and other
            assets will also need to be explcitly named for offline usage and
            should be handled elsewhere in the case they are being served
            without an extension.

            Here, we will detect for any URLs without a `.` in the last segment,
            append /index.html and strip and duplicate slashes

            /           => //index.html             => /index.html
            /some-post  => /some-post/index.html
            /some-post/ => /some-post//index.html   => /some-post/index.html
            /an-img.jpg # no match

        */
        if ( is_string( $offline_url ) && $offline_mode ) {
            // if last char is a ., we're linking to a dir path, add index.html
            $last_char_is_slash = substr( $offline_url, -1 ) == '/';

            $basename_doesnt_contain_dot =
                strpos( basename( $offline_url ), '.' ) === false;

            if ( $last_char_is_slash || $basename_doesnt_contain_dot ) {
                $offline_url .= '/index.html';
                $offline_url = str_replace( '//', '/', $offline_url );
            }
        }

        return $offline_url;
    }
}
