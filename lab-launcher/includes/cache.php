<?php

/**
 * Lab oldalak cache-kezelése: ne cache-elődjenek, mentéskor purge.
 */

function lab_launcher_lab_shortcodes()
{
    return ['lab_launcher', 'lab_start', 'lab_training'];
}

function lab_launcher_is_rest_request()
{
    if (function_exists('wp_doing_rest')) {
        return wp_doing_rest();
    }

    return defined('REST_REQUEST') && REST_REQUEST;
}

function lab_launcher_safe_cache_call($callback)
{
    try {
        $callback();
    } catch (Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Lab Launcher cache: ' . $e->getMessage());
        }
    }
}

function lab_launcher_post_has_lab_shortcode($post)
{
    if (!$post instanceof WP_Post) {
        return false;
    }

    foreach (lab_launcher_lab_shortcodes() as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            return true;
        }
    }

    return false;
}

function lab_launcher_is_lab_content_page($post = null)
{
    if ($post === null) {
        if (!is_singular()) {
            return false;
        }

        global $post;
    }

    return lab_launcher_post_has_lab_shortcode($post);
}

function lab_launcher_disable_page_cache()
{
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    if (!defined('DONOTCACHEDB')) {
        define('DONOTCACHEDB', true);
    }

    if (!defined('DONOTMINIFY')) {
        define('DONOTMINIFY', true);
    }

    if (!defined('DONOTCDN')) {
        define('DONOTCDN', true);
    }

    lab_launcher_safe_cache_call(function () {
        do_action('litespeed_control_set_nocache', 'evolvia lab launcher');
    });

    add_filter('rocket_override_donotcachepage', '__return_true');

    if (function_exists('sg_cachepress_bypass_cache')) {
        lab_launcher_safe_cache_call(function () {
            sg_cachepress_bypass_cache();
        });
    }
}

function lab_launcher_send_nocache_headers()
{
    if (!lab_launcher_is_lab_content_page()) {
        return;
    }

    lab_launcher_disable_page_cache();

    if (!headers_sent()) {
        nocache_headers();
    }
}

function lab_launcher_maybe_disable_page_cache()
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || lab_launcher_is_rest_request()) {
        return;
    }

    if (!lab_launcher_is_lab_content_page()) {
        return;
    }

    lab_launcher_disable_page_cache();
}

function lab_launcher_get_pages_with_lab_shortcodes()
{
    global $wpdb;

    $like_clauses = [];
    foreach (lab_launcher_lab_shortcodes() as $shortcode) {
        $like_clauses[] = $wpdb->prepare('post_content LIKE %s', '%[' . $shortcode . '%');
    }

    if (empty($like_clauses)) {
        return [];
    }

    $sql = "
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_status = 'publish'
          AND post_type IN ('page', 'post')
          AND (" . implode(' OR ', $like_clauses) . ')
    ';

    $post_ids = $wpdb->get_col($sql);
    if (empty($post_ids)) {
        return [];
    }

    $validated = [];
    foreach ($post_ids as $post_id) {
        $post = get_post((int) $post_id);
        if ($post && lab_launcher_post_has_lab_shortcode($post)) {
            $validated[] = (int) $post_id;
        }
    }

    return array_values(array_unique($validated));
}

function lab_launcher_purge_post_cache($post_id)
{
    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return;
    }

    clean_post_cache($post_id);

    if (function_exists('rocket_clean_post')) {
        lab_launcher_safe_cache_call(function () use ($post_id) {
            rocket_clean_post($post_id);
        });
    }

    if (function_exists('w3tc_flush_post')) {
        lab_launcher_safe_cache_call(function () use ($post_id) {
            w3tc_flush_post($post_id);
        });
    }

    lab_launcher_safe_cache_call(function () use ($post_id) {
        do_action('litespeed_purge_post', $post_id);
    });

    if (function_exists('wp_cache_post_change')) {
        lab_launcher_safe_cache_call(function () use ($post_id) {
            wp_cache_post_change($post_id);
        });
    }

    if (
        class_exists('SiteGround_Optimizer\Supercacher\Supercacher')
        && method_exists('SiteGround_Optimizer\Supercacher\Supercacher', 'purge_cache_request')
    ) {
        lab_launcher_safe_cache_call(function () use ($post_id) {
            $url = get_permalink($post_id);
            if (!is_string($url) || $url === '') {
                return;
            }

            \SiteGround_Optimizer\Supercacher\Supercacher::purge_cache_request($url);
        });
    }
}

function lab_launcher_purge_lab_page_cache()
{
    lab_launcher_safe_cache_call(function () {
        $page_ids = lab_launcher_get_pages_with_lab_shortcodes();

        foreach ($page_ids as $post_id) {
            lab_launcher_purge_post_cache($post_id);
        }
    });
}

function lab_launcher_register_cache_hooks()
{
    add_action('wp', 'lab_launcher_maybe_disable_page_cache', 1);
    add_action('template_redirect', 'lab_launcher_send_nocache_headers', 0);
    add_action('update_option_lab_launcher_labs', 'lab_launcher_purge_lab_page_cache');
}

add_action('init', 'lab_launcher_register_cache_hooks', 1);
