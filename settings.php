<?php

function twhl_install_hld_settings()
{
    global $wpdb;
    global $twhl_db_version;

    $table_name = $wpdb->prefix.'hld_settings';

    $sql = 'CREATE TABLE '.$table_name.' (
      id int(11) NOT NULL AUTO_INCREMENT,
      emailid varchar(255) NOT NULL,
      token text NOT NULL,
      role varchar(100) NULL,
      updated_on datetime null,
      PRIMARY KEY  (id)
    );';

    dbDelta($sql);
}

register_activation_hook(__FILE__, 'twhl_install_hld_settings');
function twhl_install_hld_settings_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix.'hld_settings';
}
register_activation_hook(__FILE__, 'twhl_install_hld_settings_data');

function twhl_update_db_check_hld_settings()
{
    global $twhl_db_version;
    if (get_site_option('twhl_db_version') != $twhl_db_version) {
        twhl_install_hld_settings();
    }
}

add_action('plugins_loaded', 'twhl_update_db_check_hld_settings');

class twhl_settings_List_Table extends WP_List_Table
{
    public function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'hld_setting',
            'plural' => 'hld_settings',
        ));
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    public function column_name($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=hld_settings_form&id=%s">%s</a>', $item['id'], __('Edit', 'twhl')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'twhl')),
        );

        return sprintf('%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'token' => __('Token', 'twhl'),
            'updated_on' => __('Last Updated', 'twhl'),
        );

        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'role' => array('role', true),
        );

        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete',
        );

        return $actions;
    }

    public static function checklogin()
    {
        global $wpdb;
        @ini_set('display_errors', 0);
        $table_name = $wpdb->prefix.'hld_settings';
        $data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name where id=%s order by id DESC limit 1", 1), ARRAY_A);
        if (!empty($data)) {
            return $data[0];
        }

        return false;
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'hld_settings';

        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT *,concat('<img src=',photo,' height=50 />') as photo FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }
}
function twhl_settings_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix.'hld_settings';

    $message = '';
    $notice = '';
    $form = array('email' => '', 'password' => '');
    if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        $f_data = shortcode_atts($form, $_REQUEST);

        $form_valid = twhl_validate_hld_setting($f_data);
        if ($form_valid === true) {
            $login = twhl_api_requests::getlogin(array('email' => $f_data['email'], 'password' => $f_data['password'], 'timezone' => 'Asia/Calcutta'));
            if ($login) {
                if ($login['status']) {
                    $check = twhl_settings_List_Table::checklogin();
                    if (!empty($check)) {
                        //update the token
                        $logdata = array(
                            'emailid' => $f_data['email'],
                            'role' => $login['role'],
                            'token' => $login['token'],
                            'updated_on' => date('Y-m-d H:i:s'),
                        );
                        $result = $wpdb->update($table_name, $logdata, array('id' => 1));
                    // var_dump($f_data);
                    } else {
                        //insert the token
                        $logdata = array(
                            'id' => 0,
                            'emailid' => $f_data['email'],
                            'role' => $login['role'],
                            'token' => $login['token'],
                            'updated_on' => date('Y-m-d H:i:s'),
                        );
                        $result = $wpdb->insert($table_name, $logdata);
                        $f_data['id'] = $wpdb->insert_id;
                    }
                    $message = __('Logged data saved successfully saved', 'twhl');
                } else {
                    $notice = __('There was an error while login', 'twhl');
                }
            } else {
                $notice = __('There was an error while login', 'twhl');
            }
        } else {
            $notice = $form_valid;
        }
    } else {
        $f_data = $form;
        if (isset($_REQUEST['id'])) {
            $f_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$f_data) {
                $f_data = $form;
                $notice = __('User not found', 'twhl');
            }
        }
    }

    //check logged in or not
    $check = twhl_settings_List_Table::checklogin();
    add_meta_box('hld_settings_form_meta_box', __('Login info', 'twhl'), 'twhl_settings_form_meta_box_handler', 'hld_setting', 'normal', 'default'); ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Login', 'twhl'); ?></h2>

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
        ?><div id="notice" class="error"><p>Plugin settings has not done.</p></div><?php
    } ?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?= wp_create_nonce(basename(__FILE__)); ?>"/>
        
        <input type="hidden" name="id" value="<?= $f_data['id']; ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    
                    <?php do_meta_boxes('hld_setting', 'normal', $f_data); ?>
                    
                </div>
            </div>
        </div>
    </form>
</div>

<?php
}

function twhl_settings_form_meta_box_handler($item)
{
    ?>
<tbody>
    <style>
    </style>    
        
    <div class="formdata">      
        
    <form>
        <p>         
            <label for=" email"><?php _e('Email:', 'twhl'); ?></label>
        <br>    
            <input id="email" name="email" type="email" style="width: 30%" value="<?= esc_attr($item['email']); ?>" required>
        </p>
        <p>         
            <label for="Password"><?php _e('Password:', 'twhl'); ?></label>
        <br>    
            <input id="password" name="password" type="password" style="width: 30%"  required>
        </p>
        <input type="submit" value="<?php _e('Save', 'twhl'); ?>" id="submit" class="button-primary" name="submit">
        </form>
        </div>
</tbody>
<?php
}

function twhl_validate_hld_setting($item)
{
    $messages = array();

    if (empty($item['email'])) {
        $messages[] = __('Email is required', 'twhl');
    }

    if (empty($item['password'])) {
        $messages[] = __('Password is required', 'twhl');
    }
    if (empty($messages)) {
        return true;
    }

    return implode('<br />', $messages);
}
