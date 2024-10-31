<?php
/*
Plugin Name: s3cloud
Plugin URI: http://s3cloudplugin.com
Description: Browser for Contabo S3 Cloud. This plugin connect you with your Contabo S3 Object Storage, using S3-compatible API.
Version: 2.36
Text Domain: s3cloud
Author: Bill Minozzi
Author URI: http://billminozzi.com
License:     GPL2
Copyright (c) 2022 Bill Minozzi
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
// Make sure the file is not directly accessible.
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
$s3cloud_request_url = trim(sanitize_url($_SERVER['REQUEST_URI']));
define('S3CLOUDURL', plugin_dir_url(__file__));
$plugin = plugin_basename(__FILE__);
define('S3CLOUDPATH', plugin_dir_path(__file__));
define('S3CLOUDDOMAIN', get_site_url());
define('S3CLOUDIMAGES', plugin_dir_url(__file__) . 'images');
define('S3CLOUDPAGE', trim(sanitize_text_field($GLOBALS['pagenow'])));
define('S3CLOUDHOMEURL', admin_url());
define('S3CLOUDADMURL', admin_url());
$s3cloud_request_url = sanitize_url($_SERVER['REQUEST_URI']);
$s3cloud_plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
$s3cloud_plugin_version = sanitize_text_field($s3cloud_plugin_data['Version']);
define('S3CLOUDVERSION', sanitize_text_field($s3cloud_plugin_version));
$s3cloud_region = trim(sanitize_text_field(get_option('s3cloud_region', '')));
$s3cloud_secret_key = trim(sanitize_text_field(get_option('s3cloud_secret_key', '')));
$s3cloud_access_key = trim(sanitize_text_field(get_option('s3cloud_access_key', '')));
if (!function_exists('wp_get_current_user')) {
    require_once(ABSPATH . "wp-includes/pluggable.php");
}
require_once S3CLOUDPATH . "functions/functions.php";
add_action('admin_menu', 's3cloud_init');
function s3cloud_add_admstylesheet()
{
    global $s3cloud_request_url;
    $pos = strpos($s3cloud_request_url, 's3cloud_admin_page');
    if ($pos) {
        wp_enqueue_script('jquery');
        wp_register_style('s3cloud-css', S3CLOUDURL . 'assets/css/s3cloud.css');
        wp_enqueue_style('s3cloud-css');
        wp_register_style('s3cloud-bs', S3CLOUDURL . 'assets/css/bootstrap.min.css', false);
        wp_enqueue_style('s3cloud-bs');

        // 2024
        wp_enqueue_script('botstrap', S3CLOUDURL .
        'assets/js/bootstrap.bundle.min.js', array('jquery'));


        wp_register_style('bootstrap-treeview', S3CLOUDURL . 'assets/css/bootstrap-treeview.min.css');
        wp_enqueue_style('bootstrap-treeview');
        wp_register_style('s3cloud-bsi', S3CLOUDURL . 'assets/icons-main/font/bootstrap-icons.css', false);
        wp_enqueue_style('s3cloud-bsi');
        wp_register_script('s3cloud-list', S3CLOUDURL . 'assets/list/dist/list.js', false);
        wp_enqueue_script('s3cloud-list');
        wp_register_style('s3cloud-drop-css', S3CLOUDURL . 'assets/css/dropzone.min.css', false);
        wp_enqueue_style('s3cloud-drop-css');
        wp_register_script('s3cloud-drop-js', S3CLOUDURL . 'assets/js/dropzone.min.js', false);
        wp_enqueue_script('s3cloud-drop-js');
        if(strpos($s3cloud_request_url, 'transf') === false ) {
            wp_register_script('s3cloud-js', S3CLOUDURL . 'assets/js/s3cloud.js', false);
            wp_enqueue_script('s3cloud-js');
        }
        if(strpos($s3cloud_request_url, 'tab=trans') !== false)  {
            wp_register_script('s3cloud-filesys-js', S3CLOUDURL . 'assets/js/s3cloud_filesys.js', false);
            wp_enqueue_script('s3cloud-filesys-js');
            wp_register_script('s3cloud-copy-js', S3CLOUDURL . 'assets/js/s3cloud_copy.js', false);
            wp_enqueue_script('s3cloud-copy-js');
        }
wp_register_script('s3cloud-bootstrap-treeview', S3CLOUDURL . 'assets/js/bootstrap-treeview.min.js', false);
wp_enqueue_script('s3cloud-bootstrap-treeview');
    }
}
if (is_admin()) {
    add_action('admin_enqueue_scripts', 's3cloud_add_admstylesheet');
}
// Add settings link on plugin page
function s3cloud_plugin_settings_link($links)
{
    $settings_link = '<a href="tools.php?page=s3cloud_admin_page">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 's3cloud_plugin_settings_link');
register_activation_hook(__FILE__, 's3cloud_activated');
// Pointer
function s3cloud_activated()
{
    ob_start();
    $r = update_option('s3cloud_was_activated', '1');
    if (!$r) {
        add_option('s3cloud_was_activated', '1');
    }
    $pointers = get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true);
    $pointers = ''; 
    update_user_meta(get_current_user_id(), 'dismissed_wp_pointers', $pointers);
    ob_end_clean();
}
if (is_admin() or is_super_admin()) {
    if (get_option('s3cloud_was_activated', '0') == '1') {
        add_action('admin_enqueue_scripts', 's3cloud_adm_enqueue_scripts2');
    }
}
function s3cloud_adm_enqueue_scripts2()
{
    global $s3cloud_current_screen;
    wp_enqueue_script('wp-pointer');
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    $myscreen = get_current_screen();
    $s3cloud_current_screen = $myscreen->id;
    $dismissed_string = get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true);
    if (!empty($dismissed_string)) {
        $r = update_option('s3cloud_was_activated', '0');
        if (!$r) {
            add_option('s3cloud_was_activated', '0');
        }
        return;
    }
    if (get_option('s3cloud_was_activated', '0') == '1')
        add_action('admin_print_footer_scripts', 's3cloud_admin_print_footer_scripts');
}
function s3cloud_admin_print_footer_scripts()
{
    global $s3cloud_current_screen;
    $pointer_content = 'Open S3 cloud Here!';
    $pointer_content2 = 'Just Click Over Tools, then Click over S3 cloud.';
?>
    <script>
        jQuery(document).ready(function($) {
            $('#menu-tools').pointer({
                content: '<?php echo '<h3>' . esc_attr($pointer_content) . '</h3><p>' . esc_attr($pointer_content2); ?>',
                position: {
                    edge: 'left',
                    align: 'right'
                },
                close: function() {
                    // Once the close button is hit
                    $.post(ajaxurl, {
                        pointer: '<?php echo esc_attr($s3cloud_current_screen); ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            }).pointer('open');
        });
    </script>
<?php
}
