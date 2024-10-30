<?php

add_action(
    'plugins_loaded',
    array(TWHL_Healthlynked_SH_TW::get_instance(), 'plugin_setup')
);

class TWHL_Healthlynked_SH_TW
{
    private $cpt = 'post'; // Adjust the CPT
    protected static $instance = null;
    public $plugin_url = '';

    public function __construct()
    {
    }

    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self();

        return self::$instance;
    }

    /**
     * Regular plugin work.
     */
    public function plugin_setup()
    {
        $this->plugin_url = plugins_url('/', __FILE__);
        add_shortcode('healthlynked', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));

        //book appointment
        add_action('wp_ajax_twhl_book_slot', array($this, 'twhl_book_slot'));
        add_action('wp_ajax_nopriv_twhl_book_slot', array($this, 'twhl_book_slot'));

        add_action('wp_ajax_twhl_load_captcha', array($this, 'twhl_load_captcha'));
        add_action('wp_ajax_nopriv_twhl_load_captcha', array($this, 'twhl_load_captcha'));

        add_action('wp_ajax_twhl_get_slot_details', 'twhl_get_slot_details');
        add_action('wp_ajax_nopriv_twhl_get_slot_details', 'twhl_get_slot_details');

        add_action('wp_ajax_twhl_get_calendar_data', 'twhl_get_calendar_data');
        add_action('wp_ajax_nopriv_twhl_get_calendar_data', 'twhl_get_calendar_data');
        
    }

    /**
     * SHORTCODE output.
     */
    // public function my_deregister_scripts()
    // {
    //     wp_deregister_script('wp-embed');
    // }

    public function shortcode($atts)
    {
        //default preferences
        $preference = array(
            'background_color' => '#3d3d3d',
            'text_color' => '#3d3d3d',
        );
        //get list of doctors
        $check = twhl_settings_List_Table::checklogin();
        $step = 0;
        $response = '<div id="healthlynked-appointment-area">';
        $response .= '<div id="healthlynked-appointment-message-area"></div>';
        $response .= '<div id="healthlynked-doctorlist-area">';
        $srcount = 0;
        $fdoc = 0;
        if (!empty($check)) {
            //fetch the preference settings
            $check_preference = hld_preferences_List_Table::check_settings();
            if (!empty($check_preference)) {
                $preference = $check_preference;
            }
            $doctors = twhl_api_requests::clinic_doctors(array('token' => $check['token']));
            if ($step == 0) {
                if ($doctors['status'] == false) {
                    $response .= '<div id="notice" class="error"><p>Something went wrong. Please try again later.</p></div>';
                } else {
                    $doctors = $doctors['doctors'];
                    $response .= '<ul style="margin:20px 0 0;">';

                    foreach ($doctors as $doctor) {
                        $doctor_details = twhl_api_requests::get_doctor_details(array('doctor_id' => $doctor['id'], 'token' => $check['token']));
                        ++$srcount;
                        $fdoc = $doctor['id'];
                        $office_details = $doctor_details['profile']['offices'][0];
                        $office_show=array();
                        if($office_details['name']!=''){
                            $office_show[]=$office_details['name'];
                        }
                        if($office_details['street1']!=''){
                            $office_show[]=$office_details['street1'];
                        }
                        if($office_details['street2']!=''){
                            $office_show[]=$office_details['street2'];
                        }
                        if($office_details['city']!=''){
                            $office_show[]=$office_details['city'];
                        }
                        if($office_details['state']!=''){
                            $office_show[]=$office_details['state'];
                        }
                        if($office_details['phone']!=''){
                            $office_show[]=$office_details['phone'];
                        }
                        if($office_details['country']!=''){
                            $office_show[]=$office_details['country'];
                        }
                        if(!empty($office_show)){
                            $office_show=implode(', ',$office_show);
                        }
                        $specialities = '';
                        $sr = 0;
                        if (!empty($doctor_details['profile']['specialities'])) {
                            foreach ($doctor_details['profile']['specialities'] as $speciality) {
                                if ($sr == 0) {
                                    $specialities .= $speciality['speciality'];
                                } else {
                                    $specialities .= ', '.$speciality['speciality'];
                                }
                                ++$sr;
                            }
                        }
                        $response .= '
                        <li style="margin-bottom: 10px;">
                            <a href="#" class="hl-doctor-list-item" data-id="'.esc_html($doctor['id']).'" data-doc-name="'.esc_html($doctor['name']).'" data-doc-office-id="'.$office_details['id'].'" data-doc-profile-image="'.esc_url($doctor['image']).'" data-doc-speciality="'.esc_html($specialities).'", data-doc-office="'.$office_show.'">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <img class="hl-doctor-list-img" src="'.esc_url($doctor['image']).'">
                                    </div>
                                    <div class="col-xs-7">
                                        <h2 class="doc_name" style="color: '.esc_html($preference['text_color']).';">'.esc_html($doctor['name']).'</h2>
                                        <p class="doc_speciality" id="doc-speciality-'.esc_html($doctor['id']).'">'.esc_html($specialities).'</p>
                                    </div>
            
                                    <div class="col-xs-3">
                                        <h6 class="doc_book_btn" style="color: '.esc_html($preference['text_color']).';">Book Now</h6>
                                    </div>
                                </div>
                            </a>
                        </li>
                        ';
                    }
                    $response .= '</ul>';
                }
            }
        } else {
            $response .= 'No Data Available';
        }
        $response .= '<input type="hidden" name="twhl_doctor_count" data-id="'.esc_html($fdoc).'" id="twhl_doctor_count" value="'.esc_html($srcount).'">';
        $response .= '</div>';
        $response .= '<div id="healthlynked-slots-area">';
        $response .= '<input type="hidden" name="twhl_pref_background_color" id="twhl_pref_background_color" value="'.esc_html($preference['background_color']).'">';
        $response .= '<input type="hidden" name="twhl_pref_text_color" id="twhl_pref_text_color" value="'.esc_html($preference['text_color']).'">';
        $response .= '<a id="twhl_back_btn" href="#" style="color: '.esc_html($preference['text_color']).';">Back to Doctor List </a>';
        $response .= '<span style="color: '.esc_html($preference['text_color']).';"> Available dates are shown in color</span>';
        $response .= '<input type="hidden" name="doctor_id" id="twhl_doctor_id" value="">';
        $response .= '<input type="hidden" name="doctor_office_id" id="twhl_doctor_office_id" value="">';
        $response .= '<input type="hidden" name="prev_date" id="twhl_prev_date" value="">';
        $response .= '<input type="hidden" name="start_date" id="twhl_start_date" value="'.esc_html(date('Y-m-d')).'" data-begin="'.esc_html(date('Y-m-d')).'">';
        $response .= '<input type="hidden" name="twhl_sdate" id="twhl_sdate" value="'.date('Y-m').'-01">';
        $response .= '<input type="hidden" id="twhl_today" value="'.date('Y-m-d').'"/>';
        $response .= '<input type="hidden" id="twhl_pdate" value=""/>';
        $response .= '<input type="hidden" id="twhl_pddate" value="'.date('Y-m-d', strtotime('-1 month'.date('Y-m').'-01')).'"/>';                  
                                            
        $response .= '<div class="selected-person" style="background: '.esc_html($preference['background_color']).';">';
        // $response .='<img src="" alt="" width="60" id="twhl_doc_profile_image" />';
        $response .= '<h3>Book an Appointment with Dr. <span id="twhl_doctor_name"></span></h3>';
        // $response .= '<span class="custom-title">Book an Appointment</span>';
        $response .= '<p id="twhl_doctor_office"></p>';
        $response .= '</div>';
        
        // $response .= '<div id="healthlynked-slots_list-area">';
        // $response .= '</div>';
        $response .= '</div>';
        $response .= '<div id="twhl_calendar_area_nav" style="display:none;">
                <a id="twhl_back_to_calendar" href="">Back</a>
                <h4 style="" id="twhl_clndr_selected_date_cont">Available Appointments on <span data-date="" id="twhl_clndr_selected_date"></span></h4>
            </div>
            <input type="hidden" name="twhl_checked_date" id="twhl_checked_date">';
        $response .= '<div id="twhl_calendar_area" style="">
                        <div id="nextprev">
                        <button id="twhl_slots_cal_prev_btn">Prev</button>
                        <button id="twhl_slots_cal_next_btn">Next</button>
                        </div>';
        $response .= '<div id="twhl_slots_calendar" data-loading="false"></div></div>';
        $response .= '<div id="twhl_slot_data"></div>';
        
        $response .= '<div id="healthlynked-appointment-book-area">';
        $response .= '</div>';
        $response .= '</div>';
        //modal
        $response .= '<div class="modal fade" id="twhl_booking_modal" role="dialog">';
        $response .= '<div class="modal-dialog">';
        $response .= '<div class="modal-content"><form id="twhl_appointment_form" method="post", action="">';
        $response .= '<div class="modal-header" style="background: '.esc_html($preference['background_color']).' !important;">';
        $response .= '<h4 class="modal-title">REQUEST AN APPOINTMENT<span><button type="button" class="close" data-dismiss="modal">&times;</button></span></h4>';
        $response .= '</div>';
        $response .= '<div class="modal-body">';
        $response .= '<div id="twhl_errormessage"></div>';
        $response .= '<div>Please confirm that you would like to request the following appointment:</div>';
        $response .= '<div id="twhl_modal-appointmnt-date"></div>';
        $response .= '<div id="twhl_modal-appointmnt-form">
                        <h4>Your Information: *</h4>
                        <div class="row">
                        <div class="col-md-6">
                        <div class="form-group">
                            <label>First name:*</label>
                            <input value="" id="twhl_first_name" placeholder="First Name" type="text" class="form-control" required="" name="first_name">
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div class="form-group">
                            <label>Last name:*</label>
                            <input value="" id="twhl_last_name" placeholder="Last Name" type="text" class="form-control" required="" name="last_name">
                        </div>
                        </div>
                        </div>
                        <div class="form-group">
                            <label>Email:*</label>
                            <input value="" id="twhl_emailid" placeholder="Email id..." type="email" class="form-control" required="" name="emailid">
                        </div>   
                        <div class="form-group">
                            <label>Reason:*</label>
                            <textarea id="twhl_reason" placeholder="Reason of Booking..." class="form-control" required="" name="reason"></textarea>
                        </div>  
                        <div class="row">
                        <div class="form-group">
                            <div class="col-md-6">
                                <label>Enter Captcha Text <span class="text-danger">*</span></label>
                                <input type="text" required value="" id="twhl_captchatext" name="captchatext" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <div id="twhl_recaptcha" >
                                    <img src="" id="twhl_recaptcha-src" /><br>
                                    <a id="twhl_loadcaptch">Reload Captcha</a>
                                </div>
                            </div>
                        </div>  
                        </div>  
                    </div>';
        $response .= '</div>';
        $response .= '<div class="modal-footer">';
        $response .= '<button type="button" class="btn btn-default" data-dismiss="modal" style="background-color: '.esc_html($preference['background_color']).' !important;border-color:'.esc_html($preference['background_color']).' !important;" id="twhl_cancel_appointment_btn">Cancel</button>';
        $response .= '<button type="button" id="twhl_request_appointment" class="btn btn-primary" style="background-color: '.esc_html($preference['background_color']).' !important;border-color:'.esc_html($preference['background_color']).' !important;">REQUEST APPOINTMENT</button>';
        $response .= '</div>';
        $response .= '</div></form>';
        $response .= '</div>';
        $response .= '</div>';

        return $response;
    }

    /**
     * ACTION Enqueue scripts.
     */
    public function enqueue()
    {
        // jQuery will be loaded as a dependency
        //# DO NOT use other version than the one bundled with WP
        //## Things will BREAK if you do so
        //wp_enqueue_script('jquery-min-js', plugins_url('resources/lib/jquery.min.js', __FILE__));
        // wp_enqueue_script('jquery-ui-min-js', plugins_url('resources/lib/jquery-ui.min.js', __FILE__));
        wp_enqueue_style('bootstrap-3-3-7-css', plugins_url('resources/css/bootstrap.min.css', __FILE__));

        wp_enqueue_style('fullcaendar-css', plugins_url('resources/lib/fullcalendar/fullcalendar.css', __FILE__));
        wp_enqueue_style('fullcaendar-print-css', plugins_url('resources/lib/fullcalendar/fullcalendar.print.css', __FILE__));

        wp_enqueue_script(
            array('jquery')
       );
        wp_enqueue_script('bootstrap-3-3-7-js', plugins_url('resources/lib/bootstrap.min.js', __FILE__));
        wp_enqueue_script('moment-min-js', plugins_url('resources/lib/moment.min.js', __FILE__));
        wp_enqueue_script('fullcaendar-min-js', plugins_url('resources/lib/fullcalendar/fullcalendar.min.js', __FILE__));

        wp_enqueue_script(
             'ajax-random-post',
             "{$this->plugin_url}/resources/js/twhl_shortcode.js",
             array('jquery')
        );
        // Here we send PHP values to JS
        wp_localize_script(
             'ajax-random-post',
             'wp_ajax',
             array(
                 'ajaxurl' => admin_url('admin-ajax.php'),
                 'ajaxnonce' => wp_create_nonce('ajax_post_validation'),
                 'loading' => '',
            )
        );
        wp_enqueue_style('hlinked-style-css', plugins_url('resources/css/hlinked-style.css', __FILE__));
    }

    /**
     * get the slot information of the selected doctor.
     */
    public function twhl_book_slot()
    {
        session_start();
        check_ajax_referer('ajax_post_validation', 'security');
        $emailid = sanitize_email($_POST['emailid']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $doctor_id = sanitize_text_field($_POST['doctor_id']);
        $doctor_office_id = sanitize_text_field($_POST['doctor_office_id']);
        $timestamp = sanitize_text_field($_POST['timestamp']);
        $reason = sanitize_text_field($_POST['reason']);
        $captchatext = sanitize_text_field($_POST['captchatext']);
        //
        if ($doctor_id > 0 && $doctor_office_id > 0 && $timestamp > 0 && $_SESSION['captchatext'] == $captchatext) {
            $bookdata = array(
                'email' => $emailid,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'doctor_id' => $doctor_id,
                'doctor_office_id' => $doctor_office_id,
                'timestamp' => $timestamp,
                'reason' => $reason,
            );
            $app = twhl_api_requests::book_appointment($bookdata);
            if ($app['status']) {
                $respnse = array('status' => true, 'message' => 'booked');
                wp_send_json_success($respnse);
            } else {
                $respnse = array('status' => false, 'message' => 'Not booked');
                wp_send_json_error($app);
            }
        }
        wp_send_json_error(array('status' => false, 'message' => 'Something went wrong.'));
    }
    public function twhl_load_captcha()
    {
        // initialise image with dimensions of 160 x 45 pixels
        $image = @imagecreatetruecolor(160, 45) or die('Cannot Initialize new GD image stream');

        // set background and allocate drawing colours
        $background = imagecolorallocate($image, 0x66, 0xCC, 0xFF);
        imagefill($image, 0, 0, $background);
        $linecolor = imagecolorallocate($image, 0x33, 0x99, 0xCC);
        $textcolor1 = imagecolorallocate($image, 0x00, 0x00, 0x00);
        $textcolor2 = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);

        // draw random lines on canvas
        for ($i = 0; $i < 8; ++$i) {
            imagesetthickness($image, rand(1, 3));
            imageline($image, rand(0, 160), 0, rand(0, 160), 45, $linecolor);
        }

        session_start();

        // using a mixture of TTF fonts
        $fonts = [];
        $fonts[] = './dejavu/DejaVuSerif-Bold.ttf';
        $fonts[] = './dejavu/DejaVuSans-Bold.ttf';
        $fonts[] = './dejavu/DejaVuSansMono-Bold.ttf';
        // $fonts[] = "Arial.ttf";

        // add random digits to canvas using random black/white colour
        $digit = '';
        for ($x = 10; $x <= 130; $x += 30) {
            $textcolor = (rand() % 2) ? $textcolor1 : $textcolor2;
            $digit .= ($num = rand(0, 9));
            imagettftext($image, 20, rand(-30, 30), $x, rand(20, 42), $textcolor, $fonts[array_rand($fonts)], $num);
        }

        // record digits in session variable
        $_SESSION['captchatext'] = $digit;

        // display image and clean up
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
        wp_die();
    }

}
