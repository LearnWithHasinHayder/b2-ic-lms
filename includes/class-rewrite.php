<?php 
if (!defined('ABSPATH')) {
    exit;
}


class IC_LMS_Rewrite {
    function __construct(){
        add_filter('single_template', [$this, 'load_single_course_template']);
    }

    function load_single_course_template($template){
        global $post;
        $custom_template = IC_LMS_PLUGIN_DIR."/single-course.php";

        if ($post->post_type == 'course') {
            if(file_exists($custom_template)){
                return $custom_template;
            }
        }

        return $template;
    }
}