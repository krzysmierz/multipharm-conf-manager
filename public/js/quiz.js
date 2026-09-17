/**
 * Quiz functionality for Conference Manager public frontend
 */

(function($) {
    'use strict';

    var CMQuiz = {
        currentQuestionIndex: 0,
        questions: [],
        responses: {},
        timer: null,
        timeRemaining: 0,

        init: function() {
            this.bindEvents();
            this.loadQuizData();
            this.initializeQuiz();
        },

        bindEvents: function() {
            $(document).on('click', '.quiz-answer', this.selectAnswer);
            $(document).on('click', '.next-question-btn', this.nextQuestion);
            $(document).on('click', '.prev-question-btn', this.prevQuestion);
            $(document).on('click', '.submit-quiz-btn', this.submitQuiz);
            $(document).on('click', '.restart-quiz-btn', this.restartQuiz);
            $(document).on('change', '.quiz-text-input', this.handleTextInput);
        },

        loadQuizData: function() {
            var quizContainer = $('.cm-quiz-container');
            if (quizContainer.length) {
                this.questions = quizContainer.data('questions') || [];
                this.timeRemaining = quizContainer.data('time-limit') || 0;
            }
        },

        initializeQuiz: function() {
            if (this.questions.length > 0) {
                this.showQuestion(0);
                if (this.timeRemaining > 0) {
                    this.startTimer();
                }
                this.updateProgressBar();
            }
        },

        showQuestion: function(index) {
            $('.quiz-question').hide();
            $('#question-' + index).show();
            this.currentQuestionIndex = index;
            
            this.updateNavigationButtons();
            this.updateQuestionCounter();
        },

        selectAnswer: function(e) {
            var $answer = $(this);
            var questionId = $answer.closest('.quiz-question').data('question-id');
            var questionType = $answer.closest('.quiz-question').data('question-type');

            if (questionType === 'single_choice') {
                // Single choice - unselect others
                $answer.closest('.quiz-answers').find('.quiz-answer').removeClass('selected');
                $answer.addClass('selected');
                CMQuiz.responses[questionId] = [$answer.data('answer-id')];
            } else if (questionType === 'multiple_choice') {
                // Multiple choice - toggle selection
                $answer.toggleClass('selected');
                if (!CMQuiz.responses[questionId]) {
                    CMQuiz.responses[questionId] = [];
                }
                
                var answerId = $answer.data('answer-id');
                var index = CMQuiz.responses[questionId].indexOf(answerId);
                
                if ($answer.hasClass('selected') && index === -1) {
                    CMQuiz.responses[questionId].push(answerId);
                } else if (!$answer.hasClass('selected') && index > -1) {
                    CMQuiz.responses[questionId].splice(index, 1);
                }
            }
            
            CMQuiz.updateProgressBar();
        },

        handleTextInput: function() {
            var $input = $(this);
            var questionId = $input.closest('.quiz-question').data('question-id');
            
            CMQuiz.responses[questionId] = {
                text: $input.val().trim()
            };
            
            CMQuiz.updateProgressBar();
        },

        nextQuestion: function(e) {
            e.preventDefault();
            if (CMQuiz.currentQuestionIndex < CMQuiz.questions.length - 1) {
                CMQuiz.showQuestion(CMQuiz.currentQuestionIndex + 1);
            }
        },

        prevQuestion: function(e) {
            e.preventDefault();
            if (CMQuiz.currentQuestionIndex > 0) {
                CMQuiz.showQuestion(CMQuiz.currentQuestionIndex - 1);
            }
        },

        updateNavigationButtons: function() {
            var $prevBtn = $('.prev-question-btn');
            var $nextBtn = $('.next-question-btn');
            var $submitBtn = $('.submit-quiz-btn');

            // Show/hide previous button
            if (this.currentQuestionIndex === 0) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }

            // Show/hide next/submit button
            if (this.currentQuestionIndex === this.questions.length - 1) {
                $nextBtn.hide();
                $submitBtn.show();
            } else {
                $nextBtn.show();
                $submitBtn.hide();
            }
        },

        updateQuestionCounter: function() {
            $('.question-counter').text((this.currentQuestionIndex + 1) + ' / ' + this.questions.length);
        },

        updateProgressBar: function() {
            var answeredQuestions = Object.keys(this.responses).length;
            var progress = (answeredQuestions / this.questions.length) * 100;
            $('.quiz-progress-bar').css('width', progress + '%');
            $('.quiz-progress-text').text(Math.round(progress) + '% ukończono');
        },

        startTimer: function() {
            this.updateTimerDisplay();
            
            this.timer = setInterval(function() {
                CMQuiz.timeRemaining--;
                CMQuiz.updateTimerDisplay();
                
                if (CMQuiz.timeRemaining <= 0) {
                    CMQuiz.timeUp();
                }
            }, 1000);
        },

        updateTimerDisplay: function() {
            var minutes = Math.floor(this.timeRemaining / 60);
            var seconds = this.timeRemaining % 60;
            var timeString = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            $('.quiz-timer').text(timeString);
            
            // Add warning classes for low time
            if (this.timeRemaining <= 60) {
                $('.quiz-timer').addClass('time-warning');
            }
            if (this.timeRemaining <= 30) {
                $('.quiz-timer').addClass('time-critical');
            }
        },

        timeUp: function() {
            clearInterval(this.timer);
            alert('Czas na rozwiązanie quizu się skończył!');
            this.submitQuiz();
        },

        submitQuiz: function(e) {
            if (e) e.preventDefault();
            
            if (!CMQuiz.validateResponses()) {
                alert('Proszę odpowiedzieć na wszystkie wymagane pytania.');
                return;
            }
            
            var quizId = $('.cm-quiz-container').data('quiz-id');
            var userIdentifier = CMQuiz.getUserIdentifier();
            
            // Show loading state
            $('.submit-quiz-btn').prop('disabled', true).text('Wysyłanie...');
            
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_submit_quiz',
                    quiz_id: quizId,
                    user_identifier: userIdentifier,
                    responses: CMQuiz.responses,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CMQuiz.showResults();
                    } else {
                        alert('Błąd podczas wysyłania odpowiedzi: ' + response.data);
                        $('.submit-quiz-btn').prop('disabled', false).text('Wyślij odpowiedzi');
                    }
                },
                error: function() {
                    alert('Błąd połączenia. Spróbuj ponownie.');
                    $('.submit-quiz-btn').prop('disabled', false).text('Wyślij odpowiedzi');
                }
            });
        },

        validateResponses: function() {
            var valid = true;
            
            $('.quiz-question[data-required="true"]').each(function() {
                var questionId = $(this).data('question-id');
                var response = CMQuiz.responses[questionId];
                
                if (!response || (Array.isArray(response) && response.length === 0) || 
                    (response.text && response.text.trim() === '')) {
                    valid = false;
                    return false;
                }
            });
            
            return valid;
        },

        getUserIdentifier: function() {
            // Check if user is already registered
            var identifier = localStorage.getItem('cm_user_identifier_' + this.getQuizId());

            if (identifier) {
                return identifier;
            }

            // Register new participant
            this.registerParticipant();
            return null; // Will be handled by registration callback
        },

        getQuizId: function() {
            return $('.cm-quiz-container').data('quiz-id') || 0;
        },

        registerParticipant: function() {
            var self = this;
            var quizId = this.getQuizId();

            if (!quizId) {
                alert('Błąd: Nie można znaleźć ID quizu');
                return;
            }

            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_register_participant',
                    quiz_id: quizId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Store participant ID for this quiz
                        localStorage.setItem('cm_user_identifier_' + quizId, response.data.participant_id);

                        // Show participant ID to user
                        self.showParticipantId(response.data.participant_id);

                        // Enable quiz interaction
                        $('.quiz-container').removeClass('participant-registration');
                    } else {
                        alert('Błąd rejestracji: ' + response.data);
                    }
                },
                error: function() {
                    alert('Błąd połączenia podczas rejestracji uczestnika');
                }
            });
        },

        showParticipantId: function(participantId) {
            // Create or update participant info display
            var infoHtml = '<div class="participant-info">' +
                          '<h3>Twój identyfikator uczestnika: <span class="participant-id">' + participantId + '</span></h3>' +
                          '<p>Zapamiętaj ten identyfikator - będzie potrzebny do sprawdzenia wyników!</p>' +
                          '</div>';

            $('.cm-quiz-container').prepend(infoHtml);
        },

        showResults: function() {
            $('.quiz-questions').hide();
            $('.quiz-navigation').hide();
            $('.quiz-results').show();
            
            if (this.timer) {
                clearInterval(this.timer);
            }
            
            // Load and display results
            this.loadResults();
        },

        loadResults: function() {
            var quizId = $('.cm-quiz-container').data('quiz-id');
            
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'cm_get_quiz_results',
                    quiz_id: quizId
                },
                success: function(response) {
                    if (response.success) {
                        CMQuiz.displayResults(response.data);
                    }
                }
            });
        },

        displayResults: function(results) {
            var resultsHtml = '<h3>Wyniki quizu</h3>';
            
            if (results.user_score !== undefined) {
                resultsHtml += '<div class="user-score">';
                resultsHtml += '<p>Twój wynik: <strong>' + results.user_score + '</strong></p>';
                resultsHtml += '</div>';
            }
            
            if (results.statistics) {
                resultsHtml += '<div class="quiz-statistics">';
                resultsHtml += '<h4>Statystyki:</h4>';
                resultsHtml += '<p>Średni wynik: ' + results.statistics.average_score + '</p>';
                resultsHtml += '<p>Liczba uczestników: ' + results.statistics.total_participants + '</p>';
                resultsHtml += '</div>';
            }
            
            $('.quiz-results-content').html(resultsHtml);
        },

        restartQuiz: function(e) {
            e.preventDefault();
            
            // Reset state
            this.currentQuestionIndex = 0;
            this.responses = {};
            
            // Reset UI
            $('.quiz-answer').removeClass('selected');
            $('.quiz-text-input').val('');
            $('.quiz-results').hide();
            $('.quiz-questions').show();
            $('.quiz-navigation').show();
            $('.submit-quiz-btn').prop('disabled', false).text('Wyślij odpowiedzi');
            
            // Restart
            this.showQuestion(0);
            if (this.timeRemaining > 0) {
                this.timeRemaining = $('.cm-quiz-container').data('time-limit') || 0;
                this.startTimer();
            }
            this.updateProgressBar();
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.cm-quiz-container').length) {
            CMQuiz.init();
        }
    });

    // Expose globally
    window.CMQuiz = CMQuiz;

})(jQuery);