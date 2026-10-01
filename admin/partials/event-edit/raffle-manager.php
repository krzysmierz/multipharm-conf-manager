<?php
/** Admin screen for registration QR codes, participants and repeated draws. */
if (!defined('WPINC')) {
    die;
}

$raffles = CM_Raffle::get_by_event($event->get_id());
$requested_raffle_id = absint($_GET['raffle_id'] ?? 0);
$raffle = null;
foreach ($raffles as $candidate) {
    if ((int) $candidate->id === $requested_raffle_id) {
        $raffle = $candidate;
        break;
    }
}
if (!$raffle && !empty($raffles)) {
    $raffle = $raffles[0];
}
?>
<div class="cm-raffle-manager">
    <h2><?php esc_html_e('Rejestracja i losowanie', 'conference-manager'); ?></h2>
    <p class="description"><?php esc_html_e('Każdy kod ma własną listę uczestników. Kolejne losowania zawsze obejmują całą listę, także osoby wylosowane wcześniej.', 'conference-manager'); ?></p>

    <form method="post" class="cm-raffle-create-form">
        <?php wp_nonce_field('cm_create_raffle_' . $event->get_id()); ?>
        <input type="hidden" name="action" value="cm_create_raffle">
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->get_id()); ?>">
        <label for="cm-raffle-label"><?php esc_html_e('Nazwa kodu / losowania', 'conference-manager'); ?></label>
        <input id="cm-raffle-label" class="regular-text" type="text" name="raffle_label" maxlength="255" placeholder="<?php esc_attr_e('np. Losowanie po prezentacji 1', 'conference-manager'); ?>">
        <button class="button button-primary" type="submit"><?php esc_html_e('Utwórz kod QR rejestracji', 'conference-manager'); ?></button>
    </form>

    <?php if (count($raffles) > 1): ?>
        <p><label for="cm-raffle-select"><?php esc_html_e('Wybierz losowanie:', 'conference-manager'); ?></label>
        <select id="cm-raffle-select" onchange="window.location.href=this.value">
            <?php foreach ($raffles as $item): ?>
                <option value="<?php echo esc_url(add_query_arg('raffle_id', $item->id)); ?>" <?php selected($raffle && $item->id === $raffle->id); ?>><?php echo esc_html($item->label); ?></option>
            <?php endforeach; ?>
        </select></p>
    <?php endif; ?>

    <?php if (!$raffle): ?>
        <p><?php esc_html_e('Nie utworzono jeszcze kodu rejestracji.', 'conference-manager'); ?></p>
    <?php else: ?>
        <?php $qr = CM_Raffle::get_qr($raffle->id); $registration_url = CM_Raffle::get_registration_url($raffle); $presentation_url = CM_Raffle::get_presentation_url($raffle); ?>
        <hr>
        <h3><?php echo esc_html($raffle->label); ?></h3>
        <p><strong><?php esc_html_e('Adres rejestracji:', 'conference-manager'); ?></strong><br><a href="<?php echo esc_url($registration_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($registration_url); ?></a></p>
        <p><strong><?php esc_html_e('Ekran losowania (widoczny także w harmonogramie):', 'conference-manager'); ?></strong><br><a href="<?php echo esc_url($presentation_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($presentation_url); ?></a></p>
        <?php if ($qr && ($qr_url = CM_QR_Generator::get_qr_url($qr->file_path))): ?>
            <p><a href="<?php echo esc_url($qr_url); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('Kod QR rejestracji', 'conference-manager'); ?>" style="width:200px;height:200px"></a></p>
        <?php else: ?>
            <p class="notice notice-warning inline"><span><?php esc_html_e('Brak pliku PNG QR. Adres rejestracji powyżej nadal działa.', 'conference-manager'); ?></span></p>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('cm_regenerate_raffle_qr_' . $raffle->id); ?>
            <input type="hidden" name="action" value="cm_regenerate_raffle_qr"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>">
            <button type="submit" class="button"><?php esc_html_e('Wygeneruj nowy plik QR', 'conference-manager'); ?></button>
        </form>

        <?php $participants = CM_Raffle::get_participants($raffle->id); $draws = CM_Raffle::get_draws($raffle->id); $icons = CM_Raffle::get_icons($raffle->id); ?>
        <hr><h3><?php esc_html_e('Ikony uczestników', 'conference-manager'); ?></h3>
        <p class="description"><?php esc_html_e('Dodaj PNG, JPEG, WebP lub bezpieczne SVG (maks. 2 MB na ikonę), pojedynczo lub w ZIP-ie. Gdy ikony są dostępne, uczestnik musi wybrać jedną podczas rejestracji.', 'conference-manager'); ?></p>
        <form method="post" enctype="multipart/form-data" class="cm-raffle-icon-upload-form">
            <?php wp_nonce_field('cm_upload_raffle_icons_' . $raffle->id); ?>
            <input type="hidden" name="action" value="cm_upload_raffle_icons"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>">
            <p><label><?php esc_html_e('Pliki ikon', 'conference-manager'); ?> <input type="file" name="raffle_icons[]" multiple accept="image/png,image/jpeg,image/webp,image/svg+xml,.svg"></label></p>
            <p><label><?php esc_html_e('Archiwum ZIP', 'conference-manager'); ?> <input type="file" name="raffle_icon_zip" accept="application/zip,.zip"></label></p>
            <button type="submit" class="button button-secondary"><?php esc_html_e('Prześlij ikony', 'conference-manager'); ?></button>
        </form>
        <?php if (!empty($icons)): ?>
            <div class="cm-raffle-icon-grid">
                <?php foreach ($icons as $icon): ?>
                    <div class="cm-raffle-icon-card"><img src="<?php echo esc_url(CM_Raffle::get_icon_url($icon)); ?>" alt="<?php esc_attr_e('Ikona uczestnika', 'conference-manager'); ?>">
                        <form method="post"><?php wp_nonce_field('cm_delete_raffle_icon_' . $raffle->id . '_' . $icon->id); ?><input type="hidden" name="action" value="cm_delete_raffle_icon"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>"><input type="hidden" name="icon_id" value="<?php echo esc_attr($icon->id); ?>"><button class="button-link-delete" type="submit"><?php esc_html_e('Usuń', 'conference-manager'); ?></button></form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?><p><?php esc_html_e('Brak ikon — rejestracja pozostaje bez wyboru ikony.', 'conference-manager'); ?></p><?php endif; ?>
        <hr><h3><?php printf(esc_html__('Uczestnicy (%d)', 'conference-manager'), count($participants)); ?></h3>
        <form method="post" class="cm-raffle-test-participants-form">
            <?php wp_nonce_field('cm_generate_raffle_test_participants_' . $raffle->id); ?>
            <input type="hidden" name="action" value="cm_generate_raffle_test_participants"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>">
            <button type="submit" class="button"><?php esc_html_e('Dodaj 30 testowych uczestników', 'conference-manager'); ?></button>
            <span class="description"><?php esc_html_e('Dodaje dokładnie 30 losowych imion i nazwisk do tego losowania.', 'conference-manager'); ?></span>
        </form>
        <?php if (empty($participants)): ?><p><?php esc_html_e('Brak zgłoszeń.', 'conference-manager'); ?></p><?php else: ?>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e('Ikona', 'conference-manager'); ?></th><th><?php esc_html_e('Imię i nazwisko', 'conference-manager'); ?></th><th><?php esc_html_e('Rejestracja', 'conference-manager'); ?></th><th><?php esc_html_e('Akcje', 'conference-manager'); ?></th></tr></thead><tbody>
            <?php foreach ($participants as $participant): ?><tr><td><?php if ($icon_url = CM_Raffle::get_participant_icon_url($participant)): ?><img class="cm-raffle-participant-icon" src="<?php echo esc_url($icon_url); ?>" alt=""><?php else: ?>—<?php endif; ?></td><td><?php echo esc_html($participant->first_name . ' ' . $participant->last_name); ?></td><td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $participant->created_at)); ?></td><td><form method="post" onsubmit="return confirm('<?php echo esc_js(__('Usunąć uczestnika? Zostanie też usunięta jego historia losowań.', 'conference-manager')); ?>');"><?php wp_nonce_field('cm_delete_raffle_participant_' . $raffle->id . '_' . $participant->id); ?><input type="hidden" name="action" value="cm_delete_raffle_participant"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>"><input type="hidden" name="participant_id" value="<?php echo esc_attr($participant->id); ?>"><button class="button-link-delete" type="submit"><?php esc_html_e('Usuń uczestnika', 'conference-manager'); ?></button></form></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>

        <h3><?php esc_html_e('Losuj zwycięzcę', 'conference-manager'); ?></h3>
        <form method="post">
            <?php wp_nonce_field('cm_draw_raffle_' . $raffle->id); ?>
            <input type="hidden" name="action" value="cm_draw_raffle"><input type="hidden" name="raffle_id" value="<?php echo esc_attr($raffle->id); ?>">
            <button type="submit" class="button button-primary" <?php disabled(empty($participants)); ?>><?php esc_html_e('Losuj z całej listy', 'conference-manager'); ?></button>
            <a class="button" href="<?php echo esc_url($presentation_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Otwórz animowany ekran', 'conference-manager'); ?></a>
        </form>
        <h3><?php esc_html_e('Historia losowań', 'conference-manager'); ?></h3>
        <?php if (empty($draws)): ?><p><?php esc_html_e('Nie wykonano jeszcze losowania.', 'conference-manager'); ?></p><?php else: ?>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e('Zwycięzca', 'conference-manager'); ?></th><th><?php esc_html_e('Data', 'conference-manager'); ?></th></tr></thead><tbody>
            <?php foreach ($draws as $draw): ?><tr><td><?php echo esc_html($draw->first_name . ' ' . $draw->last_name); ?></td><td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $draw->drawn_at)); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    <?php endif; ?>
</div>
<style>.cm-raffle-create-form,.cm-raffle-test-participants-form,.cm-raffle-icon-upload-form{margin:20px 0;padding:16px;background:#f6f7f7}.cm-raffle-create-form label{margin-right:8px;font-weight:600}.cm-raffle-test-participants-form .description{margin-left:8px}.cm-raffle-manager hr{margin:28px 0}.cm-raffle-manager table{max-width:720px}.cm-raffle-icon-grid{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0}.cm-raffle-icon-card{width:100px;padding:8px;border:1px solid #dcdcde;background:#fff;text-align:center}.cm-raffle-icon-card img,.cm-raffle-participant-icon{display:block;width:64px;height:64px;object-fit:contain;margin:0 auto 8px}.cm-raffle-participant-icon{width:32px;height:32px;margin:0}</style>
