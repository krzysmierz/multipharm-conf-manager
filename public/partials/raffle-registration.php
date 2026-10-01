<?php
/** Public raffle registration page. */
if (!defined('WPINC')) {
    die;
}

$registered = isset($_GET['cm_raffle_status']) && is_scalar($_GET['cm_raffle_status']) && $_GET['cm_raffle_status'] === 'registered';
$posted_first_name = isset($_POST['first_name']) && is_scalar($_POST['first_name']) ? wp_unslash($_POST['first_name']) : '';
$posted_last_name = isset($_POST['last_name']) && is_scalar($_POST['last_name']) ? wp_unslash($_POST['last_name']) : '';
$posted_icon_id = isset($_POST['icon_id']) && is_scalar($_POST['icon_id']) ? absint($_POST['icon_id']) : 0;
$event = new CM_Event($raffle->event_id);
$home_url = home_url('/');
$presentation_url = CM_Raffle::get_presentation_url($raffle);
get_header();
?>
<main class="cm-raffle-registration cm-event-theme cm-event-theme-<?php echo esc_attr($event->get_id()); ?>" aria-labelledby="cm-raffle-title">
    <div class="cm-raffle-registration__card">
        <h1 id="cm-raffle-title"><?php echo esc_html($raffle->label); ?></h1>
        <?php if ($event->get_id()): ?>
            <p class="cm-raffle-registration__event"><?php echo esc_html($event->get_title()); ?></p>
        <?php endif; ?>

        <?php if ($registered): ?>
            <div class="cm-raffle-notice cm-raffle-notice--success" role="status" data-cm-raffle-registration-success data-cm-redirect-url="<?php echo esc_url($home_url); ?>">
                <?php esc_html_e('Dziękujemy. Twoje zgłoszenie zostało zapisane.', 'conference-manager'); ?>
            </div>
            <p class="cm-raffle-registration__next-step">
                <a class="cm-raffle-registration__presentation-link" href="<?php echo esc_url($presentation_url); ?>" data-cm-raffle-presentation-link><?php esc_html_e('Strona losowania', 'conference-manager'); ?></a>
            </p>
        <?php elseif ($registration_error !== ''): ?>
            <div class="cm-raffle-notice cm-raffle-notice--error" role="alert">
                <?php echo esc_html($registration_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$registered): ?>
            <form method="post" class="cm-raffle-form">
                <?php wp_nonce_field('cm_raffle_registration_' . $raffle->token); ?>
                <input type="hidden" name="cm_raffle_action" value="register">
                <?php if (!empty($icons)): ?>
                    <fieldset class="cm-raffle-icons">
                        <legend><?php esc_html_e('Wybierz swoją ikonę', 'conference-manager'); ?></legend>
                        <div class="cm-raffle-icons__slider" data-cm-avatar-slider>
                            <button class="cm-raffle-icons__control" type="button" data-cm-avatar-previous hidden aria-label="<?php esc_attr_e('Poprzednie ikony', 'conference-manager'); ?>" aria-controls="cm-raffle-icons-track">
                                <span aria-hidden="true">&#8249;</span>
                            </button>
                            <div class="cm-raffle-icons__viewport">
                                <div id="cm-raffle-icons-track" class="cm-raffle-icons__track" tabindex="0" aria-label="<?php esc_attr_e('Dostępne ikony uczestników', 'conference-manager'); ?>" aria-describedby="cm-raffle-icons-help">
                                    <?php foreach ($icons as $index => $icon): ?>
                                        <label class="cm-raffle-icon-choice">
                                            <input type="radio" name="icon_id" value="<?php echo esc_attr($icon->id); ?>" required aria-label="<?php echo esc_attr(sprintf(__('Ikona uczestnika %d', 'conference-manager'), $index + 1)); ?>" <?php checked($posted_icon_id, $icon->id); ?>>
                                            <img src="<?php echo esc_url(CM_Raffle::get_icon_url($icon)); ?>" alt="" width="256" height="256" <?php echo $index < 2 ? '' : 'loading="lazy"'; ?>>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="cm-raffle-icons__control" type="button" data-cm-avatar-next hidden aria-label="<?php esc_attr_e('Następne ikony', 'conference-manager'); ?>" aria-controls="cm-raffle-icons-track">
                                <span aria-hidden="true">&#8250;</span>
                            </button>
                            <p id="cm-raffle-icons-help" class="cm-raffle-screen-reader-text"><?php esc_html_e('Użyj strzałek w lewo i w prawo, aby zmienić ikonę.', 'conference-manager'); ?></p>
                            <p class="cm-raffle-screen-reader-text" data-cm-avatar-selection aria-live="polite"></p>
                        </div>
                    </fieldset>
                <?php endif; ?>
                <p>
                    <label for="cm-raffle-first-name"><?php esc_html_e('Imię', 'conference-manager'); ?></label>
                    <input id="cm-raffle-first-name" name="first_name" type="text" required minlength="2" maxlength="100" autocomplete="given-name" value="<?php echo esc_attr($posted_first_name); ?>">
                </p>
                <p>
                    <label for="cm-raffle-last-name"><?php esc_html_e('Nazwisko', 'conference-manager'); ?></label>
                    <input id="cm-raffle-last-name" name="last_name" type="text" required minlength="2" maxlength="100" autocomplete="family-name" value="<?php echo esc_attr($posted_last_name); ?>">
                </p>
                <p><button type="submit"><?php esc_html_e('Zarejestruj się', 'conference-manager'); ?></button></p>
            </form>
        <?php endif; ?>
    </div>
</main>
<style>
.cm-raffle-registration{max-width:620px;margin:3rem auto;padding:0 1rem}.cm-raffle-registration__card{padding:2rem;background:#fff;border:1px solid #dcdcde;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.06)}.cm-raffle-registration__event{color:#50575e}.cm-raffle-form label{display:block;margin-bottom:.4rem;font-weight:600}.cm-raffle-form input:not([type=radio]){box-sizing:border-box;width:100%;padding:.65rem;font-size:1rem}.cm-raffle-form button,.cm-raffle-registration__presentation-link{display:inline-block;padding:.7rem 1.1rem;background:#2271b1;border:0;border-radius:3px;color:#fff;font-size:1rem;cursor:pointer;text-decoration:none}.cm-raffle-notice{margin:1rem 0;padding:1rem;border-radius:4px}.cm-raffle-notice--success{background:#edfaef;color:#146c2e}.cm-raffle-notice--error{background:#fcf0f1;color:#8a2424}.cm-raffle-registration__next-step{text-align:center}.cm-raffle-registration__presentation-link:hover,.cm-raffle-registration__presentation-link:focus{background:#135e96;color:#fff}.cm-raffle-icons{margin:1.5rem 0;padding:0;border:0}.cm-raffle-icons__slider{display:grid;gap:.5rem;align-items:center}.cm-raffle-icons__slider.is-enhanced{grid-template-columns:auto minmax(0,1fr) auto}.cm-raffle-icons__viewport{grid-column:1/-1;justify-self:center;min-width:0;width:min(100%,clamp(160px,42vw,256px));aspect-ratio:1;overflow:hidden;border-radius:50%;padding:0}.cm-raffle-icons__viewport:focus-within{outline:3px solid #72aee6;outline-offset:3px}.cm-raffle-icons__slider.is-enhanced .cm-raffle-icons__viewport{grid-column:auto}.cm-raffle-icons__track{display:flex;min-width:0;height:100%;touch-action:pan-y}.cm-raffle-icons__track.is-animating{transition:transform .28s ease}.cm-raffle-form .cm-raffle-icons__control{box-sizing:border-box;display:grid;place-items:center;width:2.5rem;height:2.5rem;padding:0;border:1px solid #2271b1;border-radius:50%;background:#fff;color:#2271b1;font-size:2rem;line-height:1;cursor:pointer}.cm-raffle-form .cm-raffle-icons__control[hidden]{display:none}.cm-raffle-form .cm-raffle-icons__control:hover{background:#edf6fc}.cm-raffle-form .cm-raffle-icon-choice{position:relative;display:grid;flex:0 0 100%;place-items:center;margin:0!important;min-width:0;cursor:pointer}.cm-raffle-icon-choice input{position:absolute;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;clip-path:inset(50%)}.cm-raffle-icon-choice img{display:block;width:100%;height:100%;object-fit:contain}.cm-raffle-icon-choice input:focus-visible+img,.cm-raffle-icons__track:focus-visible,.cm-raffle-icons__control:focus-visible,.cm-raffle-registration__presentation-link:focus-visible{outline:3px solid #72aee6;outline-offset:3px}.cm-raffle-form>p:last-child{text-align:center}.cm-raffle-form>p:last-child button{background:#d63384}.cm-raffle-form>p:last-child button:hover,.cm-raffle-form>p:last-child button:focus{background:#b5236d}.cm-raffle-screen-reader-text{position:absolute;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;clip-path:inset(50%)}@media (max-width:420px){.cm-raffle-registration__card{padding:1.25rem}.cm-raffle-icons__slider.is-enhanced{grid-template-columns:2.25rem minmax(0,1fr) 2.25rem}.cm-raffle-form .cm-raffle-icons__control{width:2.25rem;height:2.25rem}}@media (prefers-reduced-motion:reduce){.cm-raffle-icons__track.is-animating{transition:none}}
</style>
<?php echo $event->get_custom_css_style_tag('raffle'); ?>
<?php get_footer(); ?>
