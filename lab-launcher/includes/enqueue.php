<?php
// includes/enqueue.php

add_action('wp_enqueue_scripts', 'lab_launcher_enqueue_assets');
add_action('wp_enqueue_scripts', 'lab_launcher_enqueue_code_copy_style', 100);
add_action('admin_enqueue_scripts', 'lab_launcher_enqueue_code_copy_style', 100);
add_action('admin_enqueue_scripts', 'lab_launcher_enqueue_admin_assets');

function lab_launcher_enqueue_assets()
{
    $plugin_url = plugin_dir_url(dirname(__FILE__));

    wp_enqueue_style(
        'lab-launcher-style',
        $plugin_url . 'includes/lab-launcher.css',
        [],
        '1.3'
    );

    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css'
    );
}

function lab_launcher_enqueue_code_copy_style()
{
    $plugin_url = plugin_dir_url(dirname(__FILE__));

    wp_enqueue_style(
        'lab-code-copy',
        $plugin_url . 'includes/lab-code-copy.css',
        ['lab-launcher-style'],
        '1.0'
    );
}

function lab_launcher_enqueue_admin_assets($hook)
{
    lab_launcher_enqueue_assets();

    if ($hook !== 'cloud-lab_page_lab-launcher-labs') {
        return;
    }

    $plugin_url = plugin_dir_url(dirname(__FILE__));

    wp_enqueue_style(
        'lab-markdown-editor',
        $plugin_url . 'admin/lab-markdown-editor.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'lab-markdown-editor',
        $plugin_url . 'admin/lab-markdown-editor.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_enqueue_media();
}
