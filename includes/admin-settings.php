<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add a settings page to manage logging and API token
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
            <form method="post" action="">
                <input type="hidden" name="naat_add_alt_text" value="1">
                <?php submit_button('Add Alt Text'); ?>
            </form>
            <h2>Log File</h2>
            <?php
            $upload_dir = wp_upload_dir();
            $log_file = $upload_dir['basedir'] . '/naat_log.txt';
            $log_content = file_exists($log_file) ? file_get_contents($log_file) : 'Log file is empty or does not exist.';
            ?>
            <textarea readonly rows="20" cols="100" style="width: 100%;"><?php echo esc_textarea($log_content); ?></textarea>
            <form method="post" action="">
                <button type="submit" name="refresh_log" class="button">Refresh Log</button>
                <button type="submit" name="download_log" class="button">Download Log</button>
                <button type="submit" name="erase_log" class="button">Erase Log</button>
            </form>
        </div>
        <div style="flex: 0 1 40%; max-width: 40%;">
            <?php
            $single_post_id = get_option('naat_single_post');
            if ($single_post_id) :
                $single_post = get_post($single_post_id);
                if ($single_post && $single_post->post_type !== 'attachment') :
                    $post_edit_link = get_edit_post_link($single_post->ID);
                    $post_type_edit_link = ($single_post->post_type === 'consulting-services') ?
                        '/wp-admin/admin.php?page=jet-engine-cpt&cpt_action=edit&id=16' :
                        '/wp-admin/admin.php?page=jet-engine-cpt&cpt_action=edit&id=17';
                    ?>
                    <h2>Selected Post Information</h2>
                    <p><strong>Post Type:</strong> <a href="<?php echo esc_url($post_type_edit_link); ?>" target="_blank"><?php echo esc_html($single_post->post_type); ?></a></p>
                    <p><strong>Post Title:</strong> <a href="<?php echo esc_url($post_edit_link); ?>" target="_blank"><?php echo esc_html($single_post->post_title); ?></a></p>
                    <p><strong>Post ID:</strong> <a href="<?php echo esc_url($post_edit_link); ?>" target="_blank"><?php echo esc_html($single_post->ID); ?></a></p>
                    <h3>Image Meta Information</h3>
                    <ul>
                        <?php
                        $meta_keys_to_check = [];
                        $alt_text_keys = [];

                        if ($single_post->post_type === 'capabilities') {
                            $meta_keys_to_check = ['hero_image', 'tile_image', '_thumbnail_id'];
                            $alt_text_keys = ['hero_image_alt_txt', 'tile_image_alt_txt'];
                        } elseif ($single_post->post_type === 'consulting-services') {
                            $meta_keys_to_check = ['hero_image', 'tile_image', 'uc_graphic_0', 'uc_graphic_1', 'uc_graphic_2', '_thumbnail_id'];
                            $alt_text_keys = ['hero_image_alt_txt', 'tile_image_alt_txt', 'uc_graphic_alt_txt_0', 'uc_graphic_alt_txt_1', 'uc_graphic_alt_txt_2'];
                        }

                        foreach ($meta_keys_to_check as $meta_key) {
                            $image_id = get_post_meta($single_post->ID, $meta_key, true);
                            if ($image_id) {
                                $alt_text_key = array_shift($alt_text_keys);
                                $alt_text = get_post_meta($single_post->ID, $alt_text_key, true);

                                // Get image thumbnail
                                $thumbnail = wp_get_attachment_image_src($image_id, 'thumbnail');
                                $thumbnail_url = $thumbnail ? $thumbnail[0] : '';

                                ?>
                                <li>
                                    <strong>Image ID (<?php echo esc_html($meta_key); ?>):</strong> <?php echo esc_html($image_id); ?>
                                    <?php if ($thumbnail_url) : ?>
                                        <br><img src="<?php echo esc_url($thumbnail_url); ?>" width="100" height="100" alt="<?php echo esc_attr($alt_text); ?>">
                                    <?php endif; ?>
                                    <p><strong>Image Alt Txt (<?php echo esc_html($alt_text_key); ?>):</strong> <?php echo is_array($alt_text) ? 'n/a' : esc_html($alt_text); ?></p>
                                </li>
                                <?php
                            } else {
                                ?>
                                <li><strong><?php echo esc_html($meta_key); ?>:</strong> n/a</li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                <?php else : ?>
                    <p>No images found or no post selected.</p>
                <?php endif; ?>
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
    register_setting('naat_settings_group', 'naat_replace_alt_text'); // Add this line

    add_settings_section('naat_settings_section', '', null, 'new-auto-alt-text');

    add_settings_field('naat_logging_enabled', 'Enable Logging', 'naat_render_logging_enabled', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_openai_api_token', 'OpenAI API Token', 'naat_render_openai_api_token', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_authorized_post_types', 'Authorized Post Types', 'naat_render_authorized_post_types', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_single_post', 'Single Post Selector', 'naat_render_single_post', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_multi_post', 'Multi Post Selector', 'naat_render_multi_post', 'new-auto-alt-text', 'naat_settings_section');
    add_settings_field('naat_replace_alt_text', 'Replace Alt Text', 'naat_render_replace_alt_text', 'new-auto-alt-text', 'naat_settings_section'); // Add this line
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
    $post_types = explode(',', $authorized_post_types);

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

// Render the replace alt text field
function naat_render_replace_alt_text() {
    $replace_alt_text = get_option('naat_replace_alt_text');
    ?>
    <input type="checkbox" name="naat_replace_alt_text" value="1" <?php checked($replace_alt_text, '1'); ?> />
    <p class="description">Enable this to replace existing alt text with newly generated alt text.</p>
    <?php
}

// Handle log file actions
if (isset($_POST['refresh_log'])) {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $log_content = file_exists($log_file) ? file_get_contents($log_file) : 'Log file is empty or does not exist.';
    
    // Store the content in a variable instead of outputting immediately
    $script = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logFileTextArea = document.querySelector('textarea');
            logFileTextArea.value = " . json_encode($log_content) . ";
        });
    </script>";
    add_action('admin_footer', function() use ($script) {
        echo $script;
    });
    
} elseif (isset($_POST['download_log'])) {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    if (file_exists($log_file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($log_file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($log_file));
        readfile($log_file);
        exit;
    }
} elseif (isset($_POST['erase_log'])) {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
    }
    
    // Store the content in a variable instead of outputting immediately
    $script = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logFileTextArea = document.querySelector('textarea');
            logFileTextArea.value = 'Log file erased.';
        });
    </script>";
    add_action('admin_footer', function() use ($script) {
        echo $script;
    });
}

