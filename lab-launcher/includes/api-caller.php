<?php
// includes/api-caller.php

function lab_launcher_call_backend($payload, $endpoint) {
    $settings = get_option('lab_launcher_settings');
    $auth0_domain = $settings['auth0_domain'] ?? '';
    $client_id = $settings['auth0_client_id'] ?? '';
    $client_secret = $settings['auth0_client_secret'] ?? '';
    $audience = $settings['auth0_audience'] ?? '';
    $backend_url = $settings['backend_url'] ?? '';
    $backend_api_key = $settings['backend_api_key'] ?? '';

    if (!$backend_url) {
        return new WP_Error('config_error', 'A plugin nincs megfelelően konfigurálva', array('status' => 500));
    }

    $headers = array('Content-Type' => 'application/json');

    if ($backend_api_key) {
        $headers['X-API-Key'] = $backend_api_key;
    } else {
        if (!$auth0_domain || !$client_id || !$client_secret || !$audience) {
            return new WP_Error('config_error', 'A plugin nincs megfelelően konfigurálva', array('status' => 500));
        }

        $token_response = wp_remote_post("https://$auth0_domain/oauth/token", array(
            'timeout' => 15,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'audience' => $audience
            ))
        ));

        if (is_wp_error($token_response)) {
            return new WP_Error('token_error', 'Nem sikerült Auth0 tokent lekérni', array('status' => 500));
        }

        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        $access_token = $token_data['access_token'] ?? '';

        if (!$access_token) {
            return new WP_Error('token_empty', 'Hiányzó access token', array('status' => 500));
        }

        $headers['Authorization'] = 'Bearer ' . $access_token;
    }

    $url = trailingslashit($backend_url) . ltrim($endpoint, '/');
    $args = array(
        'timeout' => 90,
        'headers' => $headers,
        'body' => json_encode($payload),
    );

    $attempts = 0;
    $max_attempts = 2;
    $retry_codes = array(502, 503, 504);

    while ($attempts < $max_attempts) {
        $attempts++;
        $backend_response = wp_remote_post($url, $args);

        if (is_wp_error($backend_response)) {
            if ($attempts < $max_attempts) {
                sleep(2);
                continue;
            }
            return new WP_Error('backend_error', 'Nem sikerült elérni a backendet', array('status' => 500));
        }

        $code = (int) wp_remote_retrieve_response_code($backend_response);
        $body = json_decode(wp_remote_retrieve_body($backend_response), true);

        if (in_array($code, $retry_codes, true) && $attempts < $max_attempts) {
            sleep(2);
            continue;
        }

        if ($code < 200 || $code >= 300) {
            $detail = '';
            if (is_array($body)) {
                $detail = $body['message'] ?? $body['detail'] ?? '';
                if (is_array($detail)) {
                    $detail = wp_json_encode($detail);
                }
            }
            $detail = is_string($detail) ? trim($detail) : '';
            $message = $detail !== ''
                ? sprintf('%s (HTTP %d)', $detail, $code)
                : sprintf('A backend hibát adott (HTTP %d)', $code);
            return new WP_Error('backend_http', $message, array('status' => $code ?: 502));
        }

        return is_array($body) ? $body : array();
    }

    return new WP_Error('backend_error', 'Nem sikerült elérni a backendet', array('status' => 500));
}

// E-mail elmentése globálisan INIT alatt
add_action('init', function () {
    global $lab_launcher_user_email;
    $current_user = wp_get_current_user();
    $lab_launcher_user_email = sanitize_email($current_user->user_email);
});
