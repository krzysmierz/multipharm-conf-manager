<?php

/**
 * Events list template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Wydarzenia</h1>
    <a href="#" class="page-title-action" id="add-new-event">Dodaj nowy</a>
    
    <hr class="wp-header-end">

    <!-- Add/Edit Event Form (Initially Hidden) -->
    <div id="event-form-container" class="bg-white p-6 rounded-lg shadow-sm border mt-6" style="display: none;">
        <h2 id="form-title">Nowe wydarzenie</h2>
        <form method="post" id="event-form">
            <?php wp_nonce_field('cm_event_form'); ?>
            <input type="hidden" name="action" value="cm_save_event">
            <input type="hidden" id="event_id" name="event_id" value="">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="title">Tytuł wydarzenia</label>
                    </th>
                    <td>
                        <input type="text" id="title" name="title" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description">Opis</label>
                    </th>
                    <td>
                        <textarea id="description" name="description" rows="5" class="large-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="event_date">Data wydarzenia</label>
                    </th>
                    <td>
                        <input type="date" id="event_date" name="event_date" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="start_time">Godzina rozpoczęcia</label>
                    </th>
                    <td>
                        <input type="time" id="start_time" name="start_time">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="end_time">Godzina zakończenia</label>
                    </th>
                    <td>
                        <input type="time" id="end_time" name="end_time">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="status">Status</label>
                    </th>
                    <td>
                        <select id="status" name="status">
                            <option value="draft">Szkic</option>
                            <option value="active">Aktywny</option>
                            <option value="paused">Wstrzymany</option>
                            <option value="completed">Zakończony</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="Zapisz wydarzenie">
                <button type="button" class="button" id="cancel-event-form">Anuluj</button>
            </p>
        </form>
    </div>

    <!-- Events Table -->
    <div class="bg-white rounded-lg shadow-sm border mt-6">
        <?php if (!empty($events)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title">Tytuł</th>
                        <th scope="col" class="manage-column">Data</th>
                        <th scope="col" class="manage-column">Status</th>
                        <th scope="col" class="manage-column">Utworzono</th>
                        <th scope="col" class="manage-column">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="column-title">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=conference-manager-events&action=edit&event_id=' . $event->id); ?>">
                                        <?php echo esc_html($event->title); ?>
                                    </a>
                                </strong>
                                <?php if ($event->description): ?>
                                    <div class="row-excerpt">
                                        <?php echo esc_html(wp_trim_words($event->description, 15)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                echo esc_html($event->event_date);
                                if ($event->start_time) {
                                    echo '<br><small>' . esc_html($event->start_time);
                                    if ($event->end_time) {
                                        echo ' - ' . esc_html($event->end_time);
                                    }
                                    echo '</small>';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $event->status; ?>">
                                    <?php 
                                    $status_labels = array(
                                        'draft' => 'Szkic',
                                        'active' => 'Aktywny',
                                        'paused' => 'Wstrzymany',
                                        'completed' => 'Zakończony'
                                    );
                                    echo $status_labels[$event->status] ?? ucfirst($event->status);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html(date('Y-m-d H:i', strtotime($event->created_at))); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=conference-manager-events&action=edit&event_id=' . $event->id); ?>" 
                                   class="button button-small">
                                    Edytuj
                                </a>
                                <button class="button button-small edit-event-btn" 
                                        data-event-id="<?php echo $event->id; ?>"
                                        data-title="<?php echo esc_attr($event->title); ?>"
                                        data-description="<?php echo esc_attr($event->description); ?>"
                                        data-event-date="<?php echo esc_attr($event->event_date); ?>"
                                        data-start-time="<?php echo esc_attr($event->start_time); ?>"
                                        data-end-time="<?php echo esc_attr($event->end_time); ?>"
                                        data-status="<?php echo esc_attr($event->status); ?>">
                                    Szybka edycja
                                </button>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=conference-manager-events&action=cm_delete_event&event_id=' . $event->id), 'delete_event_' . $event->id); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('Czy na pewno chcesz usunąć to wydarzenie?');">
                                    Usuń
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-8 text-center">
                <span class="dashicons dashicons-calendar-alt text-gray-400" style="font-size: 64px; width: 64px; height: 64px;"></span>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Brak wydarzeń</h3>
                <p class="text-gray-500 mb-4">Zacznij od utworzenia pierwszego wydarzenia konferencyjnego.</p>
                <button class="button-primary" id="add-first-event">Utwórz pierwsze wydarzenie</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-draft {
    background-color: #fef3c7;
    color: #92400e;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-paused {
    background-color: #fed7aa;
    color: #9a3412;
}

.status-completed {
    background-color: #f3f4f6;
    color: #374151;
}

.row-excerpt {
    margin-top: 4px;
    color: #666;
    font-size: 13px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addNewBtn = document.getElementById('add-new-event');
    const addFirstBtn = document.getElementById('add-first-event');
    const formContainer = document.getElementById('event-form-container');
    const form = document.getElementById('event-form');
    const cancelBtn = document.getElementById('cancel-event-form');
    const formTitle = document.getElementById('form-title');
    const editBtns = document.querySelectorAll('.edit-event-btn');

    // Show form for new event
    function showNewEventForm() {
        formTitle.textContent = 'Nowe wydarzenie';
        form.reset();
        document.getElementById('event_id').value = '';
        formContainer.style.display = 'block';
        document.getElementById('title').focus();
    }

    // Show form for editing event
    function showEditEventForm(eventData) {
        formTitle.textContent = 'Edytuj wydarzenie';
        document.getElementById('event_id').value = eventData.id;
        document.getElementById('title').value = eventData.title;
        document.getElementById('description').value = eventData.description;
        document.getElementById('event_date').value = eventData.eventDate;
        document.getElementById('start_time').value = eventData.startTime;
        document.getElementById('end_time').value = eventData.endTime;
        document.getElementById('status').value = eventData.status;
        formContainer.style.display = 'block';
        document.getElementById('title').focus();
    }

    // Hide form
    function hideForm() {
        formContainer.style.display = 'none';
    }

    // Event listeners
    if (addNewBtn) {
        addNewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNewEventForm();
        });
    }

    if (addFirstBtn) {
        addFirstBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNewEventForm();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideForm();
        });
    }

    editBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const eventData = {
                id: this.dataset.eventId,
                title: this.dataset.title,
                description: this.dataset.description,
                eventDate: this.dataset.eventDate,
                startTime: this.dataset.startTime,
                endTime: this.dataset.endTime,
                status: this.dataset.status
            };
            showEditEventForm(eventData);
        });
    });
});
</script>