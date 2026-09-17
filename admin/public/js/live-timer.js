/**
 * Live Timer functionality for Conference Manager public frontend
 */

(function($) {
    'use strict';

    var LiveTimer = {
        eventId: null,
        updateInterval: null,
        lastUpdate: null,
        isActive: false,

        init: function() {
            this.bindEvents();
            this.loadEventData();
            this.startUpdates();
        },

        bindEvents: function() {
            $(document).on('click', '.refresh-timer-btn', this.forceUpdate);
            $(document).on('visibilitychange', this.handleVisibilityChange);
            
            // Auto-refresh when page becomes visible
            $(window).on('focus', this.handlePageFocus);
        },

        loadEventData: function() {
            var timerContainer = $('.cm-live-timer');
            if (timerContainer.length) {
                this.eventId = timerContainer.data('event-id');
                this.isActive = true;
            }
        },

        startUpdates: function() {
            if (!this.isActive || !this.eventId) {
                return;
            }

            // Initial update
            this.updateTimer();
            this.getCurrentPresentation();

            // Set up periodic updates
            this.updateInterval = setInterval(function() {
                LiveTimer.updateTimer();
                LiveTimer.getCurrentPresentation();
            }, 30000); // Update every 30 seconds
        },

        stopUpdates: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },

        updateTimer: function() {
            var now = new Date();
            var timeString = this.formatTime(now);
            
            $('.current-time').text(timeString);
            this.lastUpdate = now;
            
            // Update relative timestamps
            this.updateRelativeTimestamps();
            
            // Check for presentation updates
            this.checkPresentationStatus();
        },

        getCurrentPresentation: function() {
            if (!this.eventId) {
                return;
            }

            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'cm_get_current_presentation',
                    event_id: this.eventId
                },
                success: function(response) {
                    if (response.success) {
                        LiveTimer.updateCurrentPresentation(response.data);
                    } else {
                        LiveTimer.clearCurrentPresentation();
                    }
                },
                error: function() {
                    console.log('Failed to get current presentation');
                }
            });
        },

        updateCurrentPresentation: function(presentation) {
            var $container = $('.current-presentation');
            
            if (presentation && presentation.title) {
                var html = '<div class="presentation-info">';
                html += '<h3 class="presentation-title">' + presentation.title + '</h3>';
                
                if (presentation.presenter) {
                    html += '<p class="presentation-presenter">Prowadzący: ' + presentation.presenter + '</p>';
                }
                
                if (presentation.start_time) {
                    html += '<p class="presentation-time">Rozpoczęcie: ' + presentation.start_time + '</p>';
                }
                
                if (presentation.description) {
                    html += '<p class="presentation-description">' + presentation.description + '</p>';
                }
                
                html += '</div>';
                
                $container.html(html).addClass('active');
                
                // Add live indicator
                if (!$('.live-indicator').length) {
                    $container.prepend('<div class="live-indicator">NA ŻYWO</div>');
                }
            } else {
                this.clearCurrentPresentation();
            }
        },

        clearCurrentPresentation: function() {
            $('.current-presentation').removeClass('active').html('<p>Brak aktywnej prezentacji</p>');
        },

        checkPresentationStatus: function() {
            // Check if scheduled presentations should start
            $('.scheduled-presentation').each(function() {
                var $presentation = $(this);
                var startTime = $presentation.data('start-time');
                
                if (startTime && LiveTimer.isPresentationDue(startTime)) {
                    $presentation.addClass('current').removeClass('upcoming');
                }
            });
        },

        isPresentationDue: function(startTime) {
            var now = new Date();
            var scheduledTime = new Date();
            
            // Parse time string (HH:MM format)
            var timeParts = startTime.split(':');
            if (timeParts.length === 2) {
                scheduledTime.setHours(parseInt(timeParts[0], 10));
                scheduledTime.setMinutes(parseInt(timeParts[1], 10));
                scheduledTime.setSeconds(0);
                
                // Check if we're within 2 minutes of the scheduled time
                var diffMs = Math.abs(now - scheduledTime);
                return diffMs <= 120000; // 2 minutes in milliseconds
            }
            
            return false;
        },

        updateRelativeTimestamps: function() {
            $('.relative-timestamp').each(function() {
                var $timestamp = $(this);
                var datetime = $timestamp.data('datetime');
                
                if (datetime) {
                    var relativeTime = LiveTimer.getRelativeTime(new Date(datetime));
                    $timestamp.text(relativeTime);
                }
            });
        },

        getRelativeTime: function(timestamp) {
            var now = new Date();
            var diff = now - timestamp;
            var seconds = Math.floor(diff / 1000);
            var minutes = Math.floor(seconds / 60);
            var hours = Math.floor(minutes / 60);
            var days = Math.floor(hours / 24);

            if (days > 0) {
                return days + ' dni temu';
            } else if (hours > 0) {
                return hours + ' godzin temu';
            } else if (minutes > 0) {
                return minutes + ' minut temu';
            } else if (seconds > 10) {
                return seconds + ' sekund temu';
            } else {
                return 'teraz';
            }
        },

        formatTime: function(date) {
            var hours = date.getHours().toString().padStart(2, '0');
            var minutes = date.getMinutes().toString().padStart(2, '0');
            var seconds = date.getSeconds().toString().padStart(2, '0');
            return hours + ':' + minutes + ':' + seconds;
        },

        formatDate: function(date) {
            var day = date.getDate().toString().padStart(2, '0');
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var year = date.getFullYear();
            return day + '.' + month + '.' + year;
        },

        forceUpdate: function(e) {
            if (e) e.preventDefault();
            
            LiveTimer.updateTimer();
            LiveTimer.getCurrentPresentation();
            
            // Show feedback
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Aktualizowanie...').prop('disabled', true);
            
            setTimeout(function() {
                $btn.text(originalText).prop('disabled', false);
            }, 1000);
        },

        handleVisibilityChange: function() {
            if (document.hidden) {
                // Page is hidden, reduce update frequency
                LiveTimer.stopUpdates();
            } else {
                // Page is visible, resume normal updates
                LiveTimer.startUpdates();
            }
        },

        handlePageFocus: function() {
            // Force update when page regains focus
            setTimeout(function() {
                LiveTimer.forceUpdate();
            }, 100);
        },

        // Countdown functionality for upcoming presentations
        startCountdown: function(targetTime, $container) {
            var countdownInterval = setInterval(function() {
                var now = new Date();
                var target = new Date(targetTime);
                var timeLeft = target - now;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    $container.html('<span class="countdown-finished">Prezentacja rozpoczęta!</span>');
                    LiveTimer.getCurrentPresentation(); // Refresh current presentation
                    return;
                }
                
                var hours = Math.floor(timeLeft / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                var countdownText = '';
                if (hours > 0) {
                    countdownText += hours + 'h ';
                }
                if (minutes > 0 || hours > 0) {
                    countdownText += minutes + 'm ';
                }
                countdownText += seconds + 's';
                
                $container.html('<span class="countdown-timer">' + countdownText + '</span>');
            }, 1000);
            
            return countdownInterval;
        },

        // Initialize countdowns for upcoming presentations
        initCountdowns: function() {
            $('.upcoming-presentation').each(function() {
                var $presentation = $(this);
                var startTime = $presentation.data('start-time');
                var $countdown = $presentation.find('.presentation-countdown');
                
                if (startTime && $countdown.length) {
                    // Create full datetime from today + start time
                    var today = new Date();
                    var timeParts = startTime.split(':');
                    
                    if (timeParts.length === 2) {
                        var targetDate = new Date(today);
                        targetDate.setHours(parseInt(timeParts[0], 10));
                        targetDate.setMinutes(parseInt(timeParts[1], 10));
                        targetDate.setSeconds(0);
                        
                        // If time has passed today, assume it's tomorrow
                        if (targetDate < today) {
                            targetDate.setDate(targetDate.getDate() + 1);
                        }
                        
                        LiveTimer.startCountdown(targetDate, $countdown);
                    }
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.cm-live-timer').length || $('.current-presentation').length) {
            LiveTimer.init();
            LiveTimer.initCountdowns();
        }
    });

    // Expose globally
    window.LiveTimer = LiveTimer;

})(jQuery);