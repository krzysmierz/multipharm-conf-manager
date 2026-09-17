<?php

/**
 * Event preview template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$lineup_items = CM_Lineup::get_by_event_chronological($event->get_id());
$active_quizzes = CM_Quiz::get_active_by_event($event->get_id());
$qr_codes = CM_QR_Generator::get_qr_codes_by_event($event->get_id());
?>

<div class="cm-preview-tab">
    <div class="cm-tab-header">
        <h2>Podgląd wydarzenia</h2>
        <p class="description">Zobacz jak wydarzenie będzie wyglądać dla uczestników na stronie internetowej.</p>
    </div>
    
    <!-- Preview Options -->
    <div class="cm-preview-options">
        <div class="cm-preview-modes">
            <button type="button" class="cm-preview-mode active" data-mode="desktop">
                <span class="dashicons dashicons-desktop"></span>
                Desktop
            </button>
            <button type="button" class="cm-preview-mode" data-mode="tablet">
                <span class="dashicons dashicons-tablet"></span>
                Tablet
            </button>
            <button type="button" class="cm-preview-mode" data-mode="mobile">
                <span class="dashicons dashicons-smartphone"></span>
                Mobile
            </button>
        </div>
        
        <div class="cm-preview-links">
            <a href="<?php echo home_url('?cm_event=' . $event->get_id()); ?>" 
               class="button" target="_blank">
                <span class="dashicons dashicons-external"></span>
                Otwórz w nowej karcie
            </a>
        </div>
    </div>
    
    <!-- Preview Frame -->
    <div class="cm-preview-container">
        <div class="cm-preview-frame desktop" id="preview-frame">
            <iframe id="preview-iframe" 
                    src="<?php echo home_url('?cm_event=' . $event->get_id() . '&preview=1'); ?>"
                    width="100%" 
                    height="600"
                    frameborder="0"
                    style="border-radius: 8px;">
            </iframe>
        </div>
    </div>
    
    <!-- Shortcode Examples -->
    <div class="cm-shortcode-examples">
        <h3>Shortcodes do użycia w treści</h3>
        <p class="description">Skopiuj i wklej te shortcodes w treść strony lub wpisu, aby wyświetlić elementy wydarzenia.</p>
        
        <div class="cm-shortcode-grid">
            <div class="cm-shortcode-item">
                <h4>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Pełne wydarzenie
                </h4>
                <div class="cm-shortcode-code">
                    <code>[cm_event id="<?php echo $event->get_id(); ?>"]</code>
                    <button type="button" class="button button-small cm-copy-shortcode" 
                            data-shortcode='[cm_event id="<?php echo $event->get_id(); ?>"]'>
                        Kopiuj
                    </button>
                </div>
                <p class="description">Wyświetla pełne informacje o wydarzeniu wraz z harmonogramem.</p>
            </div>
            
            <div class="cm-shortcode-item">
                <h4>
                    <span class="dashicons dashicons-list-view"></span>
                    Harmonogram
                </h4>
                <div class="cm-shortcode-code">
                    <code>[cm_event_lineup event_id="<?php echo $event->get_id(); ?>"]</code>
                    <button type="button" class="button button-small cm-copy-shortcode" 
                            data-shortcode='[cm_event_lineup event_id="<?php echo $event->get_id(); ?>"]'>
                        Kopiuj
                    </button>
                </div>
                <p class="description">Wyświetla tylko harmonogram prezentacji.</p>
            </div>
            
            <div class="cm-shortcode-item">
                <h4>
                    <span class="dashicons dashicons-controls-play"></span>
                    Aktualna prezentacja
                </h4>
                <div class="cm-shortcode-code">
                    <code>[cm_current_presentation event_id="<?php echo $event->get_id(); ?>"]</code>
                    <button type="button" class="button button-small cm-copy-shortcode" 
                            data-shortcode='[cm_current_presentation event_id="<?php echo $event->get_id(); ?>"]'>
                        Kopiuj
                    </button>
                </div>
                <p class="description">Pokazuje aktualnie trwającą prezentację.</p>
            </div>
            
            <?php if (!empty($active_quizzes)): ?>
                <?php foreach ($active_quizzes as $quiz): ?>
                    <div class="cm-shortcode-item">
                        <h4>
                            <span class="dashicons dashicons-clipboard"></span>
                            Quiz: <?php echo esc_html($quiz->title); ?>
                        </h4>
                        <div class="cm-shortcode-code">
                            <code>[cm_quiz id="<?php echo $quiz->id; ?>"]</code>
                            <button type="button" class="button button-small cm-copy-shortcode" 
                                    data-shortcode='[cm_quiz id="<?php echo $quiz->id; ?>"]'>
                                Kopiuj
                            </button>
                        </div>
                        <p class="description">Wyświetla interaktywny quiz.</p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="cm-preview-stats">
        <h3>Podsumowanie wydarzenia</h3>
        
        <div class="cm-stats-grid">
            <div class="cm-stat-card">
                <div class="cm-stat-icon">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="cm-stat-content">
                    <div class="cm-stat-number"><?php echo count($lineup_items); ?></div>
                    <div class="cm-stat-label">Prezentacje</div>
                </div>
            </div>
            
            <div class="cm-stat-card">
                <div class="cm-stat-icon">
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
                <div class="cm-stat-content">
                    <div class="cm-stat-number"><?php echo count($active_quizzes); ?></div>
                    <div class="cm-stat-label">Aktywne quizy</div>
                </div>
            </div>
            
            <div class="cm-stat-card">
                <div class="cm-stat-icon">
                    <span class="dashicons dashicons-smartphone"></span>
                </div>
                <div class="cm-stat-content">
                    <div class="cm-stat-number"><?php echo count($qr_codes); ?></div>
                    <div class="cm-stat-label">Kody QR</div>
                </div>
            </div>
            
            <div class="cm-stat-card">
                <div class="cm-stat-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="cm-stat-content">
                    <div class="cm-stat-number"><?php echo ucfirst($event->get_status()); ?></div>
                    <div class="cm-stat-label">Status</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testing Tools -->
    <div class="cm-testing-tools">
        <h3>Narzędzia testowe</h3>
        
        <div class="cm-test-actions">
            <button type="button" class="button cm-test-refresh">
                <span class="dashicons dashicons-update"></span>
                Odśwież podgląd
            </button>
            
            <button type="button" class="button cm-clear-cache">
                <span class="dashicons dashicons-admin-tools"></span>
                Wyczyść cache
            </button>
            
            <?php if ($event->get_status() !== 'active'): ?>
                <button type="button" class="button button-primary cm-activate-event" 
                        data-event-id="<?php echo $event->get_id(); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                    Aktywuj wydarzenie
                </button>
            <?php else: ?>
                <button type="button" class="button cm-pause-event" 
                        data-event-id="<?php echo $event->get_id(); ?>">
                    <span class="dashicons dashicons-controls-pause"></span>
                    Wstrzymaj wydarzenie
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.cm-preview-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
}

.cm-preview-modes {
    display: flex;
    gap: 10px;
}

.cm-preview-mode {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cm-preview-mode.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.cm-preview-mode:hover:not(.active) {
    background: #f3f4f6;
}

.cm-preview-links .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cm-preview-container {
    margin-bottom: 40px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
}

.cm-preview-frame {
    margin: 0 auto;
    transition: all 0.3s ease;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cm-preview-frame.desktop {
    max-width: 100%;
}

.cm-preview-frame.tablet {
    max-width: 768px;
}

.cm-preview-frame.mobile {
    max-width: 375px;
}

.cm-shortcode-examples {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.cm-shortcode-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.cm-shortcode-item {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
}

.cm-shortcode-item h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.cm-shortcode-code {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.cm-shortcode-code code {
    flex: 1;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 8px 12px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    word-break: break-all;
}

.cm-copy-shortcode {
    white-space: nowrap;
}

.cm-preview-stats {
    margin-bottom: 30px;
}

.cm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.cm-stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.cm-stat-icon {
    width: 48px;
    height: 48px;
    background: #3b82f6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.cm-stat-content {
    flex: 1;
}

.cm-stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 5px;
}

.cm-stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.cm-testing-tools {
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 25px;
}

.cm-test-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.cm-test-actions .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 768px) {
    .cm-preview-options {
        flex-direction: column;
        gap: 20px;
    }
    
    .cm-preview-modes {
        width: 100%;
        justify-content: center;
    }
    
    .cm-shortcode-grid {
        grid-template-columns: 1fr;
    }
    
    .cm-shortcode-code {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cm-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .cm-test-actions {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewFrame = document.getElementById('preview-frame');
    const previewIframe = document.getElementById('preview-iframe');
    
    // Preview mode switching
    document.querySelectorAll('.cm-preview-mode').forEach(button => {
        button.addEventListener('click', function() {
            const mode = this.dataset.mode;
            
            // Update active button
            document.querySelectorAll('.cm-preview-mode').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update frame class
            previewFrame.className = 'cm-preview-frame ' + mode;
            
            // Adjust iframe height for mobile
            if (mode === 'mobile') {
                previewIframe.style.height = '800px';
            } else {
                previewIframe.style.height = '600px';
            }
        });
    });
    
    // Copy shortcode
    document.querySelectorAll('.cm-copy-shortcode').forEach(button => {
        button.addEventListener('click', function() {
            const shortcode = this.dataset.shortcode;
            navigator.clipboard.writeText(shortcode).then(function() {
                const originalText = button.textContent;
                button.textContent = 'Skopiowano!';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2000);
            });
        });
    });
    
    // Refresh preview
    document.querySelector('.cm-test-refresh').addEventListener('click', function() {
        previewIframe.src = previewIframe.src;
    });
    
    // Clear cache
    document.querySelector('.cm-clear-cache').addEventListener('click', function() {
        // Add cache-busting parameter
        const currentSrc = previewIframe.src;
        const separator = currentSrc.includes('?') ? '&' : '?';
        previewIframe.src = currentSrc + separator + '_cb=' + Date.now();
    });
    
    // Activate/pause event
    document.querySelectorAll('.cm-activate-event, .cm-pause-event').forEach(button => {
        button.addEventListener('click', function() {
            const eventId = this.dataset.eventId;
            const isActivate = this.classList.contains('cm-activate-event');
            const newStatus = isActivate ? 'active' : 'paused';
            
            const confirmMessage = isActivate 
                ? 'Czy na pewno chcesz aktywować to wydarzenie?' 
                : 'Czy na pewno chcesz wstrzymać to wydarzenie?';
                
            if (!confirm(confirmMessage)) return;
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_change_event_status',
                    event_id: eventId,
                    status: newStatus,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                }
            });
        });
    });
    
    // Auto-refresh preview every 30 seconds if event is active
    <?php if ($event->get_status() === 'active'): ?>
        setInterval(function() {
            const currentSrc = previewIframe.src;
            if (!currentSrc.includes('_ar=')) {
                const separator = currentSrc.includes('?') ? '&' : '?';
                previewIframe.src = currentSrc + separator + '_ar=' + Date.now();
            }
        }, 30000);
    <?php endif; ?>
});
</script>