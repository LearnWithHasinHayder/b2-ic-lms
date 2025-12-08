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
    }

    function get_course_data($request) {
        $course_id = absint($request->get_param('id'));

        if (!$course_id) {
            return new WP_Error('invalid_course_id', 'Invalid course ID provided', array('status' => 400));
        }

        $course_post = get_post($course_id);

        if (!$course_post) {
            return new WP_Error('course_not_found', 'Course post not found', array('status' => 404));
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
                            'hasDownload' => $has_download
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