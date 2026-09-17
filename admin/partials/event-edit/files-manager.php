<?php

/**
 * Files manager template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials/event-edit
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$event_files = CM_File_Manager::get_event_files($event->get_id());
?>

<div class="cm-files-manager-tab">
    <div class="cm-tab-header">
        <h2>Zarządzanie plikami</h2>
        <div class="cm-upload-info">
            <span class="dashicons dashicons-info"></span>
            Maksymalny rozmiar pliku: <?php echo get_option('cm_max_file_size', 10); ?>MB
        </div>
    </div>
    
    <!-- File Upload Section -->
    <div class="cm-upload-section">
        <h3>Przesyłanie plików</h3>
        
        <div class="cm-upload-tabs">
            <button type="button" class="cm-upload-tab active" data-tab="presentations">
                <span class="dashicons dashicons-media-document"></span>
                Prezentacje
            </button>
            <button type="button" class="cm-upload-tab" data-tab="images">
                <span class="dashicons dashicons-format-image"></span>
                Obrazy
            </button>
        </div>
        
        <!-- Presentations Upload -->
        <div id="presentations-upload" class="cm-upload-panel active">
            <div class="cm-file-uploader" id="presentation-uploader">
                <div class="cm-upload-area">
                    <span class="dashicons dashicons-upload cm-upload-icon"></span>
                    <h4>Przeciągnij pliki prezentacji tutaj</h4>
                    <p>lub kliknij, aby wybrać pliki</p>
                    <p class="cm-upload-note">
                        Obsługiwane formaty: 
                        <?php 
                        $allowed_types = get_option('cm_allow_file_types', array('ppt', 'pptx', 'pdf'));
                        $presentation_types = array_intersect($allowed_types, array('ppt', 'pptx', 'pdf'));
                        echo implode(', ', array_map('strtoupper', $presentation_types));
                        ?>
                    </p>
                    <input type="file" id="presentation-file-input" multiple 
                           accept=".ppt,.pptx,.pdf" style="display: none;">
                </div>
                <button type="button" class="button button-primary cm-select-files" 
                        data-input="presentation-file-input">
                    Wybierz pliki
                </button>
            </div>
        </div>
        
        <!-- Images Upload -->
        <div id="images-upload" class="cm-upload-panel">
            <div class="cm-file-uploader" id="image-uploader">
                <div class="cm-upload-area">
                    <span class="dashicons dashicons-format-image cm-upload-icon"></span>
                    <h4>Przeciągnij obrazy tutaj</h4>
                    <p>lub kliknij, aby wybrać pliki</p>
                    <p class="cm-upload-note">
                        Obsługiwane formaty: 
                        <?php 
                        $image_types = array_intersect($allowed_types, array('jpg', 'jpeg', 'png', 'gif'));
                        echo implode(', ', array_map('strtoupper', $image_types));
                        ?>
                    </p>
                    <input type="file" id="image-file-input" multiple 
                           accept=".jpg,.jpeg,.png,.gif" style="display: none;">
                </div>
                <button type="button" class="button button-primary cm-select-files" 
                        data-input="image-file-input">
                    Wybierz pliki
                </button>
            </div>
        </div>
        
        <!-- Upload Progress -->
        <div id="upload-progress" class="cm-upload-progress" style="display: none;">
            <div class="cm-progress-bar">
                <div class="cm-progress-fill"></div>
            </div>
            <div class="cm-progress-text">Przesyłanie plików...</div>
        </div>
    </div>
    
    <!-- Files List Section -->
    <div class="cm-files-section">
        <h3>Przesłane pliki</h3>
        
        <!-- Presentations List -->
        <div class="cm-file-category">
            <h4>
                <span class="dashicons dashicons-media-document"></span>
                Prezentacje (<?php echo count($event_files['presentations']); ?>)
            </h4>
            
            <?php if (empty($event_files['presentations'])): ?>
                <div class="cm-no-files">
                    <p>Brak przesłanych prezentacji. Użyj sekcji powyżej, aby dodać pliki.</p>
                </div>
            <?php else: ?>
                <div class="cm-file-list presentations-list">
                    <?php foreach ($event_files['presentations'] as $file): ?>
                        <div class="cm-file-item" data-file-path="<?php echo esc_attr($file['path']); ?>">
                            <div class="cm-file-icon">
                                <span class="<?php echo CM_File_Manager::get_file_type_icon($file['filename']); ?>"></span>
                            </div>
                            
                            <div class="cm-file-info">
                                <div class="cm-file-name"><?php echo esc_html($file['filename']); ?></div>
                                <div class="cm-file-meta">
                                    <span class="cm-file-size"><?php echo CM_File_Manager::format_file_size($file['size']); ?></span>
                                    <span class="cm-file-date"><?php echo date('d.m.Y H:i', $file['modified']); ?></span>
                                </div>
                            </div>
                            
                            <div class="cm-file-actions">
                                <a href="<?php echo esc_url($file['url']); ?>" 
                                   class="button button-small" target="_blank">
                                    <span class="dashicons dashicons-download"></span>
                                    Pobierz
                                </a>
                                
                                <button type="button" class="button button-small cm-assign-to-presentation" 
                                        data-filename="<?php echo esc_attr($file['filename']); ?>">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    Przypisz
                                </button>
                                
                                <button type="button" class="button button-small button-link-delete cm-delete-file" 
                                        data-file-path="<?php echo esc_attr($file['path']); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    Usuń
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Images List -->
        <div class="cm-file-category">
            <h4>
                <span class="dashicons dashicons-format-image"></span>
                Obrazy (<?php echo count($event_files['images']); ?>)
            </h4>
            
            <?php if (empty($event_files['images'])): ?>
                <div class="cm-no-files">
                    <p>Brak przesłanych obrazów.</p>
                </div>
            <?php else: ?>
                <div class="cm-file-list images-list">
                    <?php foreach ($event_files['images'] as $file): ?>
                        <div class="cm-file-item cm-image-item" data-file-path="<?php echo esc_attr($file['path']); ?>">
                            <div class="cm-image-preview">
                                <img src="<?php echo esc_url($file['url']); ?>" alt="<?php echo esc_attr($file['filename']); ?>">
                            </div>
                            
                            <div class="cm-file-info">
                                <div class="cm-file-name"><?php echo esc_html($file['filename']); ?></div>
                                <div class="cm-file-meta">
                                    <span class="cm-file-size"><?php echo CM_File_Manager::format_file_size($file['size']); ?></span>
                                    <span class="cm-file-dimensions">
                                        <?php echo $file['dimensions']['width']; ?>×<?php echo $file['dimensions']['height']; ?>px
                                    </span>
                                    <span class="cm-file-date"><?php echo date('d.m.Y H:i', $file['modified']); ?></span>
                                </div>
                            </div>
                            
                            <div class="cm-file-actions">
                                <a href="<?php echo esc_url($file['url']); ?>" 
                                   class="button button-small" target="_blank">
                                    <span class="dashicons dashicons-visibility"></span>
                                    Podgląd
                                </a>
                                
                                <button type="button" class="button button-small button-link-delete cm-delete-file" 
                                        data-file-path="<?php echo esc_attr($file['path']); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    Usuń
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Storage Usage -->
    <div class="cm-storage-info">
        <h3>Informacje o przestrzeni</h3>
        <div class="cm-storage-stats">
            <?php
            $total_size = 0;
            foreach (array_merge($event_files['presentations'], $event_files['images']) as $file) {
                $total_size += $file['size'];
            }
            ?>
            <div class="cm-storage-item">
                <span class="cm-storage-label">Łączny rozmiar plików:</span>
                <span class="cm-storage-value"><?php echo CM_File_Manager::format_file_size($total_size); ?></span>
            </div>
            <div class="cm-storage-item">
                <span class="cm-storage-label">Liczba plików:</span>
                <span class="cm-storage-value"><?php echo count($event_files['presentations']) + count($event_files['images']); ?></span>
            </div>
        </div>
        
        <div class="cm-storage-actions">
            <button type="button" class="button cm-cleanup-files">
                <span class="dashicons dashicons-admin-tools"></span>
                Wyczyść nieużywane pliki
            </button>
        </div>
    </div>
</div>

<style>
.cm-upload-section {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.cm-upload-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.cm-upload-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cm-upload-tab.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.cm-upload-panel {
    display: none;
}

.cm-upload-panel.active {
    display: block;
}

.cm-file-uploader {
    text-align: center;
}

.cm-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 40px 20px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.cm-upload-area:hover,
.cm-upload-area.dragover {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.cm-upload-icon {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
    margin-bottom: 15px;
}

.cm-upload-area h4 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #1f2937;
}

.cm-upload-note {
    font-size: 14px;
    color: #6b7280;
    margin: 10px 0 0 0;
}

.cm-upload-progress {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.cm-progress-bar {
    width: 100%;
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.cm-progress-fill {
    height: 100%;
    background: #10b981;
    width: 0%;
    transition: width 0.3s ease;
}

.cm-progress-text {
    text-align: center;
    font-weight: 600;
    color: #374151;
}

.cm-file-category {
    margin-bottom: 40px;
}

.cm-file-category h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 20px 0;
    padding: 15px 0;
    border-bottom: 1px solid #e1e5e9;
}

.cm-no-files {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 6px;
    color: #6b7280;
}

.cm-file-list {
    display: grid;
    gap: 15px;
}

.cm-file-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.cm-file-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.cm-file-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 24px;
    color: #6b7280;
}

.cm-image-preview {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.cm-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cm-file-info {
    flex: 1;
}

.cm-file-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
    word-break: break-all;
}

.cm-file-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #6b7280;
    flex-wrap: wrap;
}

.cm-file-actions {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}

.cm-image-item {
    align-items: flex-start;
}

.cm-storage-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.cm-storage-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.cm-storage-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
}

.cm-storage-label {
    font-weight: 500;
    color: #374151;
}

.cm-storage-value {
    font-weight: 700;
    color: #1f2937;
}

.cm-storage-actions {
    border-top: 1px solid #bae6fd;
    padding-top: 15px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .cm-file-item {
        flex-direction: column;
        text-align: center;
    }
    
    .cm-file-actions {
        width: 100%;
        justify-content: center;
    }
    
    .cm-upload-tabs {
        flex-direction: column;
    }
    
    .cm-file-meta {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Upload tabs
    document.querySelectorAll('.cm-upload-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Update active tab
            document.querySelectorAll('.cm-upload-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide panels
            document.querySelectorAll('.cm-upload-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.getElementById(tabName + '-upload').classList.add('active');
        });
    });
    
    // File selection
    document.querySelectorAll('.cm-select-files').forEach(button => {
        button.addEventListener('click', function() {
            const inputId = this.dataset.input;
            document.getElementById(inputId).click();
        });
    });
    
    // Drag and drop
    document.querySelectorAll('.cm-upload-area').forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            const panel = this.closest('.cm-upload-panel');
            handleFileUpload(files, panel.id.includes('presentation') ? 'presentation' : 'image');
        });
        
        area.addEventListener('click', function() {
            const panel = this.closest('.cm-upload-panel');
            const input = panel.querySelector('input[type="file"]');
            input.click();
        });
    });
    
    // File input change
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const files = this.files;
            const type = this.id.includes('presentation') ? 'presentation' : 'image';
            handleFileUpload(files, type);
        });
    });
    
    function handleFileUpload(files, type) {
        if (files.length === 0) return;
        
        const progressContainer = document.getElementById('upload-progress');
        const progressFill = progressContainer.querySelector('.cm-progress-fill');
        const progressText = progressContainer.querySelector('.cm-progress-text');
        
        progressContainer.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = 'Przesyłanie plików...';
        
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('action', 'cm_upload_files');
        formData.append('event_id', <?php echo $event->get_id(); ?>);
        formData.append('file_type', type);
        formData.append('nonce', cm_ajax.nonce);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = `Przesyłanie... ${percent}%`;
            }
        });
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        progressText.textContent = 'Przesyłanie zakończone pomyślnie!';
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        progressText.textContent = 'Błąd: ' + (response.data || 'Nieznany błąd');
                    }
                } catch (e) {
                    progressText.textContent = 'Błąd przetwarzania odpowiedzi';
                }
            } else {
                progressText.textContent = 'Błąd sieciowy';
            }
        };
        
        xhr.onerror = function() {
            progressText.textContent = 'Błąd przesyłania plików';
        };
        
        xhr.open('POST', cm_ajax.ajax_url, true);
        xhr.send(formData);
    }
    
    // Delete file
    document.querySelectorAll('.cm-delete-file').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Czy na pewno chcesz usunąć ten plik?')) return;
            
            const filePath = this.dataset.filePath;
            const fileItem = this.closest('.cm-file-item');
            
            jQuery.ajax({
                url: cm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_delete_file',
                    file_path: filePath,
                    nonce: cm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        fileItem.remove();
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                }
            });
        });
    });
    
    // Cleanup files
    document.querySelector('.cm-cleanup-files').addEventListener('click', function() {
        if (!confirm('Czy na pewno chcesz usunąć nieużywane pliki? Ta operacja jest nieodwracalna.')) return;
        
        const button = this;
        const originalText = button.textContent;
        
        button.textContent = 'Czyszczenie...';
        button.disabled = true;
        
        jQuery.ajax({
            url: cm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_cleanup_files',
                nonce: cm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Nieużywane pliki zostały pomyślnie usunięte.');
                    location.reload();
                } else {
                    alert('Wystąpił błąd: ' + response.data);
                }
            },
            complete: function() {
                button.textContent = originalText;
                button.disabled = false;
            }
        });
    });
});
</script>