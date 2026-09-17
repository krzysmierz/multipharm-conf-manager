<?php

/**
 * QR codes manager template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$qr_codes = CM_QR_Generator::get_qr_codes_by_event($event->get_id());
$lineup_items = CM_Lineup::get_by_event($event->get_id());
$quizzes = CM_Quiz::get_by_event($event->get_id());
?>

<div class="cm-qr-codes-tab">
    <div class="cm-tab-header">
        <h2>Kody QR</h2>
        <p class="description">Generuj kody QR dla łatwego dostępu do wydarzenia, quizów i prezentacji.</p>
    </div>
    
    <!-- QR Generation Section -->
    <div class="cm-qr-generator">
        <h3>Generuj nowy kod QR</h3>
        
        <div class="cm-qr-options">
            <div class="cm-qr-option" data-type="event">
                <div class="cm-qr-option-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="cm-qr-option-content">
                    <h4>Kod QR wydarzenia</h4>
                    <p>Umożliwia dostęp do głównej strony wydarzenia z harmonogramem i informacjami.</p>
                    <button type="button" class="button button-primary cm-generate-qr" 
                            data-type="event" data-target-id="<?php echo $event->get_id(); ?>">
                        Generuj kod QR wydarzenia
                    </button>
                </div>
            </div>
            
            <?php if (!empty($quizzes)): ?>
                <div class="cm-qr-option" data-type="quiz">
                    <div class="cm-qr-option-icon">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="cm-qr-option-content">
                        <h4>Kod QR quizu</h4>
                        <p>Bezpośredni dostęp do wybranego quizu.</p>
                        <div class="cm-quiz-selector">
                            <select id="quiz-selector" class="regular-text">
                                <option value="">Wybierz quiz</option>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <option value="<?php echo $quiz->id; ?>">
                                        <?php echo esc_html($quiz->title); ?>
                                        <?php echo $quiz->is_active ? ' (aktywny)' : ' (nieaktywny)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button button-primary cm-generate-quiz-qr" disabled>
                                Generuj kod QR quizu
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($lineup_items)): ?>
                <div class="cm-qr-option" data-type="presentation">
                    <div class="cm-qr-option-icon">
                        <span class="dashicons dashicons-slides"></span>
                    </div>
                    <div class="cm-qr-option-content">
                        <h4>Kod QR prezentacji</h4>
                        <p>Bezpośredni dostęp do wybranej prezentacji.</p>
                        <div class="cm-presentation-selector">
                            <select id="presentation-selector" class="regular-text">
                                <option value="">Wybierz prezentację</option>
                                <?php foreach ($lineup_items as $item): ?>
                                    <option value="<?php echo $item->id; ?>">
                                        <?php echo esc_html($item->title); ?>
                                        (<?php echo esc_html($item->start_time); ?>)
                                        <?php echo $item->is_active ? ' - NA ŻYWO' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button button-primary cm-generate-presentation-qr" disabled>
                                Generuj kod QR prezentacji
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Existing QR Codes -->
    <div class="cm-existing-qr-codes">
        <h3>Wygenerowane kody QR</h3>
        
        <?php if (empty($qr_codes)): ?>
            <div class="cm-no-qr-codes">
                <div class="cm-empty-icon">
                    <span class="dashicons dashicons-smartphone"></span>
                </div>
                <h4>Brak kodów QR</h4>
                <p>Użyj opcji powyżej, aby wygenerować pierwszy kod QR dla tego wydarzenia.</p>
            </div>
        <?php else: ?>
            <div class="cm-qr-codes-grid">
                <?php foreach ($qr_codes as $qr): ?>
                    <div class="cm-qr-code-item" data-qr-id="<?php echo $qr->id; ?>">
                        <div class="cm-qr-code-preview">
                            <?php 
                            $qr_url = CM_QR_Generator::get_qr_url($qr->file_path);
                            if (!empty($qr_url)): 
                            ?>
                                <img src="<?php echo esc_url($qr_url); ?>" 
                                     alt="QR Code" class="cm-qr-image">
                            <?php else: ?>
                                <div class="cm-qr-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>Brak pliku QR</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cm-qr-code-info">
                            <div class="cm-qr-type-badge type-<?php echo $qr->code_type; ?>">
                                <?php
                                switch ($qr->code_type) {
                                    case 'event':
                                        echo '<span class="dashicons dashicons-calendar-alt"></span> Wydarzenie';
                                        break;
                                    case 'quiz':
                                        echo '<span class="dashicons dashicons-clipboard"></span> Quiz';
                                        break;
                                    case 'presentation':
                                        echo '<span class="dashicons dashicons-slides"></span> Prezentacja';
                                        break;
                                }
                                ?>
                            </div>
                            
                            <div class="cm-qr-target">
                                <?php
                                switch ($qr->code_type) {
                                    case 'event':
                                        echo '<strong>' . esc_html($event->get_title()) . '</strong>';
                                        break;
                                    case 'quiz':
                                        $quiz = CM_Database::get_row('quizzes', array('id' => $qr->target_id));
                                        echo $quiz ? '<strong>' . esc_html($quiz->title) . '</strong>' : 'Quiz usunięty';
                                        break;
                                    case 'presentation':
                                        $presentation = CM_Database::get_row('lineup', array('id' => $qr->target_id));
                                        echo $presentation ? '<strong>' . esc_html($presentation->title) . '</strong>' : 'Prezentacja usunięta';
                                        break;
                                }
                                ?>
                            </div>
                            
                            <div class="cm-qr-url">
                                <small><?php echo esc_html($qr->qr_data); ?></small>
                            </div>
                            
                            <div class="cm-qr-created">
                                <small>Utworzono: <?php echo date('d.m.Y H:i', strtotime($qr->created_at)); ?></small>
                            </div>
                        </div>
                        
                        <div class="cm-qr-actions">
                            <?php if (!empty($qr_url)): ?>
                                <a href="<?php echo esc_url($qr_url); ?>" 
                                   class="button button-small" download target="_blank">
                                    <span class="dashicons dashicons-download"></span>
                                    Pobierz
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="button button-small cm-copy-qr-url" 
                                    data-url="<?php echo esc_attr($qr->qr_data); ?>">
                                <span class="dashicons dashicons-admin-links"></span>
                                Kopiuj URL
                            </button>
                            
                            <button type="button" class="button button-small cm-regenerate-qr" 
                                    data-qr-id="<?php echo $qr->id; ?>">
                                <span class="dashicons dashicons-update"></span>
                                Regeneruj
                            </button>
                            
                            <button type="button" class="button button-small button-link-delete cm-delete-qr" 
                                    data-qr-id="<?php echo $qr->id; ?>">
                                <span class="dashicons dashicons-trash"></span>
                                Usuń
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- QR Settings -->
    <div class="cm-qr-settings">
        <h3>Ustawienia kodów QR</h3>
        <form method="post" class="cm-qr-settings-form">
            <?php wp_nonce_field('cm_qr_settings'); ?>
            <input type="hidden" name="action" value="cm_save_qr_settings">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="qr_size">Rozmiar kodu QR (px)</label>
                    </th>
                    <td>
                        <input type="number" id="qr_size" name="qr_size" 
                               value="<?php echo get_option('cm_qr_size', 200); ?>" 
                               min="100" max="1000" step="10" class="small-text">
                        <p class="description">Rozmiar generowanych kodów QR w pikselach.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="Zapisz ustawienia">
            </p>
        </form>
    </div>
</div>

<style>
.cm-qr-generator {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.cm-qr-options {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.cm-qr-option {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.cm-qr-option:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.cm-qr-option-icon {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    background: #3b82f6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.cm-qr-option-content {
    flex: 1;
}

.cm-qr-option-content h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
}

.cm-qr-option-content p {
    margin: 0 0 15px 0;
    color: #6b7280;
}

.cm-quiz-selector,
.cm-presentation-selector {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.cm-no-qr-codes {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
}

.cm-no-qr-codes .cm-empty-icon .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.cm-qr-codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.cm-qr-code-item {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
}

.cm-qr-code-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cm-qr-code-preview {
    margin-bottom: 15px;
}

.cm-qr-image {
    max-width: 150px;
    height: auto;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
}

.cm-qr-placeholder {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.cm-qr-placeholder .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    margin-bottom: 8px;
}

.cm-qr-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.cm-qr-type-badge.type-event {
    background: #dbeafe;
    color: #1e40af;
}

.cm-qr-type-badge.type-quiz {
    background: #fef3c7;
    color: #d97706;
}

.cm-qr-type-badge.type-presentation {
    background: #f3e8ff;
    color: #7c3aed;
}

.cm-qr-target {
    margin-bottom: 8px;
    font-size: 14px;
    color: #1f2937;
}

.cm-qr-url {
    margin-bottom: 8px;
    word-break: break-all;
}

.cm-qr-created {
    margin-bottom: 15px;
    color: #9ca3af;
}

.cm-qr-actions {
    display: flex;
    gap: 5px;
    justify-content: center;
    flex-wrap: wrap;
}

.cm-qr-settings {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 25px;
    margin-top: 30px;
}

@media (max-width: 768px) {
    .cm-qr-option {
        flex-direction: column;
        text-align: center;
    }
    
    .cm-quiz-selector,
    .cm-presentation-selector {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cm-qr-codes-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable/disable quiz QR button
    const quizSelector = document.getElementById('quiz-selector');
    if (quizSelector) {
        quizSelector.addEventListener('change', function() {
            const button = document.querySelector('.cm-generate-quiz-qr');
            if (button) {
                button.disabled = !this.value;
            }
        });
    }
    
    // Enable/disable presentation QR button
    const presentationSelector = document.getElementById('presentation-selector');
    if (presentationSelector) {
        presentationSelector.addEventListener('change', function() {
            const button = document.querySelector('.cm-generate-presentation-qr');
            if (button) {
                button.disabled = !this.value;
            }
        });
    }
    
    // Generate QR codes
    document.querySelectorAll('.cm-generate-qr, .cm-generate-quiz-qr, .cm-generate-presentation-qr').forEach(button => {
        button.addEventListener('click', function() {
            let type, targetId;
            
            if (this.classList.contains('cm-generate-qr')) {
                type = this.dataset.type;
                targetId = this.dataset.targetId;
            } else if (this.classList.contains('cm-generate-quiz-qr')) {
                type = 'quiz';
                targetId = document.getElementById('quiz-selector').value;
            } else {
                type = 'presentation';
                targetId = document.getElementById('presentation-selector').value;
            }
            
            if (!targetId) return;
            
            const originalText = this.textContent;
            this.textContent = 'Generowanie...';
            this.disabled = true;
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_generate_qr',
                    type: type,
                    target_id: targetId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Błąd: ' + (response.data || 'Nie udało się wygenerować kodu QR'));
                    }
                },
                complete: function() {
                    button.textContent = originalText;
                    button.disabled = false;
                }
            });
        });
    });
    
    // Copy QR URL
    document.querySelectorAll('.cm-copy-qr-url').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(function() {
                const originalText = button.textContent;
                button.textContent = 'Skopiowano!';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2000);
            });
        });
    });
    
    // Delete QR code
    document.querySelectorAll('.cm-delete-qr').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Czy na pewno chcesz usunąć ten kod QR?')) return;
            
            const qrId = this.dataset.qrId;
            const qrItem = this.closest('.cm-qr-code-item');
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_delete_qr',
                    qr_id: qrId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        qrItem.remove();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                }
            });
        });
    });
    
    // Regenerate QR code
    document.querySelectorAll('.cm-regenerate-qr').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Czy na pewno chcesz regenerować ten kod QR?')) return;
            
            const qrId = this.dataset.qrId;
            const originalText = this.textContent;
            
            this.textContent = 'Regenerowanie...';
            this.disabled = true;
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_regenerate_qr',
                    qr_id: qrId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                },
                complete: function() {
                    button.textContent = originalText;
                    button.disabled = false;
                }
            });
        });
    });
});
</script>