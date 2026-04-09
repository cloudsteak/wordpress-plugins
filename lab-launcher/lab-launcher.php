<?php
/**
 * Plugin Name: Evolvia Lab Launcher (CloudMentor)
 * Plugin URI: https://github.com/the1bit/student-lab-backend/tree/main/wordpress/lab-launcher
 * Description: WordPress plugin a Evolvia Lab indításhoz (Azure, AWS).
 * Version: 1.1.6
 * Author: CloudMentor
 * Author URI: https://cloudmentor.hu
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires at least: 6.2
 * Tested up to: 6.7.2
 * Requires PHP: 8.0
 * Text Domain: evolvia-lab-launcher
 * Domain Path: /languages
 */


// Enqueue fájl közvetlenül a fájl elején fusson
require_once plugin_dir_path(__FILE__) . 'includes/enqueue.php';


// Beillesztés: admin oldal, REST API, shortcode, beállítások

// 1. Plugin alap inicializálás
add_action('plugins_loaded', 'lab_launcher_init');
function lab_launcher_init()
{

    require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
    require_once plugin_dir_path(__FILE__) . 'includes/api-caller.php';
    require_once plugin_dir_path(__FILE__) . 'admin/lab-admin-page.php';
    require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
    require_once plugin_dir_path(__FILE__) . 'admin/courses-page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/lab-launch-shortcode.php';
    require_once plugin_dir_path(__FILE__) . 'admin/user-statuses-page.php';



}

add_action('rest_api_init', function () {
    register_rest_route('lab-launcher/v1', '/start-lab', array(
        'methods' => 'POST',
        'callback' => 'lab_launcher_start_lab_rest',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('lab-launcher/v1', '/verify-lab', array(
        'methods' => 'POST',
        'callback' => 'lab_check_lab_rest',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('lab-launcher/v1', '/lab-status-update', [
        'methods' => 'POST',
        'callback' => 'lab_launcher_status_update',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('lab-launcher/v1', '/lab-status-webhook', [
        'methods' => 'POST',
        'callback' => 'lab_launcher_status_webhook',
        'permission_callback' => '__return_true'
    ]);
});

function lab_launcher_get_status_meta()
{
    $meta = get_option('lab_launcher_status_meta', []);

    return is_array($meta) ? $meta : [];
}

function lab_launcher_get_status_meta_for_lab($email, $lab_id)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);

    if (!$email || !$lab_id) {
        return [];
    }

    $meta = lab_launcher_get_status_meta();
    $key = "{$email}|{$lab_id}";

    return (isset($meta[$key]) && is_array($meta[$key])) ? $meta[$key] : [];
}

function lab_launcher_get_status_value($email, $lab_id)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);

    if (!$email || !$lab_id) {
        return 'unknown';
    }

    $statuses = get_option('lab_launcher_statuses', []);

    return $statuses["{$email}|{$lab_id}"] ?? 'unknown';
}

function lab_launcher_set_status_value($email, $lab_id, $status)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);
    $status = sanitize_text_field($status);

    if (!$email || !$lab_id || !$status) {
        return;
    }

    $statuses = get_option('lab_launcher_statuses', []);
    $statuses["{$email}|{$lab_id}"] = $status;
    update_option('lab_launcher_statuses', $statuses);
}

function lab_launcher_set_status_started_at($email, $lab_id, $lab_ttl = 5400)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);
    $lab_ttl = intval($lab_ttl);

    if (!$email || !$lab_id) {
        return;
    }

    $meta = lab_launcher_get_status_meta();
    $key = "{$email}|{$lab_id}";

    if (!isset($meta[$key]) || !is_array($meta[$key])) {
        $meta[$key] = [];
    }

    $meta[$key]['started_at'] = current_time('mysql');
    $meta[$key]['lab_ttl'] = $lab_ttl > 0 ? $lab_ttl : 5400;
    unset($meta[$key]['completed_at']);
    unset($meta[$key]['ready_at']);

    update_option('lab_launcher_status_meta', $meta);
}

function lab_launcher_mark_lab_ready($email, $lab_id)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);

    if (!$email || !$lab_id) {
        return;
    }

    $meta = lab_launcher_get_status_meta();
    $key = "{$email}|{$lab_id}";

    if (!isset($meta[$key]) || !is_array($meta[$key])) {
        $meta[$key] = [];
    }

    if (empty($meta[$key]['ready_at'])) {
        $meta[$key]['ready_at'] = current_time('mysql');
        update_option('lab_launcher_status_meta', $meta);
    }
}

function lab_launcher_mark_lab_completed($email, $lab_id)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);

    if (!$email || !$lab_id) {
        return;
    }

    $meta = lab_launcher_get_status_meta();
    $key = "{$email}|{$lab_id}";

    if (!isset($meta[$key]) || !is_array($meta[$key])) {
        $meta[$key] = [];
    }

    $meta[$key]['completed_at'] = current_time('mysql');
    update_option('lab_launcher_status_meta', $meta);
    lab_launcher_set_status_value($email, $lab_id, 'completed');
}

function lab_launcher_get_effective_status($email, $lab_id, $persist = true)
{
    $email = sanitize_email($email);
    $lab_id = sanitize_text_field($lab_id);
    $persist = (bool) $persist;

    if (!$email || !$lab_id) {
        return 'unknown';
    }

    $raw_status = lab_launcher_get_status_value($email, $lab_id);
    if ($raw_status === 'completed' || $raw_status === 'expired') {
        return $raw_status;
    }

    $meta = lab_launcher_get_status_meta();
    $key = "{$email}|{$lab_id}";
    $lab_meta = isset($meta[$key]) && is_array($meta[$key]) ? $meta[$key] : [];

    if (!empty($lab_meta['completed_at'])) {
        if ($persist && $raw_status !== 'completed') {
            lab_launcher_set_status_value($email, $lab_id, 'completed');
        }
        return 'completed';
    }

    $started_at = $lab_meta['started_at'] ?? '';
    $ready_at = $lab_meta['ready_at'] ?? '';
    $lab_ttl = intval($lab_meta['lab_ttl'] ?? 0);
    $expiry_base_at = $ready_at ?: $started_at;
    $expiry_base_timestamp = $expiry_base_at ? intval(mysql2date('U', $expiry_base_at, false)) : 0;

    if (
        $expiry_base_timestamp > 0
        && $lab_ttl > 0
        && $raw_status === 'success'
        && ($expiry_base_timestamp + $lab_ttl) <= current_time('timestamp')
    ) {
        if ($persist && $raw_status !== 'expired') {
            lab_launcher_set_status_value($email, $lab_id, 'expired');
        }
        return 'expired';
    }

    return $raw_status;
}

function lab_launcher_status_webhook($request)
{
    $params = $request->get_json_params();

    $email = sanitize_email($params['email'] ?? '');
    $lab_id = sanitize_text_field($params['lab_id'] ?? '');
    $status = sanitize_text_field($params['status'] ?? '');
    $provided_key = $_GET['secret_key'] ?? '';

    $valid_statuses = ['pending', 'success', 'error'];
    $settings = get_option('lab_launcher_settings', []);
    $expected_key = $settings['status_webhook_token'] ?? '';

    if (!$email || !$lab_id || !in_array($status, $valid_statuses) || $provided_key !== $expected_key) {
        return new WP_REST_Response(['message' => 'Érvénytelen kérés'], 403);
    }

    lab_launcher_set_status_value($email, $lab_id, $status);
    if ($status === 'success') {
        lab_launcher_mark_lab_ready($email, $lab_id);
    }

    return new WP_REST_Response(['message' => 'Státusz frissítve'], 200);
}

function lab_launcher_status_update($request)
{
    $params = $request->get_json_params();
    $lab_id = sanitize_text_field($params['lab_id'] ?? '');

    global $lab_launcher_user_email;
    $email = sanitize_email($lab_launcher_user_email);

    if (!$lab_id || !$email) {
        return new WP_REST_Response(['message' => 'Hiányzó adatok'], 400);
    }

    $status = lab_launcher_get_effective_status($email, $lab_id);
    $meta = lab_launcher_get_status_meta_for_lab($email, $lab_id);
    $started_at = $meta['started_at'] ?? '';
    $started_at_timestamp = $started_at ? intval(mysql2date('U', $started_at, false)) : 0;
    $ready_at = $meta['ready_at'] ?? '';
    $ready_at_timestamp = $ready_at ? intval(mysql2date('U', $ready_at, false)) : 0;
    $completed_at = $meta['completed_at'] ?? '';
    $completed_at_timestamp = $completed_at ? intval(mysql2date('U', $completed_at, false)) : 0;

    return new WP_REST_Response([
        'status' => $status,
        'started_at' => $started_at,
        'started_at_ts' => $started_at_timestamp,
        'ready_at' => $ready_at,
        'ready_at_ts' => $ready_at_timestamp,
        'lab_ttl' => intval($meta['lab_ttl'] ?? 0),
        'completed_at' => $completed_at,
        'completed_at_ts' => $completed_at_timestamp,
    ], 200);
}


function lab_launcher_start_lab_rest($request)
{
    global $lab_launcher_user_email;

    $data = $request->get_json_params();

    if (!isset($data['lab_name']) || !isset($data['cloud_provider'])) {
        return new WP_REST_Response(['message' => 'Hiányzó paraméterek'], 400);
    }

    if (empty($lab_launcher_user_email) || strpos($lab_launcher_user_email, '@') === false) {
        return new WP_REST_Response(['message' => 'Felhasználói e-mail nem elérhető vagy érvénytelen'], 401);
    }

    $lab_id = sanitize_text_field($data['lab_id'] ?? $data['lab_name']);
    $payload = array(
        'lab_name' => sanitize_text_field($data['lab_name']),
        'cloud_provider' => sanitize_text_field($data['cloud_provider']),
        'lab_ttl' => intval($data['lab_ttl'] ?? 5400),
        'email' => $lab_launcher_user_email
    );

    $result = lab_launcher_call_backend($payload, 'start-lab');

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'message' => $result->get_error_message()
        ], $result->get_error_data()['status'] ?? 500);
    }

    lab_launcher_set_status_started_at($lab_launcher_user_email, $lab_id, intval($data['lab_ttl'] ?? 5400));
    lab_launcher_set_status_value($lab_launcher_user_email, $lab_id, 'pending');

    return new WP_REST_Response($result, 200);
}

function lab_check_lab_rest($request)
{
    global $lab_launcher_user_email;

    $data = $request->get_json_params();

    if (!isset($data['lab_name']) || !isset($data['cloud_provider']) || !isset($data['user'])) {
        return new WP_REST_Response(['message' => 'Hiányzó paraméterek'], 400);
    }

    if (empty($lab_launcher_user_email) || strpos($lab_launcher_user_email, '@') === false) {
        return new WP_REST_Response(['message' => 'Felhasználói e-mail nem elérhető vagy érvénytelen'], 401);
    }

    $lab_id = sanitize_text_field($data['lab_id'] ?? $data['lab_name']);
    $payload = array(
        'lab' => sanitize_text_field($data['lab_name']),
        'cloud' => sanitize_text_field($data['cloud_provider']),
        'user' => sanitize_text_field($data['user']),
        'email' => $lab_launcher_user_email
    );

    $result = lab_launcher_call_backend($payload, '/verify-lab');

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'message' => $result->get_error_message()
        ], $result->get_error_data()['status'] ?? 500);
    }

    // Itt ellenőrizzük a sikert, és ha completed, elmentjük a státuszt
    if (isset($result['success']) && $result['success'] === true) {
        $user_obj = get_user_by('email', $lab_launcher_user_email);
        if ($user_obj) {
            $meta_key = 'lab_' . sanitize_key($data['lab_name']) . '_status';
            update_user_meta($user_obj->ID, $meta_key, 'completed');
        }
        lab_launcher_mark_lab_completed($lab_launcher_user_email, $lab_id);
    }

    return new WP_REST_Response($result, 200);
}
