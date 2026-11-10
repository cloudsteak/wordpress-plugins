<?php
// includes/settings.php

add_action('admin_menu', 'lab_launcher_settings_menu');
function lab_launcher_settings_menu()
{
    add_submenu_page(
        'cloud-lab',
        'Cloud Lab Beállítások',
        'Beállítások',
        'edit_posts',
        'lab-launcher-settings', // ⬅️ Ebből lesz: ?page=lab-launcher-settings
        'lab_launcher_settings_page'
    );
}

function lab_launcher_sanitize_settings($input)
{
    if (!is_array($input)) {
        return lab_launcher_get_settings_option();
    }

    $legacy_auth0_keys = array(
        'auth0_domain',
        'auth0_client_id',
        'auth0_client_secret',
        'auth0_audience',
    );

    $existing = get_option('lab_launcher_settings', array());
    if (!is_array($existing)) {
        $existing = array();
    }

    $merged = array_merge($existing, $input);
    foreach ($legacy_auth0_keys as $key) {
        unset($merged[$key]);
    }

    $allowed_keys = array(
        'backend_url',
        'backend_api_key',
        'azure_login_url',
        'aws_login_url',
        'status_webhook_token',
        'status_refresh_interval',
    );

    $sanitized = array();
    foreach ($allowed_keys as $key) {
        if (!array_key_exists($key, $merged)) {
            continue;
        }

        $value = $merged[$key];
        if ($key === 'status_refresh_interval') {
            $sanitized[$key] = max(5, (int) $value);
            continue;
        }

        $sanitized[$key] = sanitize_text_field((string) $value);
    }

    return $sanitized;
}

function lab_launcher_get_plugin_version()
{
    if (!defined('LAB_LAUNCHER_PLUGIN_FILE') || !is_readable(LAB_LAUNCHER_PLUGIN_FILE)) {
        return '';
    }

    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $data = get_plugin_data(LAB_LAUNCHER_PLUGIN_FILE, false, false);

    return isset($data['Version']) ? (string) $data['Version'] : '';
}

function lab_launcher_get_settings_option()
{
    $options = get_option('lab_launcher_settings', array());

    return is_array($options) ? $options : array();
}

add_action('admin_init', 'lab_launcher_register_settings');
function lab_launcher_register_settings()
{
    register_setting('lab_launcher_settings_group', 'lab_launcher_settings', array(
        'sanitize_callback' => 'lab_launcher_sanitize_settings',
    ));

    add_settings_section('lab_launcher_main_section', '', null, 'lab-launcher-settings');

    add_settings_field('backend_url', 'Backend URL', 'lab_launcher_text_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'backend_url']);
    add_settings_field('backend_api_key', 'Backend API key', 'lab_launcher_text_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'backend_api_key']);
    add_settings_field('azure_login_url', 'Azure login URL', 'lab_launcher_text_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'azure_login_url']);
    add_settings_field('aws_login_url', 'AWS login URL', 'lab_launcher_text_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'aws_login_url']);
    add_settings_field('status_webhook_token', 'Webhook Secret Token', 'lab_launcher_text_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'status_webhook_token']);
    add_settings_field('status_refresh_interval', 'Státusz frissítés (mp-ben)', 'lab_launcher_number_field', 'lab-launcher-settings', 'lab_launcher_main_section', ['name' => 'status_refresh_interval']);

}

function lab_launcher_text_field($args)
{
    $options = lab_launcher_get_settings_option();
    $name = $args['name'];
    $value = esc_attr($options[$name] ?? '');
    $type = $name === 'backend_api_key' ? 'password' : 'text';
    echo "<input type='$type' name='lab_launcher_settings[$name]' value='$value' class='regular-text' />";
}

function lab_launcher_number_field($args)
{
    $options = lab_launcher_get_settings_option();
    $name = $args['name'];
    $value = esc_attr($options[$name] ?? '30');
    echo "<input type='number' min='5' step='5' name='lab_launcher_settings[$name]' value='$value' class='small-text' /> másodperc";
}



function lab_launcher_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>Cloud Lab Beállítások</h1>';
    $version = lab_launcher_get_plugin_version();
    if ($version !== '') {
        echo '<p class="description">Plugin verzió: ' . esc_html($version);
        if (defined('LAB_LAUNCHER_PLUGIN_FILE')) {
            echo ' · Telepítési útvonal: <code>' . esc_html(plugin_basename(LAB_LAUNCHER_PLUGIN_FILE)) . '</code>';
        }
        echo '</p>';
    }
    echo '<form method="post" action="options.php">';
    settings_fields('lab_launcher_settings_group');
    do_settings_sections('lab-launcher-settings');
    submit_button();
    echo '</form>';
    echo '</div>';
}
