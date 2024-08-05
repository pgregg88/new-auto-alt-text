<?php
// Add a settings page to manage logging
function naat_register_settings_page() {
    add_options_page(
        'New Auto Alt Text Settings',
        'New Auto Alt Text',
        'manage_options',
        'new-auto-alt-text',
        'naat_render_settings_page'
    );
}
add_action('admin_menu', 'naat_register_settings_page');

// Render the settings page
function naat_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('New Auto Alt Text Settings', 'new-auto-alt-text'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('naat_settings_group');
            do_settings_sections('new-auto-alt-text');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function naat_register_settings() {
    register_setting('naat_settings_group', 'naat_logging_enabled');

    add_settings_section('naat_settings_section', '', null, 'new-auto-alt-text');

    add_settings_field('naat_logging_enabled', 'Enable Logging', 'naat_render_logging_enabled', 'new-auto-alt-text', 'naat_settings_section');
}
add_action('admin_init', 'naat_register_settings');

// Render the logging enabled field
function naat_render_logging_enabled() {
    $logging_enabled = get_option('naat_logging_enabled');
    ?>
    <input type="checkbox" name="naat_logging_enabled" value="1" <?php checked($logging_enabled, '1'); ?> />
    <?php
}
