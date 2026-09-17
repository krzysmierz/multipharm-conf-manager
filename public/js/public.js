/**
 * Public JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    // Public main functionality
    var CMPublic = {
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.startTimers();
        },

        bindEvents: function() {
            // Quiz form submission
            $(document).on('submit', '.cm-quiz-form', this.handleQuizSubmission);
            
            // Auto-refresh for live content
            $(document).on('click', '.cm-refresh-content', this.refreshContent);
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts);
        },

        initComponents: function() {
            // Initialize any third-party components
            this.initLiveUpdates();
            this.initProgressBars();
        },

        startTimers: function() {
            // Start presentation timer if present
            if ($('.cm-timer').length > 0) {
                this.updateTimer();
                setInterval(this.updateTimer.bind(this), 1000);
            }
        },

        handleQuizSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.cm-submit-quiz');
            var quizId = $form.data('quiz-id');
            
            // Validate form
            if (!CMPublic.validateQuizForm($form)) {
                CMPublic.showMessage('error', 'Please answer all questions before submitting.');
                return;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $submitBtn.text('Submitting...');
            $form.addClass('cm-loading');
            
            // Collect responses
            var responses = CMPublic.collectQuizResponses($form);
            var userIdentifier = CMPublic.generateUserIdentifier();
            
            // Submit quiz
            $.ajax({
                url: cm_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_submit_quiz',
                    quiz_id: quizId,
                    user_identifier: userIdentifier,
                    responses: responses,
                    nonce: cm_public_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CMPublic.showQuizResults($form, response.data);
                        CMPublic.showMessage('success', 'Quiz submitted successfully!');
                    } else {
                        CMPublic.showMessage('error', response.data || 'Failed to submit quiz.');
                        $submitBtn.prop('disabled', false);
                        $submitBtn.text('Submit Quiz');
                    }
                },
                error: function() {
                    CMPublic.showMessage('error', 'Network error. Please try again.');
                    $submitBtn.prop('disabled', false);
                    $submitBtn.text('Submit Quiz');
                },
                complete: function() {
                    $form.removeClass('cm-loading');
                }
            });
        },

        validateQuizForm: function($form) {
            var isValid = true;
            
            $form.find('.cm-question').each(function() {
                var $question = $(this);
                var questionId = $question.data('question-id');
                var hasAnswer = false;
                
                // Check if question has been answered
                if ($question.find('input[type="radio"]:checked, input[type="checkbox"]:checked').length > 0) {
                    hasAnswer = true;
                } else if ($question.find('textarea').length > 0 && $question.find('textarea').val().trim()) {
                    hasAnswer = true;
                }
                
                if (!hasAnswer) {
                    isValid = false;
                    $question.addClass('error');
                } else {
                    $question.removeClass('error');
                }
            });
            
            return isValid;
        },

        collectQuizResponses: function($form) {
            var responses = {};
            
            $form.find('.cm-question').each(function() {
                var $question = $(this);
                var questionId = $question.data('question-id');
                
                // Radio buttons (single choice)
                var radioAnswer = $question.find('input[type="radio"]:checked').val();
                if (radioAnswer) {
                    responses[questionId] = radioAnswer;
                }
                
                // Checkboxes (multiple choice)
                var checkboxAnswers = [];
                $question.find('input[type="checkbox"]:checked').each(function() {
                    checkboxAnswers.push($(this).val());
                });
                if (checkboxAnswers.length > 0) {
                    responses[questionId] = checkboxAnswers;
                }
                
                // Text areas
                var textAnswer = $question.find('textarea').val();
                if (textAnswer && textAnswer.trim()) {
                    responses[questionId] = { text: textAnswer.trim() };
                }
            });
            
            return responses;
        },

        generateUserIdentifier: function() {
            // Generate or retrieve user identifier
            var identifier = localStorage.getItem('cm_user_identifier');
            
            if (!identifier) {
                identifier = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('cm_user_identifier', identifier);
            }
            
            return identifier;
        },

        showQuizResults: function($form, results) {
            var resultsHtml = '<div class="cm-quiz-results">';
            resultsHtml += '<h3>Quiz Completed!</h3>';
            resultsHtml += '<p>Thank you for participating. Your responses have been recorded.</p>';
            resultsHtml += '</div>';
            
            $form.after(resultsHtml);
            $form.hide();
        },

        initLiveUpdates: function() {
            // Initialize SSE for live event updates
            this.initEventSSE();
        },

        initEventSSE: function() {
            // Get event ID from any container with data-event-id
            var eventId = $('.cm-current-presentation, .cm-lineup-container, [data-event-id]').data('event-id');
            if (!eventId) return;

            // Only initialize SSE if we have containers that need live updates
            var hasLiveContainers = $('.cm-current-presentation, .cm-lineup-container').length > 0;
            if (!hasLiveContainers) return;

            console.log('[CM SSE] Initializing SSE for event', eventId);

            // Create SSE connection
            var sseUrl = new URL(cm_public_ajax.ajax_url);
            sseUrl.searchParams.append('action', 'cm_event_live_updates');
            sseUrl.searchParams.append('event_id', eventId);

            var eventSource = new EventSource(sseUrl.toString());
            this.eventSource = eventSource;

            // Event Listeners
            eventSource.addEventListener('presentation-change', function(event) {
                var presentationData = JSON.parse(event.data);
                console.log('[CM SSE] Presentation change:', presentationData);
                CMPublic.updateCurrentPresentation(presentationData);
            });

            eventSource.addEventListener('lineup-change', function(event) {
                var lineupData = JSON.parse(event.data);
                console.log('[CM SSE] Lineup change:', lineupData);
                CMPublic.updateLineupStatus(lineupData);
            });

            eventSource.addEventListener('heartbeat', function(event) {
                var heartbeatData = JSON.parse(event.data);
                console.log('[CM SSE] Heartbeat:', heartbeatData);
            });

            eventSource.onopen = function() {
                console.log('[CM SSE] Connection opened for event', eventId);
            };

            eventSource.onerror = function() {
                console.error('[CM SSE] Connection error for event', eventId);
            };
        },

        updateCurrentPresentation: function(presentation) {
            var $container = $('.cm-current-presentation');
            if ($container.length === 0) return;

            console.log('[CM SSE] Updating current presentation:', presentation);

            // Update presentation title
            var $title = $container.find('h4, h3, .presentation-title');
            if ($title.length && presentation.title) {
                $title.text(presentation.title);
            }

            // Update presenter
            var $presenter = $container.find('.cm-presenter, .presenter');
            if ($presenter.length) {
                var presenterText = presentation.presenter ? 'Prelegent: ' + presentation.presenter : 'Prelegent: –';
                $presenter.text(presenterText);
            }

            // Update description
            var $description = $container.find('.cm-presentation-description, .presentation-description');
            if ($description.length && presentation.description) {
                $description.html(presentation.description);
            }

            // Update start time
            var $startTime = $container.find('.cm-start-time, .start-time');
            if ($startTime.length && presentation.start_time) {
                $startTime.text('Trwa od: ' + presentation.start_time);
            }
        },

        updateLineupStatus: function(lineup) {
            console.log('[CM SSE] Updating lineup status:', lineup);

            // Remove all active/live indicators
            $('.cm-lineup-item').removeClass('active current live');
            $('.lineup-item').removeClass('active current live');
            $('[data-lineup-id]').removeClass('active current live');

            // Remove LIVE text indicators
            $('.lineup-item .live-indicator, .cm-lineup-item .live-indicator').remove();

            if (Array.isArray(lineup)) {
                lineup.forEach(function(item) {
                    var $item = $('.cm-lineup-item[data-lineup-id="' + item.id + '"], .lineup-item[data-lineup-id="' + item.id + '"], [data-lineup-id="' + item.id + '"]');
                    if (item.is_active) {
                        $item.addClass('active current live');
                        // Add LIVE indicator if not present
                        if ($item.find('.live-indicator').length === 0) {
                            $item.append('<span class="live-indicator">LIVE</span>');
                        }
                    }
                });
            }
        },

        updateTimer: function() {
            var $timer = $('.cm-timer');
            if ($timer.length === 0) return;
            
            $.ajax({
                url: cm_public_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'cm_get_current_time'
                },
                success: function(response) {
                    if (response.success) {
                        var currentTime = new Date(response.data.timestamp * 1000);
                        $timer.text(CMPublic.formatTime(currentTime));
                    }
                }
            });
        },

        formatTime: function(date) {
            return date.toLocaleTimeString('en-US', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        initProgressBars: function() {
            $('.cm-progress-bar').each(function() {
                var $bar = $(this);
                var progress = $bar.data('progress') || 0;
                
                setTimeout(function() {
                    $bar.css('width', progress + '%');
                }, 500);
            });
        },

        refreshContent: function(e) {
            e.preventDefault();
            location.reload();
        },

        handleKeyboardShortcuts: function(e) {
            // Refresh page with R key
            if (e.key === 'r' || e.key === 'R') {
                if (!$(e.target).is('input, textarea')) {
                    location.reload();
                }
            }
            
            // Focus on quiz form with Q key
            if (e.key === 'q' || e.key === 'Q') {
                if (!$(e.target).is('input, textarea')) {
                    var $quiz = $('.cm-quiz-form');
                    if ($quiz.length > 0) {
                        $quiz.find('input, textarea').first().focus();
                    }
                }
            }
        },

        showMessage: function(type, message) {
            // Remove existing messages
            $('.cm-message').remove();
            
            var messageClass = 'cm-message cm-message-' + type;
            var $message = $('<div class="' + messageClass + '">' + message + '</div>');
            
            // Add to the top of the main content
            var $container = $('.cm-container, .cm-event-container, .cm-quiz-container').first();
            if ($container.length > 0) {
                $container.prepend($message);
            } else {
                $('body').prepend($message);
            }
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        // Utility functions
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    clearTimeout(timeout);
                    func.apply(this, arguments);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        isElementInViewport: function(el) {
            var rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        CMPublic.init();
    });

    // Cleanup SSE connection when page unloads
    $(window).on('beforeunload', function() {
        if (CMPublic.eventSource) {
            CMPublic.eventSource.close();
        }
    });

    // Expose CMPublic globally
    window.CMPublic = CMPublic;

})(jQuery);

// Message styles
var messageStyles = `
<style>
.cm-message {
    padding: 15px 20px;
    margin: 10px 0;
    border-radius: 6px;
    font-weight: 500;
    position: relative;
    animation: slideDown 0.3s ease;
}

.cm-message-success {
    background: #d1fae5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.cm-message-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.cm-question.error {
    border-color: #ef4444;
    background-color: #fef2f2;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', messageStyles);