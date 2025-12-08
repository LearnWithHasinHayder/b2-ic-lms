<?php
/**
 * Plugin Name: IC LMS
 * Plugin URI: https://example.com/ic-lms
 * Description: A simple Learning Management System plugin
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: ic-lms
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IC_LMS_VERSION', '1.0.0');
define('IC_LMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IC_LMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IC_LMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once IC_LMS_PLUGIN_DIR . 'includes/class-rewrite.php';
require_once IC_LMS_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once IC_LMS_PLUGIN_DIR . 'includes/class-lms-player.php';

add_action('plugins_loaded', 'ic_lms_init');

function ic_lms_init(){
    new IC_LMS_Rewrite();
    new IC_LMS_Course_API();
    new IC_LMS_Player();
}