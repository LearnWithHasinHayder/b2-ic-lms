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
            'methods' => 'POST',
            'callback' => array($this, 'bookmark_episode'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route('ic-lms/v1', '/user/complete', array(
            'methods' => 'POST',
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
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $course_id = isset($params['course_id']) ? absint($params['course_id']) : 0;
        $chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
        $episode_id = isset($params['episode_id']) ? absint($params['episode_id']) : 0;

        if (!$course_id || !$chapter_id || !$episode_id) {
            return new WP_Error('invalid_params', 'Missing course_id, chapter_id, or episode_id', array('status' => 400));
        }

        $bookmarks = get_user_meta($user_id, 'ic_lms_bookmarks', true);
        if (!is_array($bookmarks)) {
            $bookmarks = array();
        }

        if (!isset($bookmarks[$course_id])) {
            $bookmarks[$course_id] = array();
        }

        if (!isset($bookmarks[$course_id][$chapter_id])) {
            $bookmarks[$course_id][$chapter_id] = array();
        }

        // Toggle bookmark
        if (in_array($episode_id, $bookmarks[$course_id][$chapter_id])) {
            $bookmarks[$course_id][$chapter_id] = array_values(array_diff($bookmarks[$course_id][$chapter_id], array($episode_id)));
            $is_bookmarked = false;
        } else {
            $bookmarks[$course_id][$chapter_id][] = $episode_id;
            $is_bookmarked = true;
        }

        update_user_meta($user_id, 'ic_lms_bookmarks', $bookmarks);

        return new WP_REST_Response(array(
            'success' => true,
            'is_bookmarked' => $is_bookmarked,
            'course_id' => $course_id,
            'chapter_id' => $chapter_id,
            'episode_id' => $episode_id
        ), 200);
    }

    function mark_episode_completed($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $course_id = isset($params['course_id']) ? absint($params['course_id']) : 0;
        $chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
        $episode_id = isset($params['episode_id']) ? absint($params['episode_id']) : 0;
        $status = isset($params['status']) ? (bool) $params['status'] : true;

        if (!$course_id || !$chapter_id || !$episode_id) {
            return new WP_Error('invalid_params', 'Missing course_id, chapter_id, or episode_id', array('status' => 400));
        }

        $completed = get_user_meta($user_id, 'ic_lms_completed_episodes', true);
        if (!is_array($completed)) {
            $completed = array();
        }

        if (!isset($completed[$course_id])) {
            $completed[$course_id] = array();
        }

        if (!isset($completed[$course_id][$chapter_id])) {
            $completed[$course_id][$chapter_id] = array();
        }

        if ($status) {
            // Mark as complete
            if (!in_array($episode_id, $completed[$course_id][$chapter_id])) {
                $completed[$course_id][$chapter_id][] = $episode_id;
            }
        } else {
            // Mark as incomplete
            if (in_array($episode_id, $completed[$course_id][$chapter_id])) {
                $completed[$course_id][$chapter_id] = array_values(array_diff($completed[$course_id][$chapter_id], array($episode_id)));
            }
        }

        update_user_meta($user_id, 'ic_lms_completed_episodes', $completed);

        return new WP_REST_Response(array(
            'success' => true,
            'is_completed' => $status,
            'course_id' => $course_id,
            'chapter_id' => $chapter_id,
            'episode_id' => $episode_id
        ), 200);
    }

    function get_user_bookmarks($request) {
        $user_id = get_current_user_id();
        $course_id = $request->get_param('course_id');
        $bookmarks = get_user_meta($user_id, 'ic_lms_bookmarks', true);
        
        if (!is_array($bookmarks)) {
            $bookmarks = array();
        }

        if ($course_id) {
            $course_id = absint($course_id);
            $bookmarks = isset($bookmarks[$course_id]) ? $bookmarks[$course_id] : array();
        }

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