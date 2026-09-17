<?php
/**
 * Public quiz display template with SSE support
 * 
 * @package    ConferenceManager
 * @subpackage ConferenceManager/public/partials
 */

if (!defined('WPINC')) {
    die;
}

// Get template variables
$vars = CM_Public::get_template_vars();
$quiz = $vars['quiz'];
$questions = $vars['questions'];

// Get event ID - SSE will determine display mode
$event_id = $quiz->get_event_id();

get_header();
?>

<div class="cm-quiz-container"
     data-quiz-id="<?php echo esc_attr($quiz->get_id()); ?>"
     data-event-id="<?php echo esc_attr($event_id); ?>">
     
    <!-- Connection Status -->
    <div class="connection-status-bar">
        <span id="connection-status" class="disconnected">Łączenie...</span>
    </div>
    
    <div class="cm-quiz-header">
        <h1 class="cm-quiz-title"><?php echo esc_html($quiz->get_title()); ?></h1>
        
        <?php if ($quiz->get_description()): ?>
            <div class="cm-quiz-description">
                <?php echo wp_kses_post($quiz->get_description()); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main display container - content will be dynamically updated -->
    <div id="quiz-display-container" class="quiz-display-dynamic">
        <!-- Content will be populated by JavaScript based on current mode -->
    </div>

    <!-- Fallback content for when JavaScript is disabled -->
    <noscript>
        <div class="no-js-message">
            <h2>JavaScript wymagany</h2>
            <p>Ta strona wymaga włączonego JavaScript do prawidłowego funkcjonowania live updates.</p>
            <p>Jeśli nie możesz włączyć JavaScript, odśwież stronę za kilka minut.</p>
        </div>
    </noscript>

    <!-- Static fallback based on current mode -->
    <div class="static-fallback" style="display: none;">
        <?php if ($display_mode === 'qr'): ?>
            <div class="qr-fallback">
                <h2>Skanuj kod QR, aby dołączyć do quizu</h2>
                <div class="qr-placeholder">
                    <p>Kod QR będzie dostępny w trybie dynamicznym</p>
                </div>
            </div>
        <?php else: ?>
            <div class="results-fallback">
                <h2>Wyniki quizu</h2>
                <p>Wyniki są dostępne w trybie dynamicznym</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Connection Status */
.connection-status-bar {
    position: fixed;
    top: 0;
    right: 20px;
    z-index: 1000;
    padding: 10px 15px;
    font-size: 12px;
    border-radius: 0 0 8px 8px;
    transition: all 0.3s ease;
}

#connection-status.connected {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#connection-status.disconnected {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Dynamic Quiz Display */
.cm-quiz-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.quiz-display-dynamic {
    min-height: 400px;
    transition: opacity 0.3s ease;
}

/* Mode-specific styles */
.quiz-mode-container {
    padding: 20px;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
}

.mode-header {
    text-align: center;
    margin-bottom: 30px;
}

.mode-header h2 {
    color: #2c3e50;
    font-size: 2rem;
    margin-bottom: 10px;
}

/* QR Mode Styles */
.qr-mode {
    background: linear-gradient(135deg, #DE53E0 0%, #BF53ED 100%);
    color: white;
}

.qr-mode .mode-header h2 {
    color: white;
}

.qr-code-display {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
    margin: 30px 0;
}

.qr-placeholder, .qr-code {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.qr-loading {
    color: #666;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.qr-image {
    max-width: 250px;
    width: 100%;
    height: auto;
    border-radius: 8px;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.qr-url-info {
    margin-top: 20px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.qr-url-info small {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.quiz-url {
    color: #667eea;
    font-weight: 600;
    word-break: break-all;
    font-family: monospace;
    background: rgba(102, 126, 234, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
}

.qr-error {
    color: #dc3545;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.qr-error p {
    margin: 0 0 10px 0;
}

.qr-error p:last-child {
    margin: 0;
}

.mode-instructions {
    text-align: center;
    font-size: 1.1rem;
    opacity: 0.9;
    margin-top: 20px;
}

/* Results Mode Styles */
.results-mode {
    background: #f8f9fa;
}

/* Participants Section */
.participants-section {
    margin: 30px 0;
}

.participants-section h3 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin-bottom: 20px;
    text-align: center;
}

.participants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.participant-block {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.participant-block:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.participant-block.top-scorer {
    border-color: #ffd700;
    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
    position: relative;
}

.participant-block.top-scorer::before {
    content: "👑";
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 1.2rem;
}

.participant-rank {
    background: #667eea;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 8px 12px;
    border-radius: 20px;
    min-width: 40px;
    text-align: center;
}

.participant-block.top-scorer .participant-rank {
    background: #ffd700;
    color: #333;
}

.participant-info {
    flex: 1;
}

.participant-id {
    font-size: 1.3rem;
    color: #000;
    font-family: monospace;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 4px;
}

.participant-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.7rem;
}

.participant-stats {
    text-align: right;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.participant-score {
    font-weight: 700;
    color: #667eea;
    font-size: 1.1rem;
}

.participant-percentage {
    color: #28a745;
    font-weight: 600;
    font-size: 1rem;
}

.participant-time {
    color: #6c757d;
    font-size: 0.85rem;
}

/* Questions Section */
.questions-section {
    margin: 30px 0;
}

.questions-section h3 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin-bottom: 20px;
    text-align: center;
}

.results-display {
    min-height: 300px;
}

.results-loading {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.results-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid #e9ecef;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    display: block;
    margin-bottom: 8px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.question-result {
    margin-bottom: 25px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.question-result h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.answers-stats {
    display: grid;
    gap: 10px;
}

.answer-stat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.answer-stat.correct {
    border-left: 4px solid #28a745;
    background: #f8fff8;
}

.answer-text {
    flex: 1;
    margin-right: 15px;
    color: #2c3e50;
}

.answer-stats {
    display: flex;
    align-items: center;
    gap: 10px;
}

.count {
    font-weight: 600;
    color: #667eea;
}

.percentage {
    font-size: 0.9rem;
    color: #6c757d;
}

/* Notifications */
.quiz-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 1001;
    padding: 15px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    max-width: 300px;
    transform: translateX(320px);
    transition: transform 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.quiz-notification.show {
    transform: translateX(0);
}

.quiz-notification.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.quiz-notification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.quiz-notification.warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.quiz-notification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Spinner */
.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Fallback styles */
.no-js-message {
    text-align: center;
    padding: 60px 20px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    border-radius: 12px;
    margin: 20px 0;
}

.static-fallback {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

/* Error states */
.error {
    text-align: center;
    padding: 30px;
    color: #dc3545;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #6c757d;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .cm-quiz-container {
        padding: 15px;
    }

    .connection-status-bar {
        position: relative;
        top: auto;
        right: auto;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    .quiz-notification {
        top: 20px;
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100px);
    }

    .quiz-notification.show {
        transform: translateY(0);
    }

    .results-summary {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 2rem;
    }

    .participants-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .participant-block {
        padding: 12px;
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }

    .participant-info,
    .participant-stats {
        text-align: center;
        flex: none;
    }

    .participant-stats {
        flex-direction: row;
        justify-content: center;
        gap: 15px;
        margin-top: 5px;
    }

    .participant-rank {
        align-self: center;
        order: -1;
    }

    .qr-code-display {
        min-height: 250px;
    }

    .qr-placeholder, .qr-code {
        padding: 20px;
    }

    .qr-image {
        max-width: 200px;
    }

    .qr-url-info {
        padding: 10px;
        margin-top: 15px;
    }

    .quiz-url {
        font-size: 0.8rem;
        line-height: 1.3;
    }

    .mode-header h2 {
        font-size: 1.5rem;
    }
}

.cm-quiz-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;

    color: white;
    border-radius: 12px;
}

.cm-quiz-title {
    font-size: 2.5rem;
    margin: 0 0 15px 0;
    font-weight: 700;
}

.cm-quiz-description {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 20px;
}

.cm-quiz-timer {
    background: rgba(255, 255, 255, 0.2);
    padding: 15px 25px;
    border-radius: 25px;
    display: inline-block;
    font-size: 1.1rem;
    font-weight: 600;
}

.cm-timer-label {
    margin-right: 10px;
}

.cm-timer-value {
    font-weight: 700;
    font-size: 1.2rem;
}

.cm-timer-value.warning {
    color: #ff6b6b;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.cm-quiz-results-container {
    margin-top: 30px;
}

.cm-results-header {
    text-align: center;
    margin-bottom: 30px;
}

.cm-results-header h2 {
    color: #2c3e50;
    font-size: 2rem;
    margin-bottom: 10px;
}

.cm-results-subtitle {
    color: #7f8c8d;
    font-size: 1.1rem;
}

.cm-loading-results {
    text-align: center;
    padding: 60px 20px;
}

.cm-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.cm-question {
    background: white;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}

.cm-question:hover {
    border-color: #667eea;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
}

.cm-question-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.cm-question-title {
    font-size: 1.3rem;
    color: #2c3e50;
    margin: 0;
    flex: 1;
    margin-right: 15px;
}

.cm-question-number {
    background: #667eea;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
}

.cm-answers {
    display: grid;
    gap: 12px;
}

.cm-answer-option {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cm-answer-option:hover {
    background: #e9ecef;
    border-color: #667eea;
}

.cm-answer-option:has(.cm-answer-input:checked) {
    background: #e8f1ff;
    border-color: #667eea;
}

.cm-answer-input {
    margin-right: 12px;
    transform: scale(1.2);
}

.cm-answer-text {
    font-size: 1rem;
    color: #2c3e50;
}

.cm-text-answer {
    margin-top: 15px;
}

.cm-text-input {
    width: 100%;
    padding: 15px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.2s ease;
}

.cm-text-input:focus {
    outline: none;
    border-color: #667eea;
}

.cm-quiz-actions {
    text-align: center;
    margin-top: 40px;
    padding: 30px;
}

.cm-submit-quiz {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 18px 40px;
    font-size: 1.2rem;
    font-weight: 600;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.cm-submit-quiz:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.cm-submit-quiz:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.cm-quiz-success {
    text-align: center;
    padding: 60px 20px;
    background: #d4edda;
    border: 2px solid #c3e6cb;
    border-radius: 12px;
    margin-top: 30px;
}

.cm-success-icon {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 20px;
}

.cm-quiz-success h3 {
    color: #155724;
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.cm-quiz-success p {
    color: #155724;
    font-size: 1.1rem;
}

.cm-no-questions {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.cm-results-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.cm-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.cm-stat-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid #e9ecef;
}

.cm-stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    display: block;
    margin-bottom: 8px;
}

.cm-stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.cm-question-result {
    margin-bottom: 30px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.cm-question-result h4 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.2rem;
}

.cm-answer-stats {
    display: grid;
    gap: 15px;
}

.cm-answer-stat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.cm-answer-stat.correct {
    border-left: 4px solid #28a745;
    background: #f8fff8;
}

.cm-answer-text-stat {
    flex: 1;
    margin-right: 15px;
}

.cm-answer-percentage {
    font-weight: 600;
    color: #667eea;
}

.cm-answer-count {
    color: #6c757d;
    font-size: 0.9rem;
    margin-left: 10px;
}

@media (max-width: 768px) {
    .cm-quiz-container {
        padding: 15px;
    }
    
    .cm-quiz-title {
        font-size: 2rem;
    }
    
    .cm-question-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .cm-question-number {
        align-self: flex-start;
    }
    
    .cm-stats-overview {
        grid-template-columns: 1fr;
    }
    
    .cm-submit-quiz {
        width: 100%;
        padding: 15px;
    }
}
</style>

<!-- Live updates script will be loaded via wp_enqueue_script -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get quiz configuration from data attributes
    const container = document.querySelector('.cm-quiz-container');
    const quizId = parseInt(container.dataset.quizId) || 0;
    const eventId = parseInt(container.dataset.eventId) || 0;

    if (!quizId || quizId === 0 || !eventId || eventId === 0) {
        console.warn('Quiz ID or Event ID invalid - SSE disabled:', {quizId, eventId});
        return;
    }
    
    // Initialize live updates with SSE
    const liveUpdates = new QuizLiveUpdates(quizId, eventId, {
        debug: <?php echo WP_DEBUG ? 'true' : 'false'; ?>,
        reconnectDelay: 3000,
        maxReconnectAttempts: 5,
        pollFallbackInterval: 15000,
        enablePollingFallback: true
    });
    
    // Initialize display controller - SSE will set initial mode
    const displayController = new QuizDisplayController('quiz-display-container', liveUpdates);

    // Debug info
    if (<?php echo WP_DEBUG ? 'true' : 'false'; ?>) {
        console.log('Quiz Live Updates initialized:', {
            quizId: quizId,
            eventId: eventId
        });
        
        // Add admin controls for debugging (only if user can manage options)
        <?php if (current_user_can('manage_options') && WP_DEBUG): ?>
            const debugControls = document.createElement('div');
            debugControls.style.cssText = 'position: fixed; bottom: 20px; left: 20px; background: #333; color: white; padding: 15px; border-radius: 8px; z-index: 1000; font-size: 12px;';
            debugControls.innerHTML = 
                '<div style="margin-bottom: 10px;"><strong>Admin Debug Controls</strong></div>' +
                '<button id="toggle-mode" style="margin: 5px; padding: 5px 10px;">Toggle Mode</button>' +
                '<button id="switch-qr" style="margin: 5px; padding: 5px 10px;">QR Mode</button>' +
                '<button id="switch-results" style="margin: 5px; padding: 5px 10px;">Results Mode</button>' +
                '<div style="margin-top: 10px;">Current: <span id="current-mode">-</span></div>';
            document.body.appendChild(debugControls);
            
            document.getElementById('toggle-mode').addEventListener('click', function() {
                liveUpdates.toggleMode();
            });
            
            document.getElementById('switch-qr').addEventListener('click', function() {
                liveUpdates.switchMode('qr');
            });
            
            document.getElementById('switch-results').addEventListener('click', function() {
                liveUpdates.switchMode('results');
            });
            
            // Update current mode display
            liveUpdates.on('mode-change', function(data) {
                document.getElementById('current-mode').textContent = data.newMode;
            });
        <?php endif; ?>
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (liveUpdates) {
            liveUpdates.destroy();
        }
    });
    
    // Global access for debugging
    window.quizLiveUpdates = liveUpdates;
    window.quizDisplayController = displayController;
});
</script>

<?php get_footer(); ?>