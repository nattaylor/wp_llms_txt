<?php
/*
Plugin Name: llms_txt
Plugin URI:  https://nattaylor.com/wp_llms_txt/
Description: Exports all pages and posts in Markdown format when /llms.txt is called.
Version:     0.1
Author:      Nat Taylor
Author URI:  https://nattaylor.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

function llms_markdown_add_rewrite_rules() {
    add_rewrite_rule(
        '^llms\.txt$', 
        'index.php?llms_markdown=1', 
        'top'
    );
}
add_action('init', 'llms_markdown_add_rewrite_rules');

add_filter( 'redirect_canonical', 'custom_redirect_canonical', 10, 2 );
function custom_redirect_canonical( $redirect_url, $requested_url ) {
    if( str_ends_with( $requested_url, '/llms.txt' ) ) {
        return untrailingslashit($redirect_url);
    }

    return $redirect_url;
}

function llms_markdown_add_query_vars($vars) {
    $vars[] = 'llms_markdown';
    return $vars;
}
add_filter('query_vars', 'llms_markdown_add_query_vars');

require_once 'Markdownify.php';

function llms_markdown_handle_request() {
    global $wp_query;
    global $post;

    if (isset($wp_query->query_vars['llms_markdown']) && $wp_query->query_vars['llms_markdown'] == 1) {
        $args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        );

        $posts = get_posts($args);
        $name = get_bloginfo('name');
        $tagline = get_bloginfo('description');
        $markdown = "# $name\n\n$tagline\n\n";

        $converter = new Markdownify\Converter;

        $allowed_html = array(
            'h1'         => array(),
            'h2'         => array(),
            'h3'         => array(),
            'strong'     => array(),
            'em'         => array(),
            'ul'         => array(),
            'ol'         => array(),
            'li'         => array(),
            'blockquote' => array(),
            'code'       => array(),
            'p'          => array(
                'class' => array(),
                'style' => array(),
            ),
            'a'          => array(
                'href'   => array(),
                'target' => array(),
                'rel'    => array(),
            ),
            'img'        => array(
                'src' => array(),
                'alt' => array(),
            ),
            'del'        => array(
                'datetime' => array(),
            ),
        );

        foreach ($posts as $post) {
            setup_postdata($post);
            $markdown .= "# " . get_the_title() . "\n\n";
            $markdown .= $converter->parseString(preg_replace("~<!--(.*?)-->~s", "", get_the_content())) . "\n\n";
        }

        wp_reset_postdata();

        header('Content-Type: text/plain');
        echo wp_kses($markdown, $allowed_html);
        exit;
    }
}
add_action('template_redirect', 'llms_markdown_handle_request');


