<?php

function twhl_install_hld_preferences()
{
    global $wpdb;
    global $twhl_db_version;

    $table_name = $wpdb->prefix.'hld_preferences';

    $sql = 'CREATE TABLE '.$table_name.' (
      id int(11) NOT NULL AUTO_INCREMENT,
      background_color varchar(15) NULL,
      text_color varchar(15) NULL,
      created_on datetime null,
      updated_on datetime null,
      PRIMARY KEY  (id)
    );';

    dbDelta($sql);
}

register_activation_hook(__FILE__, 'twhl_install_hld_preferences');
function twhl_install_hld_preferences_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix.'hld_preferences';
}
register_activation_hook(__FILE__, 'twhl_install_hld_preferences_data');

function twhl_update_db_check_hld_preferences()
{
    global $twhl_db_version;
    if (get_site_option('twhl_db_version') != $twhl_db_version) {
        twhl_install_hld_preferences();
    }
}

add_action('plugins_loaded', 'twhl_update_db_check_hld_preferences');

class hld_preferences_List_Table extends WP_List_Table
{
    public function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'hld_preference',
            'plural' => 'hld_preferences',
        ));
    }


    public static function check_settings()
    {
        global $wpdb;
        @ini_set('display_errors', 0);
        $table_name = $wpdb->prefix.'hld_preferences';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name where 1=%s",1), ARRAY_A);
        if (!empty($data)) {
            return $data[0];
        }

        return false;
    }
}
function twhl_preferences_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix.'hld_preferences';

    $message = '';
    $notice = '';
    $f_data = array(
        'id'=>0,
        'background_color' => '',
        'text_color' => ''
    );
    if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        $f_data = shortcode_atts($f_data, $_REQUEST);
        $form_valid = twhl_validate_hld_preference($f_data);
        if ($form_valid === true) {
            if ($f_data['id'] == 0) {
                //insert the record
                $f_data['created_on'] = date('Y-m-d H:i:s');
                $result = $wpdb->insert($table_name, $f_data);
                $f_data['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Preference was successfully saved', 'hlns');
                } else {
                    $notice = __('There was an error while saving item', 'hlns');
                }
            }
            else{
                //update with respect to id
                $f_data['updated_on'] = date('Y-m-d H:i:s');
                $result = $wpdb->update($table_name, $f_data, array('id'=>$f_data['id']));
                $f_data['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Preference was successfully saved', 'hlns');
                } else {
                    $notice = __('There was an error while saving item', 'hlns');
                }
            }
        } else {
            $notice = $form_valid;
        }
    } 
    
    $check_preference=hld_preferences_List_Table::check_settings();
    if (!empty($check_preference)) {
        $f_data = $check_preference;
    }
    
    //check logged in or not
    $check = twhl_settings_List_Table::checklogin();
    
    add_meta_box('hld_preferences_form_meta_box', __('Preferences', 'twhl'), 'twhl_preferences_form_meta_box_handler', 'hld_preference', 'normal', 'default'); ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Preferences', 'twhl'); ?></h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?= $notice; ?></p></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?= $message; ?></p></div>
    <?php endif; ?>
    <?php
    if (!empty($check)) {
        ?><div id="notice" class="updated"><p>Plugin has logged in as <strong><?= $check['emailid']; ?></strong></p></div><?php
    } else {
        ?><div id="notice" class="error"><p>Plugin Settings has not done.</p></div><?php
    } ?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?= wp_create_nonce(basename(__FILE__)); ?>"/>
        
        <input type="hidden" name="id" value="<?= $f_data['id']; ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    
                    <?php do_meta_boxes('hld_preference', 'normal', $f_data); ?>
                    
                </div>
            </div>
        </div>
    </form>
</div>

<?php
}

function twhl_preferences_form_meta_box_handler($item)
{
    ?>
<tbody>
    <style>
    </style>    
        
    <div class="formdata">
    <form>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Background color</label>
                    <div class="bfh-colorpicker" data-name="background_color" data-color="<?= esc_attr($item['background_color']); ?>">
                    </div>
                </div>        
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Text color</label>
                    <div class="bfh-colorpicker" data-name="text_color" data-color="<?= esc_attr($item['text_color']); ?>">
                    </div>
                </div>        
            </div>
        </div>
        
        <input type="submit" value="<?php _e('Save', 'twhl'); ?>" id="submit" class="button-primary" name="submit">
        </form>
        </div>
</tbody>
<?php
}

function twhl_validate_hld_preference($item)
{
    $messages = array();

    //if (empty($item['background_color'])) {
//        $messages[] = __('background color is required', 'twhl');
//    }
    
    if (empty($messages)) {
        return true;
    }

    return implode('<br />', $messages);
}
