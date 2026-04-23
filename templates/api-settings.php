<?php
// Ensure options exist
$google_config     = get_option('wsc-google-config', ['secret_key' => '', 'client_id' => '', 'api_key' => '']);
$virustotal_config = get_option('wsc-virustotal-config', ['keys' => []]);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce
    if (!isset($_POST['wsc_api_nonce']) || !wp_verify_nonce($_POST['wsc_api_nonce'], 'wsc_api_action')) {
        wp_die(__('Security check failed.', 'wsc'));
    }

    // ✅ Google Config Save
    if (isset($_POST['google_config'])) {
        $google_config = [
                'secret_key' => sanitize_text_field($_POST['secret_key'] ?? ''),
                'client_id'  => sanitize_text_field($_POST['client_id'] ?? ''),
                'api_key'    => sanitize_text_field($_POST['api_key'] ?? ''),
        ];
        update_option('wsc-google-config', $google_config);
        echo '<div class="updated"><p>Google config updated!</p></div>';
    }

    // ✅ Add VirusTotal Key
    if (!empty($_POST['virustotal_key'])) {
        $new_key = sanitize_text_field($_POST['virustotal_key']);
        if (!in_array($new_key, $virustotal_config['keys'], true)) { // prevent duplicates
            $virustotal_config['keys'][] = $new_key;
            update_option('wsc-virustotal-config', $virustotal_config);
            echo '<div class="updated"><p>VirusTotal key added!</p></div>';
        }
    }

    // ✅ Delete VirusTotal Key
    if (!empty($_POST['delete_vt_key'])) {
        $keyToDelete = sanitize_text_field($_POST['delete_vt_key']);
        $virustotal_config['keys'] = array_filter($virustotal_config['keys'], fn($k) => $k !== $keyToDelete);
        update_option('wsc-virustotal-config', $virustotal_config);
        echo '<div class="updated"><p>VirusTotal key deleted!</p></div>';
    }
}
?>

<div class="wrap">
    <h1>API Settings</h1>

    <!-- Google Config -->
    <h2>Google WebRisk Config</h2>
    <form method="post">
        <?php wp_nonce_field('wsc_api_action', 'wsc_api_nonce'); ?>
        <input type="hidden" name="google_config" value="1">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="secret_key">Secret Key</label></th>
                <td><input type="text" name="secret_key" id="secret_key" value="<?php echo esc_attr($google_config['secret_key']); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="client_id">Client ID</label></th>
                <td><input type="text" name="client_id" id="client_id" value="<?php echo esc_attr($google_config['client_id']); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="api_key">API Key</label></th>
                <td><input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($google_config['api_key']); ?>" class="regular-text" required></td>
            </tr>
        </table>
        <p><input type="submit" class="button button-primary" value="Save Google Config"></p>
    </form>

    <hr>

    <!-- VirusTotal Config -->
    <h2>VirusTotal API Keys</h2>
    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('wsc_api_action', 'wsc_api_nonce'); ?>
        <input type="text" name="virustotal_key" placeholder="Enter VirusTotal API Key" class="regular-text" required>
        <input type="submit" class="button button-primary" value="Add Key">
    </form>

    <?php if (!empty($virustotal_config['keys'])): ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>API Key</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($virustotal_config['keys'] as $key): ?>
                <tr>
                    <td><?php echo esc_html($key); ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field('wsc_api_action', 'wsc_api_nonce'); ?>
                            <input type="hidden" name="delete_vt_key" value="<?php echo esc_attr($key); ?>">
                            <input type="submit" class="button button-secondary" value="Delete">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No VirusTotal API keys added yet.</p>
    <?php endif; ?>
</div>
