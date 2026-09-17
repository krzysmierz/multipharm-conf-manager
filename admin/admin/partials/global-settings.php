<?php

/**
 * Settings page template
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current settings
$qr_size = get_option('cm_qr_size', 200);
$max_file_size = get_option('cm_max_file_size', 10);
$quiz_time_limit = get_option('cm_quiz_time_limit', 300);
$auto_advance = get_option('cm_auto_advance_presentations', false);
$allowed_types = get_option('cm_allow_file_types', array('ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'png', 'gif'));
?>

<div class="wrap">
    <h1>Conference Manager - Ustawienia</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('cm_settings'); ?>
        
        <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Ustawienia ogólne</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="max_file_size">Maksymalny rozmiar pliku (MB)</label>
                    </th>
                    <td>
                        <input type="number" id="max_file_size" name="max_file_size" 
                               value="<?php echo esc_attr($max_file_size); ?>" 
                               min="1" max="100" class="small-text">
                        <p class="description">Maksymalny rozmiar przesyłanych plików w megabajtach.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="allow_file_types">Dozwolone typy plików</label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">Dozwolone typy plików</legend>
                            <?php 
                            $all_types = array(
                                'ppt' => 'PowerPoint (.ppt)',
                                'pptx' => 'PowerPoint (.pptx)',
                                'pdf' => 'PDF (.pdf)',
                                'jpg' => 'JPEG (.jpg)',
                                'jpeg' => 'JPEG (.jpeg)',
                                'png' => 'PNG (.png)',
                                'gif' => 'GIF (.gif)'
                            );
                            
                            foreach ($all_types as $ext => $label): ?>
                                <label>
                                    <input type="checkbox" name="allow_file_types[]" value="<?php echo esc_attr($ext); ?>"
                                           <?php checked(in_array($ext, $allowed_types)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description">Wybierz typy plików, które mogą być przesyłane przez użytkowników.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Ustawienia kodów QR</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="qr_size">Rozmiar kodów QR (px)</label>
                    </th>
                    <td>
                        <input type="number" id="qr_size" name="qr_size" 
                               value="<?php echo esc_attr($qr_size); ?>" 
                               min="100" max="500" step="10" class="small-text">
                        <p class="description">Rozmiar generowanych kodów QR w pikselach.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Ustawienia quizów</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="quiz_time_limit">Limit czasu na quiz (sekundy)</label>
                    </th>
                    <td>
                        <input type="number" id="quiz_time_limit" name="quiz_time_limit" 
                               value="<?php echo esc_attr($quiz_time_limit); ?>" 
                               min="60" max="3600" step="30" class="small-text">
                        <p class="description">Maksymalny czas na wypełnienie quizu (0 = bez limitu).</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Ustawienia prezentacji</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Automatyczne przełączanie prezentacji</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_advance_presentations" value="1" 
                                   <?php checked($auto_advance); ?>>
                            Automatycznie przełączaj prezentacje według harmonogramu
                        </label>
                        <p class="description">Prezentacje będą automatycznie przełączane zgodnie z czasem określonym w harmonogramie.</p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button('Zapisz ustawienia'); ?>
    </form>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
        <h2 class="text-lg font-semibold mb-4">Informacje systemowe</h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">Katalog uploadów</th>
                <td>
                    <?php 
                    $upload_dir = wp_upload_dir();
                    $cm_upload_dir = $upload_dir['basedir'] . '/conference-manager/';
                    echo esc_html($cm_upload_dir);
                    ?>
                    <span class="<?php echo is_writable($cm_upload_dir) ? 'text-green-600' : 'text-red-600'; ?>">
                        (<?php echo is_writable($cm_upload_dir) ? 'Zapisywalny' : 'Tylko do odczytu'; ?>)
                    </span>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Limit pamięci PHP</th>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            
            <tr>
                <th scope="row">Maksymalny rozmiar pliku (PHP)</th>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
            
            <tr>
                <th scope="row">Wersja pluginu</th>
                <td><?php echo CONFERENCE_MANAGER_VERSION; ?></td>
            </tr>
            
            <tr>
                <th scope="row">Wersja WordPress</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
        </table>
        
        <!-- Cleanup Actions -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-md font-medium mb-3">Narzędzia konserwacji</h3>
            <p class="mb-4">
                <button type="button" class="button" id="cleanup-files">
                    Oczyść nieużywane pliki
                </button>
                <span class="description ml-2">Usuwa pliki, które nie są używane przez żadne wydarzenie.</span>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cleanupBtn = document.getElementById('cleanup-files');
    
    if (cleanupBtn) {
        cleanupBtn.addEventListener('click', function() {
            if (!confirm('Czy na pewno chcesz usunąć nieużywane pliki? Ta operacja jest nieodwracalna.')) {
                return;
            }
            
            const button = this;
            const originalText = button.textContent;
            
            button.textContent = 'Czyszczenie...';
            button.disabled = true;
            
            // Make AJAX request to cleanup files
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=cm_cleanup_files&nonce=<?php echo wp_create_nonce('cm_admin_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Nieużywane pliki zostały pomyślnie usunięte.');
                } else {
                    alert('Wystąpił błąd podczas czyszczenia plików: ' + (data.data || 'Nieznany błąd'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem.');
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
        });
    }
});
</script>

<style>
.text-green-600 {
    color: #059669;
}

.text-red-600 {
    color: #dc2626;
}

.border-t {
    border-top-width: 1px;
}

.border-gray-200 {
    border-color: #e5e7eb;
}

.pt-6 {
    padding-top: 1.5rem;
}

.mt-6 {
    margin-top: 1.5rem;
}

.mb-3 {
    margin-bottom: 0.75rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.ml-2 {
    margin-left: 0.5rem;
}
</style>