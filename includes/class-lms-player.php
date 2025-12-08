<?php
if (!defined('ABSPATH')) {
    exit;
}

class IC_LMS_Player {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_player_assets'],100);
    }

    function enqueue_player_assets() {
        if (!is_singular('course')) {
            return;
        }

        wp_enqueue_script(
            'ic-lms-tailwind',
            '//cdn.tailwindcss.com',
            [],
            null,
            false
        );

        // Enqueue Google Fonts
        wp_enqueue_style(
            'ic-lms-google-fonts',
            '//fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            null
        );

        // Enqueue Font Awesome
        wp_enqueue_style(
            'ic-lms-fontawesome',
            '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Enqueue Highlight.js for syntax highlighting
        wp_enqueue_style(
            'ic-lms-highlightjs-css',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css',
            [],
            '11.9.0'
        );

        wp_enqueue_script(
            'ic-lms-highlightjs',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
            [],
            '11.9.0',
            false
        );

        wp_enqueue_style(
            'ic-lms-player-css',
            IC_LMS_PLUGIN_URL . 'assets/style.css',
            [],
            IC_LMS_VERSION
        );

        wp_enqueue_script(
            'ic-lms-player-js',
            IC_LMS_PLUGIN_URL . 'assets/course-player.js',
            [],
            IC_LMS_VERSION,
            false
        );

        $course_id = get_the_ID();
        $api_url = rest_url('ic-lms/v1/course/' . $course_id);

        wp_localize_script(
            'ic-lms-player-js',
            'icLmsConfig',
            [
                'apiUrl' => $api_url,
                'courseId' => $course_id,
                'restNonce' => wp_create_nonce('wp_rest')
            ]
        );
        // Enqueue Alpine.js AFTER course-player.js
        wp_enqueue_script(
            'ic-lms-alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            ['ic-lms-player-js'],
            '3.x.x',
            true
        );

        wp_deregister_style('twentytwentyfive-style');
        wp_dequeue_style('twentytwentyfive-style');
        
    }
}