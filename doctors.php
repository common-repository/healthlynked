<?php

function twhl_doctors_page_handler()
{
    $message = '';
    $notice = '';
    //check logged in or not
    $check = twhl_settings_List_Table::checklogin();
    //get the doctor list

    $message = ''; ?>
    <div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Doctors', 'twhl'); ?></h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?= $notice; ?></p></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?= $message; ?></p></div>
    <?php endif; ?>
    <?php
    if (!empty($check)) {
        //get the doctor list
        $doctors = twhl_api_requests::clinic_doctors(array('token' => $check['token']));

        if ($doctors['status'] == false) {
            ?><div id="notice" class="error"><p>Something went wrong. Please try again later.</p></div><?php
            die();
        }
        $doctors = $doctors['doctors'];
    } else {
        ?><div id="notice" class="error"><p>Plugin settings has not done.</p></div><?php
        die();
    } ?>
    <table class="wp-list-table widefat fixed striped posts">
        <thead>
        <tr>
            
            <th>SR</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Title</th>
            <th>Specialization</th>
            <th>Email</th>
            <th>Gender</th>
            <th>Appointments</th>
        </tr>
        </thead>

        <tbody id="the-list">
                <?php
                $sr = 0;
    foreach ($doctors as $doctor) {
        ++$sr; ?>
                        <tr>
                            <th>
                                <?= $sr; ?>
                            </th>
                            <td>
                                <img height="70" src="<?= esc_url($doctor['image']); ?>" alt="">
                            </td>
                            <td>
                                <strong><?= esc_html($doctor['name']); ?></strong>
                            </td>
                            <td>
                            <?= esc_html($doctor['title']); ?>
                            </td>
                            <td>
                            <?= esc_html($doctor['specialization']); ?>
                            </td>
                            <td>
                            <?= esc_html($doctor['email']); ?>
                            </td>
                            <td>
                            <?= esc_html($doctor['sex']); ?>
                            </td>
                            <td>
                            <?= '<a href="?page=doctor_details&id='.$doctor['id'].'">Details</a>'; ?>    
                            </td>
                        </tr>
                    <?php
    } ?>
                    
        </tbody>
    </table>       
    </div>
    <?php
}

function twhl_doctor_details_page_handler()
{
    // wp_enqueue_style('fullcalendar-min-css', plugins_url('resources/lib/fullcalendar/fullcalendar.min.css', __FILE__));
    $message = '';
    $notice = '';
    //check logged in or not
    $check = twhl_settings_List_Table::checklogin();
    $office_details = array();
    $time_slots = array();
    $doctor_info = array();

    if (!empty($check) && isset($_REQUEST['id'])) {
        $doctor_id = sanitize_text_field($_REQUEST['id']);
        if ($doctor_id != '' && $doctor_id > 0) {
            add_action('admin_footer', 'twhl_get_updated_calendar_data');
            $doctor_details = twhl_api_requests::get_doctor_details(array('doctor_id' => $doctor_id, 'token' => $check['token']));
            if ($doctor_details['status'] == true) {
                $doctor_info = $doctor_details['profile']['info'];
                $office_details = $doctor_details['profile']['offices'][0];
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
                    } ?>
                    <div class="wrap">
                        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
                        <h2><?php _e('Booked Appointments', 'twhl'); ?></h2>
                        <div class="doc_container">
                            <img style="width: 100px !important;margin-right: 20px;" width="80" src="<?= esc_url($doctor_info['image']); ?>" alt="">
                            
                            <h3><?= esc_html($doctor_info['name']); ?></h3>
                            <div><strong>Office:</strong></div>
                            <p><?= esc_html($office_details['name']); ?><br>
                            <?= esc_html($office_details['phone']); ?>
                            </p>    
                            </p>
                        </div>
                        
                        <?php if (!empty($notice)): ?>
                        <div id="twhl_notice" class="error"><p><?= esc_html($notice); ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($message)): ?>
                        <div id="twhl_message" class="updated"><p><?= esc_html($message); ?></p></div>
                        <?php endif; ?>
                        <input type="hidden" name="doctor_id" id="twhl_doctor_id" value="<?= esc_html($doctor_id); ?>">
                        <div id="twhl_calendar"></div>
                        <script type="text/javascript">
                            var today = new Date();
                            jQuery('#twhl_calendar').fullCalendar({
                                header: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: 'month,agendaWeek,agendaDay,listWeek'
                                },
                                
                                defaultView: 'agendaWeek',
                                defaultDate: today,
                                minTime: "09:00:00",
                                slotDuration: "00:15:00",
                                allDaySlot: true,
                                allDay: true,
                                navLinks: true, // can click day/week names to navigate views
                                editable: true,
                                eventLimitText: "Something",
                                eventLimit: true, // allow "more" link when too many events
                                events: {}
                            });
                        </script>
                    </div>
                    <?php
                }
            } else {
                ?><div id="notice" class="error"><p>Invalid doctor ID supplied</p></div><?php
                die();
            }
        }
    } else {
        ?><div id="notice" class="error"><p>Plugin settings has not done.</p></div><?php
        die();
    }
}

function twhl_load_appointments()
{
    $response = array('status' => false, 'message' => 'Something went wrong', 'mdata' => array());
    $check = twhl_settings_List_Table::checklogin();
    $doctor_id = sanitize_text_field($_POST['doctor_id']);
    if ($doctor_id > 0) {
        $check = twhl_settings_List_Table::checklogin();
        if (!empty($check)) {
            $appointments = twhl_api_requests::doctor_appointments(array('token' => $check['token'], 'doctor_id' => $doctor_id));
            if (!empty($appointments)) {
                $response = array('status' => true, 'message' => '', 'mdata' => $appointments);
            }
        }
    }
    echo json_encode($response);
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_twhl_load_appointments', 'twhl_load_appointments');
add_action('wp_ajax_nopriv_twhl_load_appointments', 'twhl_load_appointments');
function twhl_get_updated_calendar_data()
{
    ?>
    <script type="text/javascript" >
    jQuery(document).ready(function($) {
        var data = {
            'action' : 'twhl_load_appointments',
            'doctor_id' : $("#twhl_doctor_id").val()
        };
        $.ajax({
            url: ajaxurl,
            data: data,
            type: 'POST',
            dataType: 'JSON',
            success: function(response){
                if(response.status==true){
                    if(response.mdata.status){
                        var events=response.mdata.events;
                        $('#twhl_calendar').fullCalendar('removeEvents');
                        $('#twhl_calendar').fullCalendar('addEventSource',events);
                        $('#twhl_calendar').fullCalendar('rerenderEvents');

                    }
                    
                }
            }
        });
    });
    </script> <?php
}
add_action('wp_ajax_twhl_get_slot_details', 'twhl_get_slot_details');
add_action('wp_ajax_nopriv_twhl_get_slot_details', 'twhl_get_slot_details');

function twhl_get_slot_details()
{
    $response = array('status' => false, 'message' => 'Something went wrong1', 'data' => array());
    $start = sanitize_text_field($_POST['start_date']);
    $doctor_id = sanitize_text_field($_POST['doctor_id']);
    if ($doctor_id != '' && $start != '' && $doctor_id > 0) {
        $slots = twhl_api_requests::get_slotdetails($start, $doctor_id);
        if (!empty($slots)) {
            if ($slots['status'] == false) {
                $response['message'] = $slots['message'];
            } else {
                $response['data'] = $slots;
                $response['status'] = true;
                $response['message'] = '';
            }
        }
    }

    echo json_encode($response);
    wp_die(); // this is required to terminate immediately and return a proper response
}

function twhl_get_calendar_data()
{
    $start = sanitize_text_field($_POST['start_date']);
    $doctor_id = sanitize_text_field($_POST['doctor_id']);
    $office_id = sanitize_text_field($_POST['office_id']);
    $response = array('status' => false, 'message' => 'Something went wrong1', 'data' => array());
    if ($doctor_id != '' && $start != '' && $doctor_id > 0 && $office_id>0) {
        $slots = twhl_api_requests::get_monthly_availability($start, $doctor_id, $office_id);
        $response = array('status' => false, 'message' => 'Something went wrong2', 'data' => $slots);
        if (!empty($slots)) {
            if ($slots['status'] == false) {
                $response['message'] = $slots['message'];
            } else {
                $response['data'] = $slots;
                $response['status'] = true;
                $response['message'] = '';
            }
        }
    }
    echo json_encode($response);
    wp_die(); // this is required to terminate immediately and return a proper response
}

