<?php
/*
* Plugin Name: Healthlynked
* Description: Manage appointments of HEALTHLYNKED from WP Sites.
* Version:     1.0
* Author:      Healthlynked
* Author URI:  https://www.healthlynked.com/
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: twhl
* Domain Path: /languages
*/

defined('ABSPATH') or die('Sorry!!!');

add_action('plugins_loaded', 'twhl_plugin_load');
add_action('admin_menu', 'twhl_admin_menu');
add_action('admin_enqueue_scripts', 'twhl_load_scripts');

function twhl_plugin_load()
{
    load_plugin_textdomain('twhl', false, basename(dirname(__FILE__)).'/languages');
}

global $twhl_db_version;
$twhl_db_version = '1.0.0';
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}
require_once ABSPATH.'wp-admin/includes/upgrade.php';

function twhl_admin_menu()
{
    add_menu_page(__('Appointments', 'twhl'), __('Appointments', 'twhl'), 'activate_plugins', 'appointments', 'twhl_doctors_page_handler');

    add_submenu_page('appointments', __('Login', 'twhl'), __('Login', 'twhl'), 'activate_plugins', 'settings', 'twhl_settings_form_page_handler');
    add_submenu_page('appointments', __('Preferences', 'twhl'), __('Preferences', 'twhl'), 'activate_plugins', 'preferences', 'twhl_preferences_form_page_handler');

    //hidden menu
    add_submenu_page('appointments', __('', 'twhl'), __('', 'twhl'), 'activate_plugins', 'doctor_details', 'twhl_doctor_details_page_handler');
    add_submenu_page('appointments', __('', 'twhl'), __('', 'twhl'), 'activate_plugins', 'get_timeslots', 'twhl_get_timeslots_page_handler');
}

function twhl_languages()
{
    load_plugin_textdomain('twhl', false, dirname(plugin_basename(__FILE__)));
}
function twhl_load_scripts()
{
    //load the required css files
    wp_enqueue_style('bootstrap-min-css', plugins_url('resources/css/bootstrap.min.css', __FILE__));

    //color pickers
    wp_enqueue_style('bootstrap-formhelpers-css', plugins_url('resources/css/bootstrap-formhelpers.css', __FILE__));
    wp_enqueue_style('bootstrap-formhelpers-min-css', plugins_url('resources/css/bootstrap-formhelpers.min.css', __FILE__));

    wp_enqueue_style('hlinked-style-css', plugins_url('resources/css/hlinked-style.css', __FILE__));

    //jquery libraries
    wp_enqueue_script('moment-min-js', plugins_url('resources/lib/moment.min.js', __FILE__));

    //full calendar
    wp_enqueue_script('fullcalendar-min-js', plugins_url('resources/lib/fullcalendar/fullcalendar.min.js', __FILE__));
    wp_enqueue_style('fullcalendar-css', plugins_url('resources/lib/fullcalendar/fullcalendar.css', __FILE__));

    wp_enqueue_script('doctors-js', plugins_url('resources/js/doctors.js', __FILE__));
    wp_enqueue_script('appointments-js', plugins_url('resources/js/appointments.js', __FILE__));
    wp_enqueue_script('settings-js', plugins_url('resources/js/settings.js', __FILE__));
    wp_enqueue_style('hlinked-style-css', plugins_url('resources/css/hlinked-style.css', __FILE__));

    wp_enqueue_script('bootstrap-min-js', plugins_url('resources/lib/bootstrap.min.js', __FILE__));

    //color pickers JS Files
    wp_enqueue_script('bootstrap-formhelpers-js', plugins_url('resources/lib/bootstrap-formhelpers.js', __FILE__));
    wp_enqueue_script('bootstrap-formhelpers-min-js', plugins_url('resources/lib/bootstrap-formhelpers.min.js', __FILE__));
}
function twhl_fix_cuar_and_theme_bootstrap_conflict()
{
    if (function_exists('cuar_is_customer_area_page')
        && (cuar_is_customer_area_page(get_queried_object_id())
            || cuar_is_customer_area_private_content(get_the_ID()))) {
        wp_dequeue_script('bootstrap-scripts');
    }
}
add_action('wp_enqueue_scripts', 'twhl_fix_cuar_and_theme_bootstrap_conflict', 20);


//load the sub modules
include_once 'settings.php';
include_once 'doctors.php';
include_once 'functions.php';
include_once 'shortcode.php';
// include_once 'shortcode_1_0.php';
include_once 'preferences.php';
add_action('wp', 'healthlynked_schedule_cron');
function healthlynked_schedule_cron() {
  if ( !wp_next_scheduled( 'healthlynked_cron' ) )
    wp_schedule_event(time(), 'daily', 'healthlynked_cron');
}
add_action('healthlynked_cron', 'twhl_revalidateuser');
#add_action('wp_head', 'myplugin_cron_function'); //test on page load

// the actual function
function twhl_revalidateuser() {

    global $wpdb;
    try{
        $table_name = $wpdb->prefix.'hld_settings';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name where id=%s order by id DESC limit 1", 1), ARRAY_A);
        if (!empty($data)) {
            $ldata = $data[0];
        }
        $login = twhl_api_requests::verifyLogin(array('token' => $ldata['token']));
            if ($login) {
                if ($login['status']) {
                    $check = twhl_settings_List_Table::checklogin();
                    if (!empty($check)) {
                            $logdata = array(
                                'token' => $login['token'],
                                'updated_on' => date('Y-m-d H:i:s'),
                            );
                            $result = $wpdb->update($table_name, $logdata, array('id' => $ldata['id']));
                    }
                }
        }
    }
    catch(Exception $e){
        error_log($e->getMessage());
    }
}
