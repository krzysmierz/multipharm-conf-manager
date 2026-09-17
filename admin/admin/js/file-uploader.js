/**
 * File Uploader JavaScript for Conference Manager
 */

(function($) {
    'use strict';

    var FileUploader = {
        init: function() {
            this.bindEvents();
            this.initDropzones();
        },

        bindEvents: function() {
            $(document).on('click', '.upload-btn', this.triggerUpload);
            $(document).on('change', '.file-input', this.handleFileSelect);
            $(document).on('click', '.remove-file-btn', this.removeFile);
            $(document).on('dragover', '.upload-area', this.handleDragOver);
            $(document).on('drop', '.upload-area', this.handleDrop);
            $(document).on('dragleave', '.upload-area', this.handleDragLeave);
        },

        initDropzones: function() {
            $('.upload-area').each(function() {
                $(this).addClass('dropzone-ready');
            });
        },

        triggerUpload: function(e) {
            e.preventDefault();
            $(this).siblings('.file-input').click();
        },

        handleFileSelect: function(e) {
            var files = e.target.files;
            var $container = $(this).closest('.file-upload-container');
            
            for (var i = 0; i < files.length; i++) {
                FileUploader.uploadFile(files[i], $container);
            }
        },

        handleDragOver: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        },

        handleDragLeave: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        },

        handleDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $area = $(this);
            $area.removeClass('drag-over');
            
            var files = e.originalEvent.dataTransfer.files;
            var $container = $area.closest('.file-upload-container');
            
            for (var i = 0; i < files.length; i++) {
                FileUploader.uploadFile(files[i], $container);
            }
        },

        uploadFile: function(file, $container) {
            var maxSize = $container.data('max-size') || 5242880; // 5MB default
            var allowedTypes = $container.data('allowed-types') || '';
            
            // Validate file size
            if (file.size > maxSize) {
                CMAdmin.showNotice('error', 'File "' + file.name + '" is too large. Maximum size: ' + CMAdmin.formatFileSize(maxSize));
                return;
            }
            
            // Validate file type
            if (allowedTypes && allowedTypes.indexOf(file.type) === -1) {
                CMAdmin.showNotice('error', 'File type "' + file.type + '" is not allowed.');
                return;
            }
            
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'cm_upload_file');
            formData.append('upload_type', $container.data('upload-type') || 'general');
            formData.append('nonce', cm_ajax.nonce);
            
            // Create progress indicator
            var progressHtml = '<div class="upload-progress" data-filename="' + file.name + '">' +
                              '<div class="upload-info">' +
                              '<span class="filename">' + file.name + '</span>' +
                              '<span class="filesize">(' + CMAdmin.formatFileSize(file.size) + ')</span>' +
                              '</div>' +
                              '<div class="progress-bar">' +
                              '<div class="progress-fill" style="width: 0%"></div>' +
                              '</div>' +
                              '<div class="upload-status">Uploading...</div>' +
                              '</div>';
            
            $container.find('.upload-progress-area').append(progressHtml);
            var $progress = $container.find('.upload-progress[data-filename="' + file.name + '"]');
            
            // Upload file
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                            $progress.find('.progress-fill').css('width', percent + '%');
                            $progress.find('.upload-status').text(percent + '%');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        $progress.addClass('upload-complete');
                        $progress.find('.upload-status').text('Complete');
                        
                        // Add to uploaded files list
                        FileUploader.addUploadedFile(response.data, $container);
                        
                        // Remove progress after delay
                        setTimeout(function() {
                            $progress.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 2000);
                        
                    } else {
                        $progress.addClass('upload-error');
                        $progress.find('.upload-status').text('Error: ' + (response.data || 'Upload failed'));
                    }
                },
                error: function() {
                    $progress.addClass('upload-error');
                    $progress.find('.upload-status').text('Network error');
                }
            });
        },

        addUploadedFile: function(fileData, $container) {
            var fileHtml = '<div class="uploaded-file" data-file-id="' + fileData.id + '">' +
                          '<div class="file-info">' +
                          '<span class="filename">' + fileData.name + '</span>' +
                          '<span class="filesize">(' + CMAdmin.formatFileSize(fileData.size) + ')</span>' +
                          '</div>' +
                          '<div class="file-actions">' +
                          '<a href="' + fileData.url + '" target="_blank" class="view-file-btn">View</a>' +
                          '<button type="button" class="remove-file-btn" data-file-id="' + fileData.id + '">Remove</button>' +
                          '</div>' +
                          '<input type="hidden" name="uploaded_files[]" value="' + fileData.id + '">' +
                          '</div>';
            
            $container.find('.uploaded-files-list').append(fileHtml);
        },

        removeFile: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to remove this file?')) {
                return;
            }
            
            var $btn = $(this);
            var fileId = $btn.data('file-id');
            var $fileItem = $btn.closest('.uploaded-file');
            
            $.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_remove_file',
                    file_id: fileId,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $fileItem.fadeOut(300, function() {
                            $(this).remove();
                        });
                        CMAdmin.showNotice('success', 'File removed successfully.');
                    } else {
                        CMAdmin.showNotice('error', response.data || 'Failed to remove file.');
                    }
                },
                error: function() {
                    CMAdmin.showNotice('error', 'Network error occurred.');
                }
            });
        }
    };

    $(document).ready(function() {
        if ($('.file-upload-container').length) {
            FileUploader.init();
        }
    });

    window.FileUploader = FileUploader;

})(jQuery);