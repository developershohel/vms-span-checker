<div class="wrap">
    <h1>Form Builder</h1>

    <!-- React app mounts here -->
    <div id="wp-span-react-form-builder"></div>

    <form method="post">
        <input type="text" name="form_name" placeholder="Form Name" required>
        <input type="text" name="form_class" placeholder="Form Class">

        <!-- This will get updated by React -->
        <textarea id="wsc-fields" name="fields" placeholder="JSON Fields"></textarea>

        <input type="submit" class="button button-primary" value="Add Form">
    </form>
</div>
