<?php
// admin/shared-parts-page.php

function lab_launcher_get_shared_parts()
{
    $parts = get_option('lab_launcher_shared_parts', []);

    return is_array($parts) ? $parts : [];
}

function lab_launcher_normalize_shared_part_id($raw_id)
{
    $id = is_string($raw_id) ? wp_unslash($raw_id) : '';

    return sanitize_title($id);
}

function lab_launcher_sanitize_shared_part_id($raw_id)
{
    $id = lab_launcher_normalize_shared_part_id($raw_id);
    if ($id === '') {
        return '';
    }

    $parts = lab_launcher_get_shared_parts();

    return isset($parts[$id]) ? $id : '';
}

function lab_launcher_shared_part_usage_count($part_id)
{
    $labs = get_option('lab_launcher_labs', []);
    if (!is_array($labs)) {
        return 0;
    }

    $count = 0;
    foreach ($labs as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        if (($lab['shared_part_id'] ?? '') === $part_id) {
            $count++;
        }
    }

    return $count;
}

function lab_launcher_render_shared_part_select($selected_id)
{
    $selected_id = lab_launcher_normalize_shared_part_id($selected_id);
    $parts = lab_launcher_get_shared_parts();
    $options = ['' => '— Nincs —'];

    foreach ($parts as $id => $part) {
        $options[$id] = is_array($part) ? ($part['title'] ?? $id) : $id;
    }

    $missing_id = ($selected_id !== '' && !isset($options[$selected_id])) ? $selected_id : '';
    if ($missing_id !== '') {
        $selected_id = '';
    }
    $current_label = $options[$selected_id] ?? '— Nincs —';

    echo '<div class="lab-shared-select">';
    printf(
        '<input type="hidden" name="shared_part_id" id="shared_part_id" value="%s">',
        esc_attr($selected_id)
    );
    echo '<button type="button" class="lab-shared-select-toggle" aria-haspopup="listbox" aria-expanded="false">';
    echo '<span class="lab-shared-select-label">' . esc_html($current_label) . '</span>';
    echo '</button>';
    echo '<ul class="lab-shared-select-menu" role="listbox" hidden>';

    foreach ($options as $value => $label) {
        printf(
            '<li role="option" tabindex="-1" data-value="%s" aria-selected="%s">%s</li>',
            esc_attr($value),
            $value === $selected_id ? 'true' : 'false',
            esc_html($label)
        );
    }

    echo '</ul>';
    echo '</div>';

    if ($missing_id !== '') {
        echo '<p class="description" style="color:#b32d2e;">A korábban kiválasztott rész (<code>'
            . esc_html($missing_id)
            . '</code>) már nem létezik. Mentéskor a hivatkozás törlődik, ha nem választasz másikat.</p>';
    }
}

function lab_launcher_shared_parts_page()
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    $parts = lab_launcher_get_shared_parts();
    $notice = '';
    $error = '';

    $edit_id = isset($_GET['edit_part'])
        ? lab_launcher_normalize_shared_part_id(wp_unslash($_GET['edit_part']))
        : '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab_launcher_delete_shared_part'])) {
        check_admin_referer('lab_launcher_delete_shared_part');
        $delete_id = lab_launcher_normalize_shared_part_id(wp_unslash($_POST['lab_launcher_delete_shared_part']));
        if ($delete_id !== '' && isset($parts[$delete_id])) {
            unset($parts[$delete_id]);
            update_option('lab_launcher_shared_parts', $parts);
            $notice = 'Közös rész törölve.';
            if ($edit_id === $delete_id) {
                $edit_id = '';
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab_launcher_save_shared_part'])) {
        check_admin_referer('lab_launcher_save_shared_part');

        $id = lab_launcher_normalize_shared_part_id(wp_unslash($_POST['shared_part_id'] ?? ''));
        $original_id = lab_launcher_normalize_shared_part_id(wp_unslash($_POST['original_shared_part_id'] ?? ''));
        $is_edit = $original_id !== '' && isset($parts[$original_id]);
        $title = sanitize_text_field(wp_unslash($_POST['shared_part_title'] ?? ''));
        $content = isset($_POST['content_md']) ? wp_unslash($_POST['content_md']) : '';
        $content = is_string($content) ? $content : '';

        if ($id === '') {
            $error = 'Adj meg egy azonosítót, például azure-belepes.';
        } elseif ($title === '') {
            $error = 'A megjelenő név kötelező.';
        } elseif (trim($content) === '') {
            $error = 'A Markdown tartalom nem lehet üres.';
        } elseif ($is_edit && $id !== $original_id) {
            $error = 'Meglévő közös rész azonosítója nem módosítható.';
        } elseif (!$is_edit && isset($parts[$id])) {
            $error = 'Már létezik ilyen azonosító: ' . $id;
        } else {
            $parts[$id] = [
                'id' => $id,
                'title' => $title,
                'content_md' => $content,
            ];
            update_option('lab_launcher_shared_parts', $parts);
            $edit_id = $id;
            $notice = 'Közös rész elmentve.';
        }
    }

    $saved = ($edit_id !== '' && isset($parts[$edit_id])) ? $parts[$edit_id] : null;
    $form = [
        'id' => $saved['id'] ?? '',
        'title' => $saved['title'] ?? '',
        'content_md' => $saved['content_md'] ?? '',
    ];
    if ($error !== '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab_launcher_save_shared_part'])) {
        $form = [
            'id' => lab_launcher_normalize_shared_part_id(wp_unslash($_POST['shared_part_id'] ?? '')),
            'title' => sanitize_text_field(wp_unslash($_POST['shared_part_title'] ?? '')),
            'content_md' => is_string($_POST['content_md'] ?? null) ? wp_unslash($_POST['content_md']) : '',
        ];
    }
    ?>
    <div class="wrap">
        <?php if ($notice !== ''): ?>
            <div class="updated"><p><?php echo esc_html($notice); ?></p></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="error"><p><strong>Hiba:</strong> <?php echo esc_html($error); ?></p></div>
        <?php endif; ?>

        <div style="display:flex;justify-content:flex-start;align-items:center;gap:12px;flex-wrap:wrap;">
            <h1>Közös részek</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=lab-launcher-shared-parts')); ?>" class="button button-primary">
                Új közös rész
            </a>
        </div>
        <p>Itt hozhatók létre azok a Markdown részek, amelyeket több lab is használhat. A lab szerkesztőben kiválasztott rész a lab lapozójának első oldala lesz. Tipikusan ide kerül a felhőbe való bejelentkezés.</p>

        <?php if (!empty($parts)): ?>
            <table class="widefat striped" style="max-width:900px;margin:16px 0 24px;">
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Azonosító</th>
                        <th>Labok</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parts as $part_id => $part): ?>
                        <?php
                        $usage = lab_launcher_shared_part_usage_count($part_id);
                        $usage_label = $usage === 1 ? '1 lab' : $usage . ' lab';
                        $confirm = $usage > 0
                            ? sprintf('Ezt a részt %d lab használja. Törlés után ezeken nem jelenik meg. Biztosan törlöd?', $usage)
                            : 'Biztosan törölni szeretnéd ezt a közös részt?';
                        ?>
                        <tr>
                            <td><?php echo esc_html($part['title'] ?? $part_id); ?></td>
                            <td><code><?php echo esc_html($part_id); ?></code></td>
                            <td><?php echo esc_html($usage_label); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=lab-launcher-shared-parts&edit_part=' . urlencode($part_id))); ?>">Szerkesztés</a>
                                <form method="post" style="display:inline;margin-left:8px;">
                                    <?php wp_nonce_field('lab_launcher_delete_shared_part'); ?>
                                    <input type="hidden" name="lab_launcher_delete_shared_part" value="<?php echo esc_attr($part_id); ?>">
                                    <button type="submit" class="button button-small" onclick="return confirm(<?php echo wp_json_encode($confirm); ?>);">Törlés</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?php echo $saved ? 'Közös rész szerkesztése' : 'Új közös rész'; ?></h2>
        <form method="post">
            <?php wp_nonce_field('lab_launcher_save_shared_part'); ?>
            <input type="hidden" name="lab_launcher_save_shared_part" value="1">
            <input type="hidden" name="original_shared_part_id" value="<?php echo esc_attr($saved['id'] ?? ''); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="shared_part_id_field">Azonosító</label></th>
                    <td>
                        <input name="shared_part_id" id="shared_part_id_field" type="text" required class="regular-text"
                            placeholder="pl. azure-belepes"
                            value="<?php echo esc_attr($form['id']); ?>" <?php echo $saved ? 'readonly' : ''; ?>>
                        <p class="description">Ezt a labok hivatkozásként tárolják. Létrehozás után nem módosítható.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="shared_part_title">Megjelenő név</label></th>
                    <td>
                        <input name="shared_part_title" id="shared_part_title" type="text" required class="regular-text"
                            placeholder="pl. Azure bejelentkezés"
                            value="<?php echo esc_attr($form['title']); ?>">
                        <p class="description">Ez a név jelenik meg a lab szerkesztő legördülőjében.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="content_md">Markdown</label></th>
                    <td>
                        <div class="lab-md-editor"
                            data-preview-url="<?php echo esc_url(rest_url('lab-launcher/v1/preview-markdown')); ?>"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
                            <div class="lab-md-editor-toolbar">
                                <div class="lab-md-editor-tabs">
                                    <button type="button" class="lab-md-tab is-active" data-mode="code">Kód</button>
                                    <button type="button" class="lab-md-tab" data-mode="preview">Előnézet</button>
                                </div>
                                <button type="button" class="button lab-md-insert-image">Kép beszúrása</button>
                                <button type="button" class="button lab-md-insert-pagebreak">Oldaltörés</button>
                            </div>
                            <div class="lab-md-editor-body">
                                <textarea name="content_md" id="content_md" class="lab-md-textarea" placeholder="Írd ide a közös részt Markdownban..."><?php echo esc_textarea($form['content_md']); ?></textarea>
                                <div class="lab-md-preview"></div>
                            </div>
                        </div>
                        <div class="lab-md-help">
                            Oldaltörés nélkül ez a lab első oldala. Ha <code>&lt;!-- pagebreak --&gt;</code> is szerepel benne, azok a lab első lapjai lesznek, utána jön a lab saját leírása.
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button($saved ? 'Közös rész mentése' : 'Közös rész létrehozása'); ?>
        </form>
    </div>
    <?php
}
