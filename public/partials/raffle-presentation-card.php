<?php
/** Reusable raffle draw component for standalone and active schedule displays. */
if (!defined('WPINC')) {
    die;
}

$winner_name = $latest_draw ? trim($latest_draw->first_name . ' ' . $latest_draw->last_name) : '—';
$winner_icon_url = $latest_draw ? CM_Raffle::get_participant_icon_url($latest_draw) : '';
?>
<section class="cm-raffle-presentation__card cm-raffle-presentation-component"
    data-raffle-id="<?php echo esc_attr((int) $raffle->id); ?>"
    data-can-draw="<?php echo $can_draw ? '1' : '0'; ?>"
    data-nonce="<?php echo esc_attr($can_draw ? wp_create_nonce('cm_draw_raffle_presentation_' . $raffle->id) : ''); ?>"
    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-drawing-message="<?php echo esc_attr__('Losowanie trwa…', 'conference-manager'); ?>"
    data-draw-again-message="<?php echo esc_attr__('Losuj ponownie', 'conference-manager'); ?>"
    data-network-error-message="<?php echo esc_attr__('Nie udało się połączyć z serwerem. Spróbuj ponownie.', 'conference-manager'); ?>">
    <p class="cm-raffle-presentation__eyebrow"><?php echo esc_html($event && $event->get_id() ? $event->get_title() : __('Losowanie', 'conference-manager')); ?></p>
    <h2 class="cm-raffle-presentation__title"><?php echo esc_html($raffle->label); ?></h2>
    <p class="cm-raffle-presentation__count">
        <?php printf(esc_html(_n('%d zarejestrowany uczestnik', '%d zarejestrowanych uczestników', count($participants), 'conference-manager')), count($participants)); ?>
    </p>

    <div class="cm-raffle-presentation__machine" aria-live="polite" aria-atomic="true">
        <span><?php esc_html_e('Wynik losowania', 'conference-manager'); ?></span>
        <img class="cm-raffle-presentation__icon" src="<?php echo esc_url($winner_icon_url); ?>" alt=""<?php echo $winner_icon_url ? '' : ' hidden'; ?>>
        <strong class="cm-raffle-presentation__name"><?php echo esc_html($winner_name); ?></strong>
    </div>
    <p class="cm-raffle-presentation__status" role="status">
        <?php if (empty($participants)): ?>
            <?php esc_html_e('Brak zarejestrowanych uczestników.', 'conference-manager'); ?>
        <?php elseif ($latest_draw): ?>
            <?php printf(esc_html__('Ostatnio wylosowano: %s.', 'conference-manager'), esc_html($winner_name)); ?>
        <?php elseif ($can_draw): ?>
            <?php esc_html_e('Naciśnij przycisk, aby rozpocząć.', 'conference-manager'); ?>
        <?php else: ?>
            <?php esc_html_e('Oczekiwanie na rozpoczęcie losowania.', 'conference-manager'); ?>
        <?php endif; ?>
    </p>

    <?php if ($can_draw): ?>
        <button class="cm-raffle-presentation__draw" type="button"<?php echo empty($participants) ? ' disabled' : ''; ?>><?php esc_html_e('Rozpocznij losowanie', 'conference-manager'); ?></button>
    <?php endif; ?>
</section>
