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
    // Get current settings
    $logging_enabled = get_option('naat_logging_enabled');
    $openai_api_token = get_option('naat_openai_api_token');
    $authorized_post_types = get_option('naat_authorized_post_types');
    $single_post_id = get_option('naat_single_post');
    $multi_post = get_option('naat_multi_post');

    // Get log file content
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $log_content = file_exists($log_file) ? file_get_contents($log_file) : 'Log file is empty or does not exist.';

    // Fetch the selected single post information
    $single_post = null;
    $single_post_meta_info = [];
    if ($single_post_id) {
        $single_post = get_post($single_post_id);
        if ($single_post && $single_post->post_type !== 'attachment') {
            $attachments = get_attached_media('image', $single_post_id);
            foreach ($attachments as $attachment) {
                $meta_keys = get_post_custom_keys($attachment->ID);
                $meta_info = [];
                foreach ($meta_keys as $key) {
                    // Filter out unwanted meta keys
                    if (in_array($key, ['image_optimizer_metadata', '_wp_attachment_metadata', 'siteground_optimizer_optimization_attempts', 'siteground_optimizer_optimization_failed'])) {
                        continue;
                    }
                    $meta_value = get_post_meta($attachment->ID, $key, true);
                    $meta_info[$key] = empty($meta_value) ? 'n/a' : $meta_value;
                }
                $single_post_meta_info[] = [
                    'attachment_id' => $attachment->ID,
                    'meta_info' => $meta_info,
                ];
            }
        }
    }

    ?>
    <div class="wrap" style="display: flex;">
        <div style="flex: 0 1 60%; max-width: 60%; padding-right: 20px;">
            <h1><?php esc_html_e('New Auto Alt Text Settings', 'new-auto-alt-text'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('naat_settings_group');
                do_settings_sections('new-auto-alt-text');
                submit_button('Save Config');
                ?>
            </form>
            <h2>Log File</h2>
            <textarea readonly rows="20" cols="100" style="width: 100%;"><?php echo esc_textarea($log_content); ?></textarea>
            <form method="post" action="">
                <button type="submit" name="refresh_log" class="button">Refresh Log</button>
            </form>
        </div>
        <div style="flex: 0 1 40%; max-width: 40%;">
            <?php if ($single_post) : ?>
                <h2>Selected Post Information</h2>
                <p><strong>Post Type:</strong> <?php echo esc_html($single_post->post_type); ?></p>
                <p><strong>Post Title:</strong> <?php echo esc_html($single_post->post_title); ?></p>
                <p><strong>Post ID:</strong> <?php echo esc_html($single_post->ID); ?></p>
                <h3>Image Meta Information</h3>
                <ul>
                    <?php foreach ($single_post_meta_info as $info) : ?>
                        <li>
                            <strong>Image ID:</strong> <?php echo esc_html($info['attachment_id']); ?>
                            <ul>
                                <?php foreach ($info['meta_info'] as $key => $value) : ?>
                                    <li><?php echo esc_html($key); ?>: <?php echo esc_html($value); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>No images found or no post selected.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Register settings
function naat_register_settings() {
    register_setting('naat_settings_group', 'naat_logging_enabled');
    register_setting('naat_settings_group', 'naat_openai_api_token');
    register_setting('naat_settings_group', 'naat_authorized_post_types');
    register_setting('naat_settings_group', 'naat_single_post');
    register_setting('naat_settings_group', 'naat_multi_post');

    add_settings_section('naat_settings_section', '', null, 'new-auto-alt-text');

    add_settings_field('naat_logging_enabled', 'Enable Logging', 'naat_render_logging_enabled', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_openai_api_token', 'OpenAI API Token', 'naat_render_openai_api_token', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_authorized_post_types', 'Authorized Post Types', 'naat_render_authorized_post_types', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_single_post', 'Single Post Selector', 'naat_render_single_post', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_multi_post', 'Multi Post Selector', 'naat_render_multi_post', 'new-auto-alt-text', 'naat_settings_section');
}
add_action('admin_init', 'naat_register_settings');

// Render the logging enabled field
function naat_render_logging_enabled() {
    $logging_enabled = get_option('naat_logging_enabled');
    ?>
    <input type="checkbox" name="naat_logging_enabled" value="1" <?php checked($logging_enabled, '1'); ?> />
    <?php
}

// Render the OpenAI API token field
function naat_render_openai_api_token() {
    $openai_api_token = get_option('naat_openai_api_token');
    ?>
    <input type="text" name="naat_openai_api_token" value="<?php echo esc_attr($openai_api_token); ?>" class="regular-text" />
    <?php
}

// Render the authorized post types field
function naat_render_authorized_post_types() {
    $authorized_post_types = get_option('naat_authorized_post_types');
    ?>
    <input type="text" name="naat_authorized_post_types" value="<?php echo esc_attr($authorized_post_types); ?>" class="regular-text" />
    <p class="description">Enter content post types separated by commas (e.g., post,page). 'attachments' is always included.</p>
    <?php
}

// Render the single post selector field
function naat_render_single_post() {
    $single_post = get_option('naat_single_post');
    $authorized_post_types = get_option('naat_authorized_post_types');
    $post_types = array_merge(explode(',', $authorized_post_types), ['attachments']); // Ensure attachments is included

    // Get all posts for the given post types
    $posts = get_posts([
        'post_type' => $post_types,
        'posts_per_page' => -1, // Ensure all posts are retrieved
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <select name="naat_single_post">
        <option value="">-- Select Post --</option>
        <?php foreach ($posts as $post) : ?>
            <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($single_post, $post->ID); ?>>
                <?php echo esc_html($post->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// Render the multi post selector field
function naat_render_multi_post() {
    $multi_post = get_option('naat_multi_post');
    ?>
    <input type="text" name="naat_multi_post" value="<?php echo esc_attr($multi_post); ?>" class="regular-text" />
    <p class="description">Enter post IDs separated by commas or a range (e.g., 1,2,3 or 1-10).</p>
    <?php
}

// Refresh log content upon button click
if (isset($_POST['refresh_log'])) {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $log_content = file_exists($log_file) ? file_get_contents($log_file) : 'Log file is empty or does not exist.';
    add_action('admin_notices', function() use ($log_content) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Log file refreshed.</p>
            <textarea readonly rows="20" cols="100" style="width: 100%;"><?php echo esc_textarea($log_content); ?></textarea>
        </div>
        <?php
    });
}

// Log the settings update
function naat_log_settings_update() {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $time = current_time('Y-m-d H:i:s');
    $version = '1.0'; // Update this to your plugin version
    $log_entry = "{$time} - Plugin updated to version {$version}. Settings saved:\n";
    $log_entry .= "Logging Enabled: " . get_option('naat_logging_enabled') . "\n";
    $log_entry .= "OpenAI API Token: " . get_option('naat_openai_api_token') . "\n";
    $log_entry .= "Authorized Post Types: " . get_option('naat_authorized_post_types') . "\n";
    $log_entry .= "Single Post: " . get_option('naat_single_post') . "\n";
    $log_entry .= "Multi Post: " . get_option('naat_multi_post') . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
add_action('update_option_naat_logging_enabled', 'naat_log_settings_update');
add_action('update_option_naat_openai_api_token', 'naat_log_settings_update');
add_action('update_option_naat_authorized_post_types', 'naat_log_settings_update');
add_action('update_option_naat_single_post', 'naat_log_settings_update');
add_action('update_option_naat_multi_post', 'naat_log_settings_update');
