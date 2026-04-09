<?php
// admin/courses-page.php

add_action('init', function () {
    register_post_type('lab_training', [
        'label' => 'Képzések',
        'labels' => [
            'name' => 'Képzések',
            'singular_name' => 'Képzés',
            'add_new' => 'Új képzés',
            'add_new_item' => 'Új képzés',
            'edit_item' => 'Képzés szerkesztése',
            'new_item' => 'Új képzés',
            'view_item' => 'Képzés megtekintése',
            'search_items' => 'Képzések keresése',
            'not_found' => 'Nincs találat.',
            'not_found_in_trash' => 'Nincs találat a kukában.',
            'all_items' => 'Képzések',
            'menu_name' => 'Képzések',
            'name_admin_bar' => 'Képzés',
        ],
        'public' => false,
        'show_ui' => true,
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'edit_post',
            'delete_post' => 'delete_post',
            'edit_posts' => 'edit_posts',
            'edit_others_posts' => 'edit_others_posts',
            'publish_posts' => 'publish_posts',
            'read_post' => 'read_post',
            'read_private_posts' => 'read_private_posts'
        ],
        'map_meta_cap' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'show_in_menu' => 'cloud-lab',
        'supports' => ['title', 'editor'],
        'has_archive' => false,
        'rewrite' => false
    ]);
});



add_action('add_meta_boxes', function () {
    add_meta_box('lab_training_shortcode', 'Shortcode', 'render_shortcode_box', 'lab_training', 'side');
    add_meta_box('lab_training_labs', 'Hozzárendelt Lab-ok', 'render_labs_box', 'lab_training');
});

function render_labs_box($post)
{
    $selected = get_post_meta($post->ID, 'assigned_labs', true) ?: [];
    $labs = get_option('lab_launcher_labs', []);

    if (empty($labs)) {
        echo '<p><em>Nincs elérhető Lab. Először hozz létre egyet a „Lab kezelő” menüpontban!</em></p>';
        return;
    }

    $ordered_labs = [];

    foreach ($selected as $lab_id) {
        if (isset($labs[$lab_id])) {
            $ordered_labs[$lab_id] = $labs[$lab_id];
        }
    }

    foreach ($labs as $lab_id => $lab_data) {
        if (!isset($ordered_labs[$lab_id])) {
            $ordered_labs[$lab_id] = $lab_data;
        }
    }

    echo '<style>
        .lab-training-labs-list {
            margin: 0;
        }

        .lab-training-labs-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
            padding: 8px 10px;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            background: #fff;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .lab-training-labs-list label {
            flex: 1;
        }

        .lab-training-labs-actions {
            display: inline-flex;
            gap: 6px;
        }

        .lab-training-labs-list li.is-active {
            background: #e7f5ff;
            border-color: #2271b1;
        }
    </style>';

    echo '<p><em>Jelöld ki a lab-okat, majd a Fel és Le gombokkal állítsd be a sorrendet.</em></p>';
    echo '<ul class="lab-training-labs-list">';
    foreach ($ordered_labs as $lab_id => $lab_data) {
        $checked = in_array($lab_id, $selected) ? 'checked' : '';
        echo "<li>";
        echo "<label><input type='checkbox' name='assigned_labs[]' value='" . esc_attr($lab_id) . "' $checked> " . esc_html($lab_id . ' (' . ucfirst($lab_data['cloud']) . ')') . "</label>";
        echo "<span class='lab-training-labs-actions'>";
        echo "<button type='button' class='button button-small lab-move-up' aria-label='Mozgatás fel' title='Mozgatás fel'>&uarr;</button>";
        echo "<button type='button' class='button button-small lab-move-down' aria-label='Mozgatás le' title='Mozgatás le'>&darr;</button>";
        echo "</span>";
        echo "</li>";
    }
    echo '</ul>';
    ?>
    <script>
        jQuery(function ($) {
            const $list = $('.lab-training-labs-list');
            let highlightTimer = null;

            function highlightItem($item) {
                $list.find('li').removeClass('is-active');
                $item.addClass('is-active');

                if (highlightTimer) {
                    window.clearTimeout(highlightTimer);
                }

                highlightTimer = window.setTimeout(function () {
                    $item.removeClass('is-active');
                }, 3000);
            }

            $list.on('click', '.lab-move-up', function () {
                const $item = $(this).closest('li');
                const $prev = $item.prev('li');

                if ($prev.length) {
                    $item.insertBefore($prev);
                    highlightItem($item);
                }
            });

            $list.on('click', '.lab-move-down', function () {
                const $item = $(this).closest('li');
                const $next = $item.next('li');

                if ($next.length) {
                    $item.insertAfter($next);
                    highlightItem($item);
                }
            });
        });
    </script>
    <?php
}

add_action('save_post_lab_training', function ($post_id) {
    if (isset($_POST['assigned_labs'])) {
        update_post_meta($post_id, 'assigned_labs', array_map('sanitize_text_field', $_POST['assigned_labs']));
    } else {
        delete_post_meta($post_id, 'assigned_labs');
    }
});

add_shortcode('lab_training', function ($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $post = get_post($atts['id']);
    if (!$post || $post->post_type !== 'lab_training')
        return '';

    $assigned = get_post_meta($post->ID, 'assigned_labs', true) ?: [];
    $all_labs = get_option('lab_launcher_labs', []);

    if (!function_exists('get_user_lab_status')) {
        function get_user_lab_status($user_id, $lab_id)
        {
            // Teszt funkció: mindig "not_started"
            return 'not_started';
        }
    }

    ob_start();
    echo "<div class='training-box' style='font-family:sans-serif;'>";
    echo "<h2 style='font-size: 28px; margin-bottom: 1rem;'>Képzés: " . esc_html($post->post_title) . "</h2>";

    foreach ($assigned as $lab_id) {
        if (!isset($all_labs[$lab_id]))
            continue;
        $lab = $all_labs[$lab_id];

        $status = get_user_lab_status(get_current_user_id(), $lab_id); // 'ready', 'in_progress', 'not_started'
        if ($status === 'ready') {
            $icon = '<i class="fas fa-check-circle" style="color:green;"></i>';
        } else {
            $parsed_url = parse_url($_SERVER['REQUEST_URI']);
            $ref_path = $parsed_url['path'] ?? '';
            $launch_url = home_url('/labs') . '?id=' . urlencode($lab_id) . '&ref=' . urlencode($ref_path);

            $icon_class = ($status === 'in_progress') ? 'fas fa-spinner fa-spin' : 'fas fa-play-circle';
            $icon_color = ($status === 'in_progress') ? 'orange' : 'gray';
            $icon = '<a href="' . $launch_url . '"><i class="' . $icon_class . '" style="color:' . $icon_color . ';"></i></a>';
        }


        echo "<div style='border:2px solid #00AAB2;padding:16px;margin-bottom:16px;border-radius:16px;display:flex;align-items:center;'>";
        echo "<div style='font-size: 32px; margin-right: 12px;'>$icon</div>";
        echo "<div>";
        echo "<div style='font-size: 20px; font-weight: bold;'>" . esc_html($lab['lab_title'] ?? $lab_id) . "</div>";

        echo "<div style='color: #555;'>" . esc_html($lab['lab_brief'] ?? '') . "</div>";

        echo "</div></div>";
    }

    echo "</div>";
    return ob_get_clean();
});

function render_shortcode_box($post)
{
    echo '<input type="text" readonly value="[lab_training id=' . esc_attr($post->ID) . ']" style="width:100%;">';
}
