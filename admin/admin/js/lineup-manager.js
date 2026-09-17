/**
 * Lineup Manager JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    var LineupManager = {
        init: function() {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function() {
            $(document).on('click', '.add-speaker-btn', this.addSpeaker);
            $(document).on('click', '.remove-speaker-btn', this.removeSpeaker);
            $(document).on('click', '.add-session-btn', this.addSession);
            $(document).on('click', '.remove-session-btn', this.removeSession);
            $(document).on('change', '.speaker-select', this.handleSpeakerChange);
        },

        initSortable: function() {
            if (typeof $.fn.sortable === 'function') {
                $('.lineup-sessions').sortable({
                    handle: '.sort-handle',
                    placeholder: 'sort-placeholder',
                    update: this.updateOrder
                });
            }
        },

        addSpeaker: function(e) {
            e.preventDefault();
            var template = $('#speaker-template').html();
            var index = $('.speaker-item').length;
            template = template.replace(/\{INDEX\}/g, index);
            $('.speakers-list').append(template);
        },

        removeSpeaker: function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this speaker?')) {
                $(this).closest('.speaker-item').remove();
                LineupManager.reindexSpeakers();
            }
        },

        addSession: function(e) {
            e.preventDefault();
            var template = $('#session-template').html();
            var index = $('.session-item').length;
            template = template.replace(/\{INDEX\}/g, index);
            $('.sessions-list').append(template);
        },

        removeSession: function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this session?')) {
                $(this).closest('.session-item').remove();
                LineupManager.reindexSessions();
            }
        },

        handleSpeakerChange: function() {
            var $select = $(this);
            var speakerId = $select.val();
            var $container = $select.closest('.session-item');
            
            if (speakerId) {
                // Load speaker details via AJAX
                $.ajax({
                    url: cm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_speaker_details',
                        speaker_id: speakerId,
                        nonce: cm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $container.find('.speaker-details').html(response.data.html);
                        }
                    }
                });
            }
        },

        updateOrder: function() {
            var order = [];
            $('.session-item').each(function(index) {
                order.push({
                    id: $(this).data('id'),
                    order: index
                });
            });

            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_lineup_order',
                    order: order,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CMAdmin.showNotice('success', 'Order updated successfully.');
                    }
                }
            });
        },

        reindexSpeakers: function() {
            $('.speaker-item').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        reindexSessions: function() {
            $('.session-item').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        if ($('.lineup-manager').length) {
            LineupManager.init();
        }
    });

    window.LineupManager = LineupManager;

})(jQuery);