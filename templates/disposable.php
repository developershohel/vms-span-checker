<?php
$domain_type = 'disposable';
?>

<div class="wrap" id="wp-span-checker-wrap">
    <h1>Disposable Domains</h1>

    <form id="add-domain-form">
        <input type="hidden" name="domain_type" value="<?php echo esc_attr($domain_type); ?>">
        <input type="text" name="domain" placeholder="Enter domain" required>
        <input type="submit" class="button button-primary" value="Add Domain">
    </form>

    <hr>

    <table id="domains-table" class="display nowrap" style="min-width:320px; max-width: 750px; margin: 0 0 0;">
        <thead>
        <tr>
            <th>ID</th>
            <th>Domain</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
