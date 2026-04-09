<?php
// admin/user-statuses-page.php

if (!defined('ABSPATH')) { exit; }

// Menü: Cloud Lab alatt "Felhasználói státuszok"
add_action('admin_menu', function () {
    add_submenu_page(
        'cloud-lab',
        'Felhasználói státuszok',
        'Felhasználói státuszok',
        'edit_posts',
        'lab-user-statuses',
        'lab_launcher_render_user_statuses_page'
    );
});

// Biztonság: nonce kulcs
function lab_launcher_statuses_nonce_action() { return 'lab_launcher_statuses_action'; }
function lab_launcher_statuses_nonce_name() { return '_lab_launcher_statuses_nonce'; }

// Egy státusz reset kezelése (admin-post)
add_action('admin_post_lab_launcher_reset_status', function () {
    if (!current_user_can('edit_posts')) { wp_die('Nincs jogosultság.'); }
    check_admin_referer(lab_launcher_statuses_nonce_action(), lab_launcher_statuses_nonce_name());

    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
    $lab_id = isset($_GET['lab_id']) ? sanitize_text_field($_GET['lab_id']) : '';

    if ($email && $lab_id) {
        $statuses = get_option('lab_launcher_statuses', []);
        $status_meta = lab_launcher_get_status_meta();
        $key = "{$email}|{$lab_id}";
        if (isset($statuses[$key])) {
            unset($statuses[$key]);
            update_option('lab_launcher_statuses', $statuses);
        }
        if (isset($status_meta[$key])) {
            unset($status_meta[$key]);
            update_option('lab_launcher_status_meta', $status_meta);
        }
    }

    $redirect = add_query_arg(['page' => 'lab-user-statuses', 'reset' => '1'], admin_url('admin.php'));
    wp_safe_redirect($redirect);
    exit;
});

// Tömeges reset (POST űrlapról)
add_action('admin_post_lab_launcher_bulk_reset', function () {
    if (!current_user_can('edit_posts')) { wp_die('Nincs jogosultság.'); }
    check_admin_referer(lab_launcher_statuses_nonce_action(), lab_launcher_statuses_nonce_name());

    $selected = isset($_POST['selected']) && is_array($_POST['selected']) ? array_map('sanitize_text_field', $_POST['selected']) : [];
    if ($selected) {
        $statuses = get_option('lab_launcher_statuses', []);
        $status_meta = lab_launcher_get_status_meta();
        foreach ($selected as $key) {
            if (isset($statuses[$key])) { unset($statuses[$key]); }
            if (isset($status_meta[$key])) { unset($status_meta[$key]); }
        }
        update_option('lab_launcher_statuses', $statuses);
        update_option('lab_launcher_status_meta', $status_meta);
    }

    $redirect = add_query_arg(['page' => 'lab-user-statuses', 'bulk_reset' => '1'], admin_url('admin.php'));
    wp_safe_redirect($redirect);
    exit;
});

// Oldal kirajzolása
function lab_launcher_render_user_statuses_page() {
    if (!current_user_can('edit_posts')) { wp_die('Nincs jogosultság.'); }

    $q_email = isset($_GET['s_email']) ? sanitize_email($_GET['s_email']) : '';
    $q_lab   = isset($_GET['s_lab']) ? sanitize_text_field($_GET['s_lab']) : '';
    $q_cloud = isset($_GET['s_cloud']) ? sanitize_text_field($_GET['s_cloud']) : '';
    $q_status = isset($_GET['s_status']) ? sanitize_text_field($_GET['s_status']) : '';

    $statuses = get_option('lab_launcher_statuses', []);
    $status_meta = lab_launcher_get_status_meta();
    $labs = get_option('lab_launcher_labs', []);
    $rows = [];
    $emails = [];
    $lab_ids = [];
    $clouds = [];
    $statuses_available = [];

    foreach ($statuses as $key => $status) {
        $parts = explode('|', $key, 2);
        if (count($parts) !== 2) { continue; }
        list($email, $lab_id) = $parts;
        $cloud = isset($labs[$lab_id]['cloud']) ? sanitize_text_field($labs[$lab_id]['cloud']) : '';

        $emails[$email] = $email;
        $lab_ids[$lab_id] = $lab_id;
        if ($cloud) {
            $clouds[$cloud] = $cloud;
        }
        $statuses_available[$status] = $status;

        if ($q_email && $email !== $q_email) { continue; }
        if ($q_lab && $lab_id !== $q_lab) { continue; }
        if ($q_cloud && $cloud !== $q_cloud) { continue; }
        if ($q_status && $status !== $q_status) { continue; }

        $user = get_user_by('email', $email);
        $user_display = $user ? sprintf('%s (#%d)', $user->display_name ?: $user->user_login, $user->ID) : 'Ismeretlen felhasználó';
        $started_at = $status_meta[$key]['started_at'] ?? '';

        if ($started_at) {
            $timestamp = mysql2date('U', $started_at, false);
            $started_at_display = $timestamp ? wp_date('Y-m-d H:i:s', $timestamp) : $started_at;
        } else {
            $started_at_display = 'Nincs adat';
        }

        $rows[] = [
            'key'    => $key,
            'email'  => $email,
            'user'   => $user_display,
            'lab_id' => $lab_id,
            'cloud' => $cloud,
            'status' => $status,
            'started_at' => $started_at_display,
        ];
    }

    natcasesort($emails);
    natcasesort($lab_ids);
    natcasesort($clouds);
    natcasesort($statuses_available);

    usort($rows, function($a, $b){
        return [$a['email'], $a['lab_id']] <=> [$b['email'], $b['lab_id']];
    });

    $grouped_rows = [];
    foreach ($rows as $row) {
        if (!isset($grouped_rows[$row['email']])) {
            $grouped_rows[$row['email']] = [
                'user' => $row['user'],
                'items' => [],
            ];
        }

        $grouped_rows[$row['email']]['items'][] = $row;
    }

    $nonce_field = wp_nonce_field(lab_launcher_statuses_nonce_action(), lab_launcher_statuses_nonce_name(), true, false);

    ?>
    <div class="wrap">
        <h1>Felhasználói státuszok</h1>

        <?php if (isset($_GET['reset'])): ?>
            <div class="notice notice-success is-dismissible"><p>Státusz visszaállítva.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['bulk_reset'])): ?>
            <div class="notice notice-success is-dismissible"><p>Tömeges visszaállítás kész.</p></div>
        <?php endif; ?>

        <style>
            .ll-filters {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                align-items: end;
                margin: 16px 0;
            }

            .ll-filter-field {
                min-width: 280px;
            }

            .ll-filter-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 6px;
            }

            .ll-filter-field select {
                width: 100%;
            }

            .ll-user-group td {
                background: #f6f7f7;
                font-weight: 600;
            }

            .ll-user-meta {
                color: #50575e;
                font-weight: 400;
                margin-left: 8px;
            }

            .ll-started-at {
                white-space: nowrap;
            }
        </style>

        <form method="get" class="ll-filters">
            <input type="hidden" name="page" value="lab-user-statuses"/>

            <div class="ll-filter-field">
                <label for="ll-filter-email">Szűrés emailre</label>
                <select id="ll-filter-email" name="s_email">
                    <option value="">Összes email</option>
                    <?php foreach ($emails as $email): ?>
                        <option value="<?php echo esc_attr($email); ?>" <?php selected($q_email, $email); ?>>
                            <?php echo esc_html($email); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ll-filter-field">
                <label for="ll-filter-lab">Szűrés lab azonosítóra</label>
                <select id="ll-filter-lab" name="s_lab">
                    <option value="">Összes lab</option>
                    <?php foreach ($lab_ids as $lab_id): ?>
                        <option value="<?php echo esc_attr($lab_id); ?>" <?php selected($q_lab, $lab_id); ?>>
                            <?php echo esc_html($lab_id); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ll-filter-field">
                <label for="ll-filter-cloud">Szűrés cloud-ra</label>
                <select id="ll-filter-cloud" name="s_cloud">
                    <option value="">Összes cloud</option>
                    <?php foreach ($clouds as $cloud): ?>
                        <option value="<?php echo esc_attr($cloud); ?>" <?php selected($q_cloud, $cloud); ?>>
                            <?php echo esc_html(strtoupper($cloud)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ll-filter-field">
                <label for="ll-filter-status">Szűrés státuszra</label>
                <select id="ll-filter-status" name="s_status">
                    <option value="">Összes státusz</option>
                    <?php foreach ($statuses_available as $status_value): ?>
                        <?php
                        $status_label = [
                            'pending' => 'Folyamatban',
                            'success' => 'Elérhető',
                            'error' => 'Hiba',
                        ][$status_value] ?? $status_value;
                        ?>
                        <option value="<?php echo esc_attr($status_value); ?>" <?php selected($q_status, $status_value); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button class="button button-primary">Szűrés</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lab-user-statuses')); ?>">Szűrők törlése</a>
            </div>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php echo $nonce_field; ?>
            <input type="hidden" name="action" value="lab_launcher_bulk_reset" />

            <table class="widefat striped">
                <thead>
                    <tr>
                        <td style="width:24px;"><input type="checkbox" onclick="jQuery('.ll-check').prop('checked', this.checked);" /></td>
                        <th>Email</th>
                        <th>Felhasználó</th>
                        <th>Lab ID</th>
                        <th>Lab indulás</th>
                        <th>Státusz</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$grouped_rows): ?>
                    <tr><td colspan="7">Nincs megjeleníthető adat.</td></tr>
                <?php else: ?>
                    <?php foreach ($grouped_rows as $email => $group): ?>
                    <tr class="ll-user-group">
                        <td colspan="7">
                            <?php echo esc_html($email); ?>
                            <span class="ll-user-meta"><?php echo esc_html($group['user']); ?></span>
                        </td>
                    </tr>
                    <?php foreach ($group['items'] as $r):
                        $reset_url = wp_nonce_url(
                            add_query_arg([
                                'action' => 'lab_launcher_reset_status',
                                'email'  => $r['email'],
                                'lab_id' => $r['lab_id']
                            ], admin_url('admin-post.php')),
                            lab_launcher_statuses_nonce_action(),
                            lab_launcher_statuses_nonce_name()
                        );
                    ?>
                    <tr>
                        <td><input type="checkbox" class="ll-check" name="selected[]" value="<?php echo esc_attr($r['key']); ?>" /></td>
                        <td><?php echo esc_html($r['email']); ?></td>
                        <td><?php echo esc_html($r['user']); ?></td>
                        <td><code><?php echo esc_html($r['lab_id']); ?></code></td>
                        <td class="ll-started-at"><?php echo esc_html($r['started_at']); ?></td>
                        <td>
                            <?php
                                $label = [
                                    'pending' => 'Folyamatban',
                                    'success' => 'Elérhető',
                                    'error'   => 'Hiba',
                                ][$r['status']] ?? $r['status'];
                                echo esc_html($label);
                            ?>
                        </td>
                        <td>
                            <a class="button button-small lab-admin-button" href="<?php echo esc_url($reset_url); ?>"
                               onclick="return confirm('Biztosan visszaállítod ezt a státuszt?');">
                               Alaphelyzetbe
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">
                            <button class="button button-primary" 
                                    onclick="return confirm('Biztosan visszaállítod a kijelölteket?');">
                                Kijelöltek alaphelyzetbe
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
    <?php
}
