<?php
class IC_LMS_Course_API {
    function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    function register_routes() {
        register_rest_route('ic-lms/v1', '/course/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_course_data'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('ic-lms/v1', '/user/bookmark', array(
            'methods' => array('POST', 'DELETE'),
            'callback' => array($this, 'bookmark_episode'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route('ic-lms/v1', '/user/complete', array(
            'methods' => array('POST', 'DELETE'),
            'callback' => array($this, 'mark_episode_completed'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route('ic-lms/v1', '/user/bookmarks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_bookmarks'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route('ic-lms/v1', '/user/completes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_completed_episodes'),
            'permission_callback' => array($this, 'check_auth'),
        ));
    }

    function check_auth() {
        return is_user_logged_in();
    }

    function bookmark_episode($request) {
        // Get the current logged-in user's ID
        $user_id = get_current_user_id();
        // Parse JSON parameters from the request body
        $params = $request->get_json_params();
        // Extract and sanitize course_id from parameters, default to 0 if not provided
        $course_id = isset($params['course_id']) ? absint($params['course_id']) : 0;
        // Extract and sanitize chapter_id from parameters, default to 0 if not provided
        $chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
        // Extract and sanitize episode_id from parameters, default to 0 if not provided
        $episode_id = isset($params['episode_id']) ? absint($params['episode_id']) : 0;

        // Validate that all required parameters are provided and valid
        if (!$course_id || !$chapter_id || !$episode_id) {
            return new WP_Error('invalid_params', 'Missing course_id, chapter_id, or episode_id', array('status' => 400));
        }

        // Retrieve user's existing bookmarks from user meta, default to empty array if none exist
        $bookmarks = get_user_meta($user_id, 'ic_lms_bookmarks', true);
        if (!is_array($bookmarks)) {
            $bookmarks = array();
        }

        // Ensure course structure exists in bookmarks array
        if (!isset($bookmarks[$course_id])) {
            $bookmarks[$course_id] = array();
        }

        // Ensure chapter structure exists in course bookmarks
        if (!isset($bookmarks[$course_id][$chapter_id])) {
            $bookmarks[$course_id][$chapter_id] = array();
        }

        // Get the HTTP method (POST for bookmark, DELETE for unbookmark)
        $method = $request->get_method();
        if ($method === 'POST') {
            // Add bookmark: check if episode is not already bookmarked, then add it
            if (!in_array($episode_id, $bookmarks[$course_id][$chapter_id])) {
                $bookmarks[$course_id][$chapter_id][] = $episode_id;
            }
            $is_bookmarked = true;
        } elseif ($method === 'DELETE') {
            // Remove bookmark: check if episode is bookmarked, then remove it
            if (in_array($episode_id, $bookmarks[$course_id][$chapter_id])) {
                $bookmarks[$course_id][$chapter_id] = array_values(array_diff($bookmarks[$course_id][$chapter_id], array($episode_id)));
            }
            $is_bookmarked = false;
        } else {
            // Invalid HTTP method provided
            return new WP_Error('invalid_method', 'Invalid method', array('status' => 405));
        }

        // Save updated bookmarks to user meta
        update_user_meta($user_id, 'ic_lms_bookmarks', $bookmarks);

        // Return success response with bookmark status and identifiers
        return new WP_REST_Response(array(
            'success' => true,
            'is_bookmarked' => $is_bookmarked,
            'course_id' => $course_id,
            'chapter_id' => $chapter_id,
            'episode_id' => $episode_id
        ), 200);
    }

    function mark_episode_completed($request) {
        // Get the current logged-in user's ID
        $user_id = get_current_user_id();
        // Parse JSON parameters from the request body
        $params = $request->get_json_params();
        // Extract and sanitize course_id from parameters, default to 0 if not provided
        $course_id = isset($params['course_id']) ? absint($params['course_id']) : 0;
        // Extract and sanitize chapter_id from parameters, default to 0 if not provided
        $chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
        // Extract and sanitize episode_id from parameters, default to 0 if not provided
        $episode_id = isset($params['episode_id']) ? absint($params['episode_id']) : 0;

        // Validate that all required parameters are provided and valid
        if (!$course_id || !$chapter_id || !$episode_id) {
            return new WP_Error('invalid_params', 'Missing course_id, chapter_id, or episode_id', array('status' => 400));
        }

        // Retrieve user's existing completed episodes from user meta, default to empty array if none exist
        $completed = get_user_meta($user_id, 'ic_lms_completed_episodes', true);
        if (!is_array($completed)) {
            $completed = array();
        }

        // Ensure course structure exists in completed episodes array
        if (!isset($completed[$course_id])) {
            $completed[$course_id] = array();
        }

        // Ensure chapter structure exists in course completed episodes
        if (!isset($completed[$course_id][$chapter_id])) {
            $completed[$course_id][$chapter_id] = array();
        }

        // Get the HTTP method (POST for mark complete, DELETE for mark incomplete)
        $method = $request->get_method();
        if ($method === 'POST') {
            // Mark as complete: check if episode is not already completed, then add it
            if (!in_array($episode_id, $completed[$course_id][$chapter_id])) {
                $completed[$course_id][$chapter_id][] = $episode_id;
            }
            $is_completed = true;
        } elseif ($method === 'DELETE') {
            // Mark as incomplete: check if episode is completed, then remove it
            if (in_array($episode_id, $completed[$course_id][$chapter_id])) {
                $completed[$course_id][$chapter_id] = array_values(array_diff($completed[$course_id][$chapter_id], array($episode_id)));
            }
            $is_completed = false;
        } else {
            // Invalid HTTP method provided
            return new WP_Error('invalid_method', 'Invalid method', array('status' => 405));
        }

        // Save updated completed episodes to user meta
        update_user_meta($user_id, 'ic_lms_completed_episodes', $completed);

        // Return success response with completion status and identifiers
        return new WP_REST_Response(array(
            'success' => true,
            'is_completed' => $is_completed,
            'course_id' => $course_id,
            'chapter_id' => $chapter_id,
            'episode_id' => $episode_id
        ), 200);
    }

    function get_user_bookmarks($request) {
        // Get the current logged-in user's ID
        $user_id = get_current_user_id();
        // Get course_id parameter from the request URL (optional filter)
        $course_id = $request->get_param('course_id');
        // Retrieve user's bookmarks from user meta
        $bookmarks = get_user_meta($user_id, 'ic_lms_bookmarks', true);

        // Ensure bookmarks is an array, default to empty array if not set
        if (!is_array($bookmarks)) {
            $bookmarks = array();
        }

        // If course_id is provided, filter bookmarks to only show that course's bookmarks
        if ($course_id) {
            $course_id = absint($course_id);
            $bookmarks = isset($bookmarks[$course_id]) ? $bookmarks[$course_id] : array();
        }

        // Return success response with bookmarks data
        return new WP_REST_Response(array(
            'success' => true,
            'bookmarks' => $bookmarks
        ), 200);
    }

    function get_user_completed_episodes($request) {
        $user_id = get_current_user_id();
        $course_id = $request->get_param('course_id');
        $completed = get_user_meta($user_id, 'ic_lms_completed_episodes', true);
        
        if (!is_array($completed)) {
            $completed = array();
        }

        if ($course_id) {
            $course_id = absint($course_id);
            $completed = isset($completed[$course_id]) ? $completed[$course_id] : array();
        }

        return new WP_REST_Response(array(
            'success' => true,
            'completed' => $completed
        ), 200);
    }

    function get_course_data($request) {
        $course_id = absint($request->get_param('id'));
        $user_id = get_current_user_id();

        if (!$course_id) {
            return new WP_Error('invalid_course_id', 'Invalid course ID provided', array('status' => 400));
        }

        $course_post = get_post($course_id);

        if (!$course_post) {
            return new WP_Error('course_not_found', 'Course post not found', array('status' => 404));
        }

        // Retrieve user progress
        $bookmarks = array();
        $completed = array();

        if ($user_id) {
            $bookmarks_meta = get_user_meta($user_id, 'ic_lms_bookmarks', true);
            if (isset($bookmarks_meta[$course_id]) && is_array($bookmarks_meta[$course_id])) {
                $bookmarks = $bookmarks_meta[$course_id];
            }

            $completed_meta = get_user_meta($user_id, 'ic_lms_completed_episodes', true);
            if (isset($completed_meta[$course_id]) && is_array($completed_meta[$course_id])) {
                $completed = $completed_meta[$course_id];
            }
        }

        $course_data = array(
            'course' => array(
                'id' => $course_id,
                'title' => get_the_title($course_id),
                'description' => get_the_excerpt($course_id),
                'chapters' => array()
            )
        );

        if (have_rows('chapters', $course_id)) {
            $chapter_counter = 0;
            while (have_rows('chapters', $course_id)) {
                the_row();
                $chapter_counter++;

                $chapter_id = get_sub_field('chapter');
                if (!$chapter_id) {
                    continue;
                }

                $chapter_data = array(
                    'id' => $chapter_counter,
                    'title' => get_the_title($chapter_id),
                    'videos' => array()
                );

                // Get episodes for this chapter
                $episode_counter = 0;
                if (have_rows('episodes', $chapter_id)) {
                    while (have_rows('episodes', $chapter_id)) {
                        the_row();
                        $episode_counter++;

                        $episode_title = get_sub_field('title');
                        $duration = get_sub_field('duration');
                        $content_type = get_sub_field('content_type');
                        $video_type = get_sub_field('video_type');
                        $video_url = get_sub_field('vdeo_url');
                        $resource_url = get_sub_field('resource_url');
                        $content = get_sub_field('content');

                        // Determine if has downloads
                        $has_download = !empty($resource_url);

                        // Determine status - check correctly with chapter ID
                        $is_bookmarked = false;
                        if (isset($bookmarks[$chapter_counter]) && in_array($episode_counter, $bookmarks[$chapter_counter])) {
                            $is_bookmarked = true;
                        }

                        $is_completed = false;
                        if (isset($completed[$chapter_counter]) && in_array($episode_counter, $completed[$chapter_counter])) {
                            $is_completed = true;
                        }

                        $episode_data = array(
                            'id' => $episode_counter,
                            'number' => $episode_counter,
                            'title' => $episode_title,
                            'duration' => $duration,
                            'contentType' => $content_type,
                            'videoType' => $video_type,
                            'videoUrl' => $video_url,
                            'resourceUrl' => $resource_url,
                            'content' => $content,
                            'hasDownload' => $has_download,
                            'isBookmarked' => $is_bookmarked,
                            'isCompleted' => $is_completed
                        );

                        $chapter_data['videos'][] = $episode_data;
                    }
                }

                $course_data['course']['chapters'][] = $chapter_data;
            }
        }

        return new WP_REST_Response( $course_data, 200 );
    }
}