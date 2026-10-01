<?php
/** Full-screen raffle presentation, opened from an event schedule item. */
if (!defined('WPINC')) {
    die;
}

get_header();
?>
<?php echo $event->get_custom_css_style_tag('raffle'); ?>
<main class="cm-raffle-presentation cm-event-theme cm-event-theme-<?php echo esc_attr($event->get_id()); ?>">
    <?php echo CM_Public::render_raffle_presentation_card($raffle, $event); ?>
</main>
<?php get_footer(); ?>
