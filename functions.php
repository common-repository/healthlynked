<?php
/**
 * API related function calls.
 */
class twhl_api_requests
{
    public function api_request($data, $token = '')
    {
        try {
            $url = 'https://api.healthlynked.com/api/'.$data['url'];
            // $url = 'http://54.175.212.60/staging/server/api/'.$data['url'];
            $headers = array();
            if ($token != '') {
                $headers['Authorization'] = 'Bearer '.$token;
                $headers['Content-Type'] = 'application/json;charset=utf-8';
            }
            if ($data['request'] == 'get') {
                $args = array('timeout' => 120, 'httpversion' => '1.1', 'headers' => $headers);
                $postvars = '';
                if (!empty($data['mdata'])) {
                    foreach ($data['mdata'] as $key => $value) {
                        $postvars .= $key.'='.$value.'&';
                    }
                }
                $response = wp_remote_get($url.'?'.$postvars, $args);
                error_log($response['body']);
                return json_decode($response['body'], true);
            } else {
                $args = array('timeout' => 120, 'httpversion' => '1.1', 'headers' => $headers, 'method' => 'POST', 'redirection' => 5, 'blocking' => true, 'body' => $data['mdata']);
                $response = wp_remote_post($url, $args);

                return json_decode($response['body'], true);
            }
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
    }

    public static function getslotsnew($data)
    {
        try {
            $headers = array();
            $headers['Content-Type'] = 'application/json;charset=utf-8';
            $args = array('timeout' => 120, 'httpversion' => '1.1', 'headers' => $headers);
            $url = 'https://api.healthlynked.com/api/'.$data['doctor_id'].'/'.$data['office_id'].'?date='.$data['from_date'];
            $response = wp_remote_get($url, $args);

            return json_decode($response['body'], true);
        } catch (Exception $ex) {
        }
    }

    /**
     * Login to the app.
     */
    public static function checklogin($mdata)
    {
        try {
            $req_data = array(
                'url' => 'users/me',
                'mdata' => array(),
                'request' => 'get',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data, $mdata['token']);

            return $response;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Get login.
     */
    public static function getlogin($mdata)
    {
        try {
            $req_data = array(
                'mdata' => $mdata,
                'url' => 'sessions/login',
                'request' => '',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data);

            return $response;
        } catch (Exception $ex) {
            return false;
        }
    }
    public static function verifyLogin($mdata)
    {
        try {
            $req_data = array(
                'mdata' => $mdata,
                'url' => 'sessions/regenerate_token',
                'request' => '',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data);

            return $response;
        } catch (Exception $ex) {
            return false;
        }
    }

    /*
     * User details from token
     */
    public static function getuser($mdata)
    {
        try {
            $req_data = array(
                'mdata' => $mdata,
                'url' => 'users/me',
                'request' => 'get',
            );
        } catch (Exception $ex) {
        }
    }

    /**
     * Get the doctor list.
     */
    public static function clinic_doctors($mdata)
    {
        try {
            $req_data = array(
                'url' => 'clinic_doctors/',
                'mdata' => array(),
                'request' => 'get',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data, $mdata['token']);

            return $response;
        } catch (Exception $ex) {
        }
    }

    /**
     * Get the doctor Details by ID.
     */
    public static function get_doctor_details($mdata)
    {
        try {
            // requests/doctor/id
            $req_data = array(
                'url' => 'requests/doctor/'.$mdata['doctor_id'],
                'mdata' => array(),
                'request' => 'get',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data, $mdata['token']);

            return $response;
        } catch (Exception $ex) {
        }
    }

    /**
     * Get the doctor office time slots.
     */
    public static function get_timeslots($mdata)
    {
        try {
            $req_data = array(
                'url' => 'requests/timeslots/'.$data['doctor_id'].'/'.$data['office_id'].'?date='.$mdata['from_date'],
                'mdata' => array(),
                'request' => 'get',
            );

            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data);

            return $response;
        } catch (Exception $ex) {
        }
    }

    /**
     * Get the appointment lists by doctor.
     */
    public static function doctor_appointments($mdata)
    {
        try {
            // clinic_appointments?undefined&doctor_id=902248
            $req_data = array(
                'url' => 'clinic_appointments',
                'mdata' => array('doctor_id' => $mdata['doctor_id']),
                'request' => 'get',
            );
            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data, $mdata['token']);

            return $response;
        } catch (Exception $ex) {
        }
    }

    //requests/doctor/sherrie.g.neustein
    public static function get_slotdetails($start, $doctor_id)
    {
        $check = twhl_settings_List_Table::checklogin();
        $office_details = array();
        $time_slots = array();
        if (!empty($check)) {
            //get doctor details
            $doctor_details = twhl_api_requests::get_doctor_details(array('doctor_id' => $doctor_id, 'token' => $check['token']));
            //get the
            $profile = $doctor_details['profile'];
            // var_dump($profile);
            if ($doctor_details['status'] == true) {
                //profile exist
                $office = $profile['offices'];
                // var_dump($office);
                if (!empty($office)) {
                    //get the time slots for the office
                    $office_details = $office[0];
                    $data_array = array('doctor_id' => $doctor_id, 'office_id' => $office_details['id'], 'token' => $check['token'], 'from_date' => $start);
                    $time_slots = twhl_api_requests::getslotsnew($data_array);
                    // var_dump($data_array);
                    $prev_date = '';
                    if (strtotime($start) > strtotime(date('Y-m-d'))) {
                        $prev_date = date('Y-m-d', strtotime('-7days'.$start));
                    }

                    return array('status' => true, 'message' => '', 'mdata' => array('office_details' => $office_details, 'time_slots' => $time_slots, 'doctor_id' => $doctor_id, 'doctor_profile' => $profile, 'from_date' => $start, 'prev_date' => $prev_date));
                // return twhl_api_requests::getslotsnew($start);
                } else {
                    return array('status' => false, 'message' => 'Office Information Not available.', 'mdata' => array());
                }
            } else {
                return array('status' => false, 'message' => 'Doctor information not available.', 'mdata' => array());
            }
        } else {
            return array('status' => false, 'message' => 'Not Logged in', 'mdata' => array());
        }
    }

    public static function book_appointment($mdata)
    {
        try {
            $req_data = array(
                'url' => 'requests/book_user_appointment',
                'mdata' => $mdata,
                'request' => '',
            );

            $apireq = new twhl_api_requests();
            $response = $apireq->api_request($req_data);

            return $response;
        } catch (Exception $ex) {
        }
    }
    /**
     * Full calendar implementations
     */
    //Get the availability for month
    
    public static function get_monthly_availability($start_date,$doctor_id, $office_id){
        
        try {
            $url = 'requests/timeslots_new/'.$doctor_id.'/'.$office_id.'?date='.$start_date;
            $req_data = array(
                'url' => $url,
                'mdata' => array(),
                'request' => 'get',
            );
            $headers = array();
            $headers['Content-Type'] = 'application/json;charset=utf-8';
            $args = array('timeout' => 120, 'httpversion' => '1.1', 'headers' => $headers);
            $url = 'https://api.healthlynked.com/api/requests/timeslots_new/'.$doctor_id.'/'.$office_id.'?date='.$start_date;
            $response = wp_remote_get($url, $args);

            return json_decode($response['body'], true);
        } catch (Exception $ex) {
        }
    }

    public static function book_appointment_news($mdata){
        
        try {
            $headers = array();
            $headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $args = array('timeout' => 120, 'httpversion' => '1.1', 'headers' => $headers,'method' => 'POST', 'redirection' => 5, 'blocking' => true, 'body' => $mdata);
            $url = 'https://api.healthlynked.com/api/requests/book_user_appointment';
            $response = wp_remote_get($url, $args);

            return json_decode($response['body'], true);
        } catch (Exception $ex) {
        }
    }
}
