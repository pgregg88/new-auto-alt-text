<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function naat_process_alt_text() {
    // Use get_plugin_data() to retrieve plugin header data
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/new-auto-alt-text/new-auto-alt-text.php');
    $version = $plugin_data['Version'];
    $openai_api_token = get_option('naat_openai_api_token');
    $authorized_post_types = get_option('naat_authorized_post_types');
    $single_post_id = get_option('naat_single_post');
    $multi_post = get_option('naat_multi_post');
    $replace_alt_text = get_option('naat_replace_alt_text');
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    
    $post_types = explode(',', $authorized_post_types);
    $posts = [];

    // Add a log entry indicating the start of a new job
    file_put_contents($log_file, "Starting new job: New Auto Alt Text Plugin v{$version}\n", FILE_APPEND);

    if ($multi_post) {
        if (strpos($multi_post, '-') !== false) {
            list($start, $end) = explode('-', $multi_post);
            $posts = get_posts([
                'post_type' => $post_types,
                'numberposts' => -1,
                'include' => range($start, $end),
            ]);
        } else {
            $posts = get_posts([
                'post_type' => $post_types,
                'numberposts' => -1,
                'include' => explode(',', $multi_post),
            ]);
        }
    } elseif ($single_post_id) {
        $posts = [get_post($single_post_id)];
    } else {
        $posts = get_posts([
            'post_type' => $post_types,
            'numberposts' => -1,
        ]);
    }

    foreach ($posts as $post) {
        $meta_keys_to_check = [];
        $alt_text_keys = [];
        $log_entries = [];

        $log_entries[] = "Processing post ID {$post->ID} ({$post->post_type}): {$post->post_title}";

        if ($post->post_type === 'capabilities') {
            $meta_keys_to_check = ['hero_image', 'tile_image', '_thumbnail_id'];
            $alt_text_keys = ['hero_image_alt_txt', 'tile_image_alt_txt'];
        } elseif ($post->post_type === 'consulting-services') {
            $meta_keys_to_check = ['hero_image', 'tile_image', 'uc_graphic_0', 'uc_graphic_1', 'uc_graphic_2', '_thumbnail_id'];
            $alt_text_keys = ['hero_image_alt_txt', 'tile_image_alt_txt', 'uc_graphic_alt_txt_0', 'uc_graphic_alt_txt_1', 'uc_graphic_alt_txt_2'];
        }

        foreach ($meta_keys_to_check as $meta_key) {
            $image_id = get_post_meta($post->ID, $meta_key, true);
            if ($image_id) {
                $alt_text_key = array_shift($alt_text_keys);
                $alt_text = get_post_meta($post->ID, $alt_text_key, true);

                // Convert array alt text to string if necessary
                if (is_array($alt_text)) {
                    $alt_text = implode(', ', $alt_text);
                }

                // Check for invalid 'Array' alt text
                if ($alt_text === 'Array' || strpos($alt_text, 'Array,') !== false) {
                    $alt_text = '';
                }

                if (empty($alt_text) || $replace_alt_text) {
                    $focus_keyword = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true);
                    $prompt = $focus_keyword ? "Generate a unique, SEO-friendly alt text for an image related to '{$focus_keyword}'. Ensure the description is varied and specific to the image." : "Generate a unique, SEO-friendly alt text for an image related to '{$post->post_title}'. Ensure the description is varied and specific to the image.";
                    $generated_alt_text = naat_generate_alt_text($openai_api_token, $prompt, $log_file);

                    if ($generated_alt_text && $generated_alt_text !== 'Error generating alt text') {
                        // Remove any unnecessary quotes around the alt text
                        $generated_alt_text = trim($generated_alt_text, '"');
                        update_post_meta($post->ID, $alt_text_key, $generated_alt_text);
                        $log_entries[] = "Updated alt text for image ID {$image_id} ({$meta_key}) to '{$generated_alt_text}'";
                    } else {
                        $log_entries[] = "Failed to generate alt text for image ID {$image_id} ({$meta_key})";
                    }
                } else {
                    $log_entries[] = "Skipped image ID {$image_id} ({$meta_key}) as it already has alt text '{$alt_text}'";
                }
            } else {
                $log_entries[] = "No image found for meta key '{$meta_key}'";
            }
        }

        // Write essential log entries to the log file
        $log_entry = implode("\n", $log_entries) . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

function naat_generate_alt_text($api_token, $prompt, $log_file) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant that generates SEO-friendly alt text for images.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_tokens' => 60,
        'temperature' => 0.7,
    ];
    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($data),
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        file_put_contents($log_file, "API request error: {$error_message}\n", FILE_APPEND);
        return 'Error generating alt text';
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (isset($result['error'])) {
        file_put_contents($log_file, "API response error: {$result['error']['message']}\n", FILE_APPEND);
        return 'Error generating alt text';
    }

    return $result['choices'][0]['message']['content'] ?? 'Error generating alt text';
}

if (isset($_POST['naat_add_alt_text'])) {
    naat_process_alt_text();
}
