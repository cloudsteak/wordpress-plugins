<?php

require_once plugin_dir_path(__FILE__) . 'vendor/Parsedown.php';

/**
 * Preprocess markdown image syntax with optional size attributes.
 *
 * Standard:  ![alt](url)
 * With size: ![alt](url){width=300px}
 *            ![alt](url){width=50% height=200px}
 */
function lab_launcher_preprocess_markdown_images($markdown)
{
    return preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)]+)\)\{([^}]+)\}/',
        function ($matches) {
            $alt = $matches[1];
            $url = trim($matches[2]);
            $attrs = $matches[3];

            $html_attrs = '';
            $style_parts = ['max-width:100%', 'height:auto'];

            if (preg_match('/width=([^\s}]+)/', $attrs, $width_match)) {
                $width = sanitize_text_field($width_match[1]);
                if ($width !== '') {
                    $html_attrs .= ' width="' . esc_attr($width) . '"';
                }
            }

            if (preg_match('/height=([^\s}]+)/', $attrs, $height_match)) {
                $height = sanitize_text_field($height_match[1]);
                if ($height !== '') {
                    $html_attrs .= ' height="' . esc_attr($height) . '"';
                    $style_parts = array_values(array_filter($style_parts, fn($part) => $part !== 'height:auto'));
                }
            }

            $style = ' style="' . esc_attr(implode(';', $style_parts)) . '"';

            return sprintf(
                '<img src="%s" alt="%s"%s%s>',
                esc_url($url),
                esc_attr($alt),
                $html_attrs,
                $style
            );
        },
        $markdown
    );
}

function lab_launcher_get_parsedown()
{
    static $parsedown = null;

    if ($parsedown === null) {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false);
    }

    return $parsedown;
}

/**
 * Sanitize parsed markdown HTML while preserving fenced code blocks verbatim.
 *
 * wp_kses_post decodes entities like &lt; inside <code>, which breaks paths such as
 * C:\Users\<felhasználónév>\Downloads by treating placeholder tags as HTML.
 */
function lab_launcher_sanitize_markdown_html($html)
{
    if (!is_string($html) || $html === '') {
        return '';
    }

    $placeholders = [];
    $index = 0;

    $protected = preg_replace_callback(
        '/<pre\b[^>]*>\s*<code\b[^>]*>.*?<\/code>\s*<\/pre>/is',
        function ($matches) use (&$placeholders, &$index) {
            $key = '%%LABCODEBLOCK' . $index++ . '%%';
            $placeholders[$key] = $matches[0];
            return $key;
        },
        $html
    );

    $protected = preg_replace_callback(
        '/<code\b[^>]*>.*?<\/code>/is',
        function ($matches) use (&$placeholders, &$index) {
            $key = '%%LABCODEBLOCK' . $index++ . '%%';
            $placeholders[$key] = $matches[0];
            return $key;
        },
        $protected
    );

    $allowed = wp_kses_allowed_html('post');
    if (!isset($allowed['code'])) {
        $allowed['code'] = [];
    }
    $allowed['code']['class'] = true;
    if (!isset($allowed['a'])) {
        $allowed['a'] = [];
    }
    $allowed['a']['target'] = true;
    $allowed['a']['rel'] = true;

    $sanitized = wp_kses($protected, $allowed);

    if (!empty($placeholders)) {
        $sanitized = str_replace(array_keys($placeholders), array_values($placeholders), $sanitized);
    }

    return $sanitized;
}

function lab_launcher_add_target_blank_to_links($html)
{
    if (!is_string($html) || $html === '') {
        return '';
    }

    return preg_replace_callback(
        '/<a\b([^>]*)>/i',
        function ($matches) {
            $attrs = trim($matches[1]);

            $attrs = preg_replace('/\btarget\s*=\s*("|\')[^"\']*\1/i', '', $attrs);
            $attrs = preg_replace('/\btarget\s*=\S+/i', '', $attrs);
            $attrs = trim(preg_replace('/\s+/', ' ', $attrs));

            if (preg_match('/\brel\s*=\s*("|\')([^"\']*)\1/i', $attrs, $rel_match)) {
                $rel = trim($rel_match[2]);
                if (stripos($rel, 'noopener') === false) {
                    $rel = trim($rel . ' noopener noreferrer');
                }
                $attrs = preg_replace(
                    '/\brel\s*=\s*("|\')[^"\']*\1/i',
                    'rel="' . esc_attr($rel) . '"',
                    $attrs
                );
            } else {
                $attrs = trim($attrs . ' rel="noopener noreferrer"');
            }

            return '<a ' . $attrs . ' target="_blank">';
        },
        $html
    );
}

function lab_launcher_parse_markdown($markdown)
{
    if (!is_string($markdown) || trim($markdown) === '') {
        return '';
    }

    $preprocessed = lab_launcher_preprocess_markdown_images($markdown);
    $html = lab_launcher_get_parsedown()->text($preprocessed);
    $html = lab_launcher_sanitize_markdown_html($html);

    return lab_launcher_add_target_blank_to_links($html);
}

function lab_launcher_render_description_pages($lab)
{
    $use_markdown = !empty($lab['use_markdown_description']);

    if ($use_markdown) {
        $source = $lab['description_md'] ?? '';
        $pages = explode('<!-- pagebreak -->', $source);
    } else {
        $source = $lab['description'] ?? '';
        $pages = explode('<!-- pagebreak -->', wp_kses_post($source));
    }

    $output = '';
    $page_index = 0;

    foreach ($pages as $page_content) {
        if (!trim($page_content)) {
            continue;
        }

        $output .= '<div class="lab-page" style="display: none;" data-page="' . $page_index++ . '">';

        if ($use_markdown) {
            $output .= lab_launcher_parse_markdown($page_content);
        } else {
            $output .= $page_content;
        }

        $output .= '</div>';
    }

    return $output;
}

add_action('rest_api_init', function () {
    register_rest_route('lab-launcher/v1', '/preview-markdown', [
        'methods' => 'POST',
        'callback' => 'lab_launcher_preview_markdown_rest',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});

function lab_launcher_preview_markdown_rest($request)
{
    $params = $request->get_json_params();
    $markdown = isset($params['markdown']) ? wp_unslash($params['markdown']) : '';

    if (!is_string($markdown)) {
        return new WP_REST_Response(['message' => 'Érvénytelen markdown tartalom'], 400);
    }

    return new WP_REST_Response([
        'html' => lab_launcher_parse_markdown($markdown),
    ], 200);
}
