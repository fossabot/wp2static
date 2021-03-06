<?php

namespace WP2Static;

use DOMNode;
use DOMDocument;

class CDATAProcessor {

    /**
     *  Process CDATA element
     *
     *  @param mixed[] $rewrite_rules url rewriting patterns
     */
    public function processCDATA(
        DOMNode $node,
        DOMDocument $xml_doc,
        array $rewrite_rules
    ) : void {
        $node_text = $node->textContent;

        $node_text = str_replace(
            $rewrite_rules['site_url_patterns'],
            $rewrite_rules['destination_url_patterns'],
            $node_text
        );

        $new_node =
            $xml_doc->createTextNode( $node_text );

        // replace old node with new
        $node->parentNode->replaceChild( $new_node, $node );
    }
}

