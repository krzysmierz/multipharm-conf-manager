/**
 * Quiz Live Updates with Server-Sent Events
 * 
 * @package ConferenceManager
 */

class QuizLiveUpdates {
    constructor(quizId, eventId, options = {}) {
        this.quizId = quizId;
        this.eventId = eventId;
        this.options = {
            reconnectDelay: 3000,
            maxReconnectAttempts: 10,
            pollFallbackInterval: 30000,
            enablePollingFallback: true,
            debug: false,
            ...options
        };

        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.currentMode = null;
        this.pollingInterval = null;
        this.initialized = false;
        this.callbacks = {
            'mode-change': [],
            'results-update': [],
            'participant-joined': [],
            'connection-status': [],
            'quiz-status-change': []
        };
        
        // Bind methods
        this.connect = this.connect.bind(this);
        this.disconnect = this.disconnect.bind(this);
        this.handleError = this.handleError.bind(this);
        
        this.log('QuizLiveUpdates initialized', { quizId, eventId, options });
        
        // Auto-connect
        this.connect();
    }
    
    /**
     * Connect to SSE stream
     */
    connect() {
        if (this.isConnected || !this.canUseSSE()) {
            if (!this.canUseSSE() && this.options.enablePollingFallback) {
                this.startPollingFallback();
            }
            return;
        }
        
        this.log('Attempting to connect to SSE...');
        
        try {
            const sseUrl = new URL(cm_public_ajax.ajax_url);
            sseUrl.searchParams.set('action', 'cm_quiz_live_updates');
            sseUrl.searchParams.set('quiz_id', this.quizId);
            sseUrl.searchParams.set('event_id', this.eventId);
            sseUrl.searchParams.set('nonce', this.generateSSENonce());
            
            this.eventSource = new EventSource(sseUrl.toString());
            
            // Connection opened
            this.eventSource.addEventListener('open', (event) => {
                this.log('SSE connection opened');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.triggerCallback('connection-status', { connected: true, type: 'sse' });
            });
            
            // Handle different event types
            this.eventSource.addEventListener('quiz-state-init', (event) => {
                const data = JSON.parse(event.data);
                this.log('Initial quiz state received', data);
                this.handleStateUpdate(data);
            });
            
            this.eventSource.addEventListener('quiz-state-update', (event) => {
                const data = JSON.parse(event.data);
                this.log('Quiz state update received', data);
                this.handleStateUpdate(data);
            });
            
            this.eventSource.addEventListener('quiz-mode-change', (event) => {
                const data = JSON.parse(event.data);
                this.log('Mode change received', data);
                this.handleModeChange(data);
            });
            
            this.eventSource.addEventListener('results-update', (event) => {
                const data = JSON.parse(event.data);
                this.log('Results update received', data);
                this.log('Participants in results update:', data.participants ? data.participants.length : 'NO participants data');
                this.triggerCallback('results-update', data);
            });
            
            this.eventSource.addEventListener('participant-joined', (event) => {
                const data = JSON.parse(event.data);
                this.log('Participant joined', data);
                this.triggerCallback('participant-joined', data);
            });
            
            this.eventSource.addEventListener('quiz-status-change', (event) => {
                const data = JSON.parse(event.data);
                this.log('Quiz status change', data);
                this.triggerCallback('quiz-status-change', data);
            });
            
            this.eventSource.addEventListener('heartbeat', (event) => {
                const data = JSON.parse(event.data);
                this.log('Heartbeat received', data);
            });
            
            this.eventSource.addEventListener('keep-alive', (event) => {
                this.log('Keep-alive received');
            });
            
            // Handle errors
            this.eventSource.addEventListener('error', this.handleError);
            
        } catch (error) {
            this.log('Error creating EventSource', error);
            this.handleConnectionFailure();
        }
    }
    
    /**
     * Disconnect from SSE stream
     */
    disconnect() {
        this.log('Disconnecting from SSE...');
        
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        
        this.isConnected = false;
        this.triggerCallback('connection-status', { connected: false });
    }
    
    /**
     * Handle SSE errors
     */
    handleError(event) {
        this.log('SSE error occurred', event);
        this.isConnected = false;
        this.triggerCallback('connection-status', { connected: false, error: event });
        
        // Attempt reconnection
        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.reconnectAttempts++;
            this.log(`Attempting reconnect ${this.reconnectAttempts}/${this.options.maxReconnectAttempts}`);
            
            setTimeout(() => {
                if (this.eventSource) {
                    this.eventSource.close();
                    this.eventSource = null;
                }
                this.connect();
            }, this.options.reconnectDelay * this.reconnectAttempts);
            
        } else {
            this.log('Max reconnect attempts reached, falling back to polling');
            this.handleConnectionFailure();
        }
    }
    
    /**
     * Handle connection failure - switch to polling
     */
    handleConnectionFailure() {
        this.disconnect();
        
        if (this.options.enablePollingFallback) {
            this.startPollingFallback();
        }
    }
    
    /**
     * Start polling fallback
     */
    startPollingFallback() {
        if (this.pollingInterval) return;
        
        this.log('Starting polling fallback');
        this.triggerCallback('connection-status', { connected: true, type: 'polling' });
        
        // Poll immediately
        this.pollQuizState();
        
        // Set up regular polling
        this.pollingInterval = setInterval(() => {
            this.pollQuizState();
        }, this.options.pollFallbackInterval);
    }
    
    /**
     * Poll quiz state via AJAX
     */
    pollQuizState() {
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_get_quiz_current_mode',
                quiz_id: this.quizId,
                nonce: cm_public_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.handleStateUpdate(data.data);
                
                // Check for mode changes
                if (data.data.mode !== this.currentMode) {
                    this.handleModeChange({
                        quiz_id: this.quizId,
                        mode: data.data.mode,
                        auto_switched: false
                    });
                }
            }
        })
        .catch(error => {
            this.log('Polling error', error);
        });
    }
    
    /**
     * Handle state update
     */
    handleStateUpdate(data) {
        // Update internal state
        if (data.display_mode || data.mode) {
            const newMode = data.display_mode || data.mode;
            // Force mode change on first initialization or when mode actually changes
            if (!this.initialized || newMode !== this.currentMode) {
                this.initialized = true;
                this.handleModeChange({
                    mode: newMode,
                    auto_switched: data.auto_switched || false,
                    timestamp: data.timestamp
                });
            }
        }
    }
    
    /**
     * Handle mode change
     */
    handleModeChange(data) {
        this.log('Handling mode change', data);
        
        if (data.mode !== this.currentMode) {
            const oldMode = this.currentMode;
            this.currentMode = data.mode;
            
            this.triggerCallback('mode-change', {
                oldMode: oldMode,
                newMode: data.mode,
                autoSwitched: data.auto_switched,
                timestamp: data.timestamp
            });
        }
    }
    
    /**
     * Register event callback
     */
    on(eventType, callback) {
        if (this.callbacks[eventType]) {
            this.callbacks[eventType].push(callback);
        } else {
            this.log(`Unknown event type: ${eventType}`);
        }
    }
    
    /**
     * Unregister event callback
     */
    off(eventType, callback) {
        if (this.callbacks[eventType]) {
            const index = this.callbacks[eventType].indexOf(callback);
            if (index > -1) {
                this.callbacks[eventType].splice(index, 1);
            }
        }
    }
    
    /**
     * Trigger callback
     */
    triggerCallback(eventType, data) {
        if (this.callbacks[eventType]) {
            this.callbacks[eventType].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    this.log(`Error in ${eventType} callback`, error);
                }
            });
        }
    }
    
    /**
     * Get current mode
     */
    getCurrentMode() {
        return this.currentMode;
    }
    
    /**
     * Check if SSE is supported
     */
    canUseSSE() {
        return typeof(EventSource) !== 'undefined';
    }
    
    /**
     * Generate nonce for SSE connection
     */
    generateSSENonce() {
        // Use the same nonce as for regular AJAX calls
        return cm_public_ajax.nonce;
    }
    
    /**
     * Manual mode switch (for admin interface)
     */
    switchMode(mode) {
        if (!['qr', 'results'].includes(mode)) {
            this.log('Invalid mode:', mode);
            return;
        }
        
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_set_quiz_state_mode',
                quiz_id: this.quizId,
                mode: mode,
                nonce: cm_public_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.log('Mode switch successful:', data);
            } else {
                this.log('Mode switch failed:', data.data);
            }
        })
        .catch(error => {
            this.log('Mode switch error:', error);
        });
    }
    
    /**
     * Toggle between modes
     */
    toggleMode() {
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_toggle_quiz_mode',
                quiz_id: this.quizId,
                nonce: cm_public_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.log('Mode toggle successful:', data);
            } else {
                this.log('Mode toggle failed:', data.data);
            }
        })
        .catch(error => {
            this.log('Mode toggle error:', error);
        });
    }
    
    /**
     * Send participant join notification
     */
    notifyParticipantJoined(participantName) {
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_participant_join_notification',
                quiz_id: this.quizId,
                participant_name: participantName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.log('Participant join notification sent:', participantName);
            }
        })
        .catch(error => {
            this.log('Participant join notification error:', error);
        });
    }
    
    /**
     * Log messages (if debugging enabled)
     */
    log(message, data = null) {
        if (this.options.debug) {
            console.log(`[QuizLiveUpdates] ${message}`, data || '');
        }
    }
    
    /**
     * Destroy instance
     */
    destroy() {
        this.log('Destroying QuizLiveUpdates instance');
        this.disconnect();
        this.callbacks = {};
    }
}

/**
 * Quiz Display Controller
 * Manages the display switching between QR and Results modes
 */
class QuizDisplayController {
    constructor(containerId, liveUpdates) {
        this.container = document.getElementById(containerId);
        this.liveUpdates = liveUpdates;
        this.currentMode = 'qr';
        
        if (!this.container) {
            console.error('QuizDisplayController: Container not found');
            return;
        }
        
        // Set up mode change handler
        this.liveUpdates.on('mode-change', this.handleModeChange.bind(this));
        
        // Set up other event handlers
        this.liveUpdates.on('results-update', this.handleResultsUpdate.bind(this));
        this.liveUpdates.on('participant-joined', this.handleParticipantJoined.bind(this));
        this.liveUpdates.on('connection-status', this.handleConnectionStatus.bind(this));
        
        this.log('QuizDisplayController initialized');
    }
    
    /**
     * Handle mode change
     */
    handleModeChange(data) {
        this.log('Mode changed from', data.oldMode, 'to', data.newMode);
        
        if (data.newMode === 'qr') {
            this.showQRMode();
        } else if (data.newMode === 'results') {
            this.showResultsMode();
        }
        
        this.currentMode = data.newMode;
        
        // Show notification if auto-switched
        if (data.autoSwitched) {
            this.showNotification(`Automatycznie przełączono na tryb ${data.newMode === 'qr' ? 'QR' : 'wyników'}`, 'info');
        }
    }
    
    /**
     * Show QR mode
     */
    showQRMode() {
        this.log('Switching to QR mode');
        
        this.container.innerHTML = `
            <div class="quiz-mode-container qr-mode">
                <div class="mode-header">
                    <h2>Skanuj kod QR, aby dołączyć do quizu</h2>
                </div>
                <div class="qr-code-display">
                    <div class="qr-placeholder">
                        <div class="qr-loading">
                            <div class="spinner"></div>
                            <p>Ładowanie kodu QR...</p>
                        </div>
                    </div>
                </div>
                <div class="mode-instructions">
                    <p>Użyj aparatu w telefonie, aby zeskanować kod QR i dołączyć do quizu</p>
                </div>
            </div>
        `;
        
        // Load actual QR code
        this.loadQRCode();
    }
    
    /**
     * Show Results mode
     */
    showResultsMode() {
        this.log('Switching to Results mode');
        
        this.container.innerHTML = `
            <div class="quiz-mode-container results-mode">
                <div class="mode-header">
                    <h2>Wyniki Quizu na żywo</h2>
                </div>
                <div class="results-display">
                    <div class="results-loading">
                        <div class="spinner"></div>
                        <p>Ładowanie wyników...</p>
                    </div>
                </div>
            </div>
        `;
        
        // Load initial results
        this.loadResults();
    }
    
    /**
     * Handle results update
     */
    handleResultsUpdate(data) {
        this.log('handleResultsUpdate called with mode:', this.currentMode);
        this.log('handleResultsUpdate data:', data);
        if (this.currentMode === 'results') {
            this.updateResults(data);
        } else {
            this.log('Not updating results because current mode is:', this.currentMode);
        }
    }
    
    /**
     * Handle participant joined
     */
    handleParticipantJoined(data) {
        this.showNotification(`${data.participant_name} dołączył do quizu! (Łącznie: ${data.total_participants})`, 'success');
    }
    
    /**
     * Handle connection status
     */
    handleConnectionStatus(data) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.textContent = data.connected ? 
                (data.type === 'sse' ? 'Połączono (SSE)' : 'Połączono (Polling)') : 
                'Rozłączono';
            statusElement.className = data.connected ? 'connected' : 'disconnected';
        }
    }
    
    /**
     * Load QR Code
     */
    loadQRCode() {
        const placeholder = this.container.querySelector('.qr-placeholder');
        if (!placeholder) return;
        
        // Fetch actual QR code from backend
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_get_quiz_qr_code',
                quiz_id: this.liveUpdates.quizId,
                nonce: cm_public_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.qr_url) {
                placeholder.innerHTML = `
                    <div class="qr-code">
                        <img src="${data.data.qr_url}" 
                             alt="QR Code do dołączenia do quizu" 
                             class="qr-image" 
                             onload="this.style.opacity=1"
                             onerror="this.parentNode.innerHTML='<div class=\\"qr-error\\">Błąd ładowania kodu QR</div>'" />
                        <div class="qr-url-info">
                            <small>Lub wejdź na: <br><span class="quiz-url">${data.data.quiz_url || ''}</span></small>
                        </div>
                    </div>
                `;
            } else {
                // Generate QR code if it doesn't exist
                this.generateQRCode();
            }
        })
        .catch(error => {
            console.error('QR Code loading error:', error);
            // Fallback to generating QR code
            this.generateQRCode();
        });
    }
    
    /**
     * Generate new QR Code
     */
    generateQRCode() {
        const placeholder = this.container.querySelector('.qr-placeholder');
        if (!placeholder) return;
        
        placeholder.innerHTML = `
            <div class="qr-loading">
                <div class="spinner"></div>
                <p>Generowanie kodu QR...</p>
            </div>
        `;
        
        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'cm_generate_quiz_qr',
                quiz_id: this.liveUpdates.quizId,
                nonce: cm_public_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.qr_url) {
                placeholder.innerHTML = `
                    <div class="qr-code">
                        <img src="${data.data.qr_url}" 
                             alt="QR Code do dołączenia do quizu" 
                             class="qr-image" 
                             onload="this.style.opacity=1"
                             onerror="this.parentNode.innerHTML='<div class=\\"qr-error\\">Błąd ładowania kodu QR</div>'" />
                        <div class="qr-url-info">
                            <small>Lub wejdź na: <br><span class="quiz-url">${data.data.quiz_url || ''}</span></small>
                        </div>
                    </div>
                `;
            } else {
                placeholder.innerHTML = `
                    <div class="qr-error">
                        <p>Nie udało się wygenerować kodu QR</p>
                        <p><small>${data.data || 'Nieznany błąd'}</small></p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('QR Code generation error:', error);
            placeholder.innerHTML = `
                <div class="qr-error">
                    <p>Błąd generowania kodu QR</p>
                    <p><small>Sprawdź połączenie internetowe</small></p>
                </div>
            `;
        });
    }
    
    /**
     * Load Results
     */
    loadResults() {
        const resultsDisplay = this.container.querySelector('.results-display');
        if (!resultsDisplay) return;

        this.log('Loading results for quiz ID:', this.liveUpdates.quizId);
        this.log('Available ajax config:', cm_public_ajax);

        const formData = new URLSearchParams({
            action: 'cm_get_quiz_results_public',
            quiz_id: this.liveUpdates.quizId,
            nonce: cm_public_ajax.nonce
        });

        this.log('Form data being sent:', Object.fromEntries(formData));

        fetch(cm_public_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.log('Results data received:', data);
            if (data.success) {
                this.updateResults(data.data);
            } else {
                resultsDisplay.innerHTML = '<div class="error">Błąd wczytywania wyników</div>';
                this.log('Results loading failed:', data);
            }
        })
        .catch(error => {
            console.error('Results loading error:', error);
            resultsDisplay.innerHTML = '<div class="error">Błąd połączenia</div>';
        });
    }
    
    /**
     * Update Results Display
     */
    updateResults(data) {
        const resultsDisplay = this.container.querySelector('.results-display');
        if (!resultsDisplay) {
            this.log('ERROR: results-display container not found');
            return;
        }

        this.log('Updating results with data:', data);

        // Handle different data structure based on endpoint used
        const stats = data.stats || {};
        const participants = data.participants || [];
        const questions = data.questions || [];

        this.log('Parsed data:', { stats, participants: participants.length, questions: questions.length });
        this.log('Full participants array:', participants);

        resultsDisplay.innerHTML = `
            <div class="results-summary">
                <div class="stat-card">
                    <div class="stat-value">${stats.unique_participants || data.total_participants || 0}</div>
                    <div class="stat-label">Uczestnicy</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total_questions || data.total_questions || 0}</div>
                    <div class="stat-label">Pytania</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.average_score || data.avg_score || 0}%</div>
                    <div class="stat-label">Średni wynik</div>
                </div>
            </div>
            ${this.generateParticipantsHTML(participants)}
            ${this.generateQuestionsHTML(questions)}
        `;
    }
    
    /**
     * Generate participants HTML for results
     */
    generateParticipantsHTML(participants) {
        this.log('Generating participants HTML for:', participants);

        if (!participants || !participants.length) {
            return '<div class="no-data">Brak danych o uczestnikach</div>';
        }

        // Sort participants: highest score first, then fastest completion time
        const sortedParticipants = [...participants].sort((a, b) => {
            // First sort by correct answers count (descending)
            if (b.correct_answers !== a.correct_answers) {
                return b.correct_answers - a.correct_answers;
            }
            // Then by completion time (ascending - faster is better)
            return new Date(a.submission_time) - new Date(b.submission_time);
        });

        return `
            <div class="participants-section">
                <h3>Ranking uczestników</h3>
                <div class="participants-grid">
                    ${sortedParticipants.map((participant, index) => `
                        <div class="participant-block ${participant.is_top_scorer ? 'top-scorer' : ''}">
                            <div class="participant-rank">#${index + 1}</div>
                            <div class="participant-info">
                                <div class="participant-id">${participant.user_identifier}</div>
                                <div class="participant-name">${participant.participant_name || 'Nieznany uczestnik'}</div>
                            </div>
                            <div class="participant-stats">
                                <div class="participant-score">${participant.correct_answers}/${participant.total_possible_answers}</div>
                                <div class="participant-percentage">${participant.score_percentage}%</div>
                                <div class="participant-time">${this.formatSubmissionTime(participant.submission_time)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * Generate questions HTML for results
     */
    generateQuestionsHTML(questions) {
        if (!questions.length) {
            return '<div class="no-data">Brak danych o pytaniach</div>';
        }

        return `
            <div class="questions-section">
                <h3>Statystyki odpowiedzi</h3>
                ${questions.map((question, index) => `
                    <div class="question-result">
                        <h4>Pytanie ${index + 1}: ${question.question}</h4>
                        <div class="answers-stats">
                            ${question.answers ? question.answers.map(answer => `
                                <div class="answer-stat ${answer.is_correct ? 'correct' : ''}">
                                    <span class="answer-text">${answer.answer_text}</span>
                                    <span class="answer-stats">
                                        <span class="count">${answer.count}</span>
                                        <span class="percentage">${answer.percentage}%</span>
                                    </span>
                                </div>
                            `).join('') : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    /**
     * Format submission time for display
     */
    formatSubmissionTime(submissionTime) {
        if (!submissionTime) return 'Brak danych';

        const date = new Date(submissionTime);
        const now = new Date();
        const diffMs = now - date;
        const diffSeconds = Math.floor(diffMs / 1000);
        const diffMinutes = Math.floor(diffSeconds / 60);
        const diffHours = Math.floor(diffMinutes / 60);

        if (diffSeconds < 60) {
            return `${diffSeconds}s temu`;
        } else if (diffMinutes < 60) {
            return `${diffMinutes}m temu`;
        } else if (diffHours < 24) {
            return `${diffHours}h temu`;
        } else {
            return date.toLocaleString('pl-PL', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `quiz-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
    
    /**
     * Generate QR placeholder (base64 SVG)
     */
    generateQRPlaceholder() {
        const svg = `<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="200" fill="#f0f0f0"/>
            <text x="100" y="100" text-anchor="middle" font-family="Arial" font-size="14" fill="#666">
                QR Code
            </text>
        </svg>`;
        return btoa(svg);
    }
    
    /**
     * Log messages
     */
    log(message, ...args) {
        if (this.liveUpdates && this.liveUpdates.options.debug) {
            console.log(`[QuizDisplayController] ${message}`, ...args);
        }
    }
}

// Export for global use
window.QuizLiveUpdates = QuizLiveUpdates;
window.QuizDisplayController = QuizDisplayController;