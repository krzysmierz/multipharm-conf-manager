/**
 * Quiz Manager JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    var QuizManager = {
        init: function() {
            this.bindEvents();
            this.initQuizBuilder();
        },

        bindEvents: function() {
            $(document).on('click', '.add-question-btn', this.addQuestion);
            $(document).on('click', '.remove-question-btn', this.removeQuestion);
            $(document).on('click', '.add-answer-btn', this.addAnswer);
            $(document).on('click', '.remove-answer-btn', this.removeAnswer);
            $(document).on('change', '.question-type-select', this.handleQuestionTypeChange);
            $(document).on('click', '.preview-quiz-btn', this.previewQuiz);
            $(document).on('click', '#quiz-preview-modal button, #quiz-preview-modal', this.closeModal);
            $(document).on('click', '#quiz-preview-modal .relative', function(e) {
                e.stopPropagation();
            });
        },

        initQuizBuilder: function() {
            $('.quiz-questions').each(function() {
                QuizManager.updateQuestionNumbers($(this));
            });
        },

        addQuestion: function(e) {
            e.preventDefault();
            
            var $container = $(this).closest('.quiz-builder');
            var template = $('#question-template').html();
            var index = $container.find('.question-item').length;
            
            template = template.replace(/\{INDEX\}/g, index);
            $container.find('.quiz-questions').append(template);
            
            QuizManager.updateQuestionNumbers($container.find('.quiz-questions'));
        },

        removeQuestion: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to remove this question?')) {
                return;
            }
            
            var $question = $(this).closest('.question-item');
            var $container = $question.closest('.quiz-questions');
            
            $question.fadeOut(300, function() {
                $(this).remove();
                QuizManager.reindexQuestions($container);
                QuizManager.updateQuestionNumbers($container);
            });
        },

        addAnswer: function(e) {
            e.preventDefault();
            
            var $question = $(this).closest('.question-item');
            var template = $('#answer-template').html();
            var questionIndex = $question.index();
            var answerIndex = $question.find('.answer-item').length;
            
            template = template.replace(/\{QUESTION_INDEX\}/g, questionIndex);
            template = template.replace(/\{ANSWER_INDEX\}/g, answerIndex);
            
            $question.find('.answers-list').append(template);
        },

        removeAnswer: function(e) {
            e.preventDefault();
            
            var $answer = $(this).closest('.answer-item');
            var $question = $answer.closest('.question-item');
            
            if ($question.find('.answer-item').length <= 2) {
                CMAdmin.showNotice('error', 'A question must have at least 2 answers.');
                return;
            }
            
            $answer.fadeOut(300, function() {
                $(this).remove();
                QuizManager.reindexAnswers($question);
            });
        },

        handleQuestionTypeChange: function() {
            var $select = $(this);
            var type = $select.val();
            var $question = $select.closest('.question-item');
            var $answersContainer = $question.find('.answers-container');
            
            switch (type) {
                case 'multiple_choice':
                case 'single_choice':
                    $answersContainer.show();
                    $question.find('.correct-answer-type').text(
                        type === 'multiple_choice' ? 'Select all correct answers:' : 'Select the correct answer:'
                    );
                    break;
                case 'text':
                case 'textarea':
                    $answersContainer.hide();
                    break;
            }
        },

        previewQuiz: function(e) {
            e.preventDefault();
            
            var $form = $(this).closest('form');
            var quizData = QuizManager.serializeQuiz($form);
            
            // Open preview in modal or new window
            var previewHtml = QuizManager.generatePreviewHtml(quizData);
            
            // Create modal for preview
            var modalHtml = '<div id="quiz-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">' +
                           '<div class="relative w-full max-w-4xl mx-4 max-h-[90vh] bg-white rounded-xl shadow-2xl overflow-hidden">' +
                           '<div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200">' +
                           '<h3 class="text-xl font-semibold text-gray-900">Quiz Preview</h3>' +
                           '<button type="button" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-colors duration-200">' +
                           '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">' +
                           '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>' +
                           '</svg>' +
                           '</button>' +
                           '</div>' +
                           '<div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">' + previewHtml + '</div>' +
                           '</div>' +
                           '</div>';
            
            $('body').append(modalHtml);
            $('#quiz-preview-modal').removeClass('hidden').addClass('flex');
        },

        closeModal: function(e) {
            e.preventDefault();
            $('#quiz-preview-modal').addClass('hidden').removeClass('flex');
            setTimeout(function() {
                $('#quiz-preview-modal').remove();
            }, 300);
        },

        serializeQuiz: function($form) {
            var quiz = {
                title: $form.find('[name="quiz_title"]').val(),
                description: $form.find('[name="quiz_description"]').val(),
                questions: []
            };
            
            $form.find('.question-item').each(function() {
                var $question = $(this);
                var question = {
                    title: $question.find('[name*="[title]"]').val(),
                    type: $question.find('[name*="[type]"]').val(),
                    required: $question.find('[name*="[required]"]').is(':checked'),
                    answers: []
                };
                
                $question.find('.answer-item').each(function() {
                    var $answer = $(this);
                    question.answers.push({
                        text: $answer.find('[name*="[text]"]').val(),
                        correct: $answer.find('[name*="[correct]"]').is(':checked')
                    });
                });
                
                quiz.questions.push(question);
            });
            
            return quiz;
        },

        generatePreviewHtml: function(quiz) {
            var html = '<div class="quiz-preview space-y-6">';
            html += '<div class="text-center border-b border-gray-200 pb-4">';
            html += '<h3 class="text-2xl font-bold text-gray-900 mb-2">' + quiz.title + '</h3>';
            if (quiz.description) {
                html += '<p class="text-gray-600 leading-relaxed">' + quiz.description + '</p>';
            }
            html += '</div>';
            
            quiz.questions.forEach(function(question, index) {
                html += '<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 space-y-4">';
                html += '<h4 class="text-lg font-semibold text-gray-900 leading-tight">Question ' + (index + 1) + ': ' + question.title;
                if (question.required) html += ' <span class="text-red-500">*</span>';
                html += '</h4>';
                
                html += '<div class="space-y-3">';
                switch (question.type) {
                    case 'multiple_choice':
                        question.answers.forEach(function(answer) {
                            html += '<label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-100 p-2 rounded transition-colors">';
                            html += '<input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">';
                            html += '<span class="text-gray-700">' + answer.text + '</span>';
                            html += '</label>';
                        });
                        break;
                    case 'single_choice':
                        question.answers.forEach(function(answer) {
                            html += '<label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-100 p-2 rounded transition-colors">';
                            html += '<input type="radio" name="q' + index + '" disabled class="border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">';
                            html += '<span class="text-gray-700">' + answer.text + '</span>';
                            html += '</label>';
                        });
                        break;
                    case 'text':
                        html += '<input type="text" disabled placeholder="Text answer" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">';
                        break;
                    case 'textarea':
                        html += '<textarea disabled placeholder="Long text answer" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white resize-none"></textarea>';
                        break;
                }
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            return html;
        },

        updateQuestionNumbers: function($container) {
            $container.find('.question-item').each(function(index) {
                $(this).find('.question-number').text('Question ' + (index + 1));
            });
        },

        reindexQuestions: function($container) {
            $container.find('.question-item').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[questions\]\[\d+\]/, '[questions][' + index + ']'));
                    }
                });
                
                QuizManager.reindexAnswers($(this));
            });
        },

        reindexAnswers: function($question) {
            $question.find('.answer-item').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[answers\]\[\d+\]/, '[answers][' + index + ']'));
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        if ($('.quiz-builder').length) {
            QuizManager.init();
        }
    });

    window.QuizManager = QuizManager;

})(jQuery);