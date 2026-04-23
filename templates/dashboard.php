<?php
use WP_Span_Checker\Dashboard;

$dashboard = new Dashboard();
$summary = $dashboard->get_summary();
$spam_logs = $dashboard->get_spam_logs();
?>

<div class="wrap">
    <h1>VMS Spam Checker Dashboard</h1>

    <h2>Summary</h2>
    <table class="widefat fixed striped">
        <tr><th>Whitelisted Domains</th><td><?php echo esc_html($summary['whitelist_count']); ?></td></tr>
        <tr><th>Disposable Domains</th><td><?php echo esc_html($summary['disposable_count']); ?></td></tr>
        <tr><th>Login Attempts</th><td><?php echo esc_html($summary['login_attempts']); ?></td></tr>
        <tr><th>Registration Attempts</th><td><?php echo esc_html($summary['registration_attempts']); ?></td></tr>
        <tr><th>Spam Logs</th><td><?php echo esc_html($summary['spam_logs']); ?></td></tr>
    </table>

    <h2>Latest Spam Logs</h2>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th>ID</th><th>Type</th><th>IP</th><th>Domain</th><th>Status</th><th>Message</th><th>Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($spam_logs as $log): ?>
            <tr>
                <td><?php echo esc_html($log['id']); ?></td>
                <td><?php echo esc_html($log['type']); ?></td>
                <td><?php echo esc_html($log['ip']); ?></td>
                <td><?php echo esc_html($log['domain']); ?></td>
                <td><?php echo esc_html($log['status']); ?></td>
                <td><?php echo esc_html($log['message']); ?></td>
                <td><?php echo esc_html($log['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
