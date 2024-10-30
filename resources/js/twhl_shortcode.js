/* 
 * @plugin SO Ajax Shortcode
 */

jQuery( document ).ready( function( $ ) 
{ 
    var data = {
        security: wp_ajax.ajaxnonce
    };
    jQuery(document).ready(function(){
        $("#healthlynked-slots-area").hide();
        $('.twhl_day_slot_container').hide();
        $('#twhl_clndr_selected_date_cont').hide();
        $('#twhl_calendar_area_nav').hide();
        $('#twhl_calendar_area').hide();
    })
    if(parseInt($("#twhl_doctor_count").val())>0){
        var doctor_id = $('.hl-doctor-list-item').attr('data-id');
        var doctor_name = $('.hl-doctor-list-item').attr('data-doc-name');
        var office_id=$('.hl-doctor-list-item').attr('data-doc-office-id');
        var doc_image=$('.hl-doctor-list-item').attr('data-doc-profile-image');
        var office=$('.hl-doctor-list-item').attr('data-doc-office');
        var doc_speciality=$('.hl-doctor-list-item').attr('data-doc-speciality');

        //twhl_get_calendar(doctor_id,doctor_name,office_id,doc_image,office,doc_speciality);
    }
    function twhl_get_calendar(doctor_id,doctor_name,office_id,doc_image,doc_office,doc_speciality){
        $('#twhl_doctor_id').val(doctor_id);
        $('#twhl_doctor_office_id').val(office_id);
        $("#twhl_doctor_name").text(doctor_name);
        // $("#twhl_doctor_office").text(doc_office);
        $("#twhl_doctor_speciality").text(doc_speciality);
        $("#twhl_doc_profile_image").attr('src',doc_image);
        $("#healthlynked-doctorlist-area").hide(1000);
        $("#healthlynked-slots-area").show(1000);
        twhl_load_calendar_data($('#twhl_sdate').val());
        $('#twhl_calendar_area').show(1000);
    }
    $(document).on('click','#twhl_request_appointment',function(arg){
        arg.preventDefault();
        data['action']='twhl_book_slot';
        data['first_name']= $("#twhl_first_name").val();
        data['last_name']= $("#twhl_last_name").val();
        data['emailid']= $("#twhl_emailid").val();
        data['phone'] =$("#twhl_phone").val();
        data['captchatext']= $("#twhl_captchatext").val();
        $(this).attr('disabled','');
        $('#twhl_cancel_appointment_btn').attr('disabled','');
        if(data['emailid']!='' && data['first_name']!='' && data['last_name']!='' && data['captchatext']!='' && data['phone']!='' ){
            $.post(
                wp_ajax.ajaxurl, 
                data,                   
                function( response )
                {
                    if(response.success==true){
                        $("#healthlynked-appointment-message-area").html('<div class="alert alert-success">Success. Appointment has booked successfully.</div>');
                        $("#healthlynked-appointment-message-area").show(1000);
                        setTimeout(function() { $("#healthlynked-appointment-message-area").hide(); }, 5000);
                        $("#twhl_appointment_form")[0].reset();
                        $('#twhl_booking_modal').modal('hide'); 
                        //slow scroll to the message area
                        $('html, body').animate({
                            scrollTop: $("#healthlynked-appointment-area").offset().top
                        }, 2000);
                    }
                    else{
                        $("#twhl_errormessage").html('<div class="alert alert-danger">Failed. Something went wrong. Please try again.</div>');
                        twhl_load_captcha();
                    }
                    $('#twhl_request_appointment').removeAttr('disabled');
                    $('#twhl_cancel_appointment_btn').removeAttr('disabled');
                }
            );
        }
        else{
            $("#twhl_errormessage").html('<div class="alert alert-danger">Failed. Something went wrong. Please check the submitted data again.</div>');
            $('#twhl_request_appointment').removeAttr('disabled');
            $('#twhl_cancel_appointment_btn').removeAttr('disabled');
            twhl_load_captcha();
        }
    });
    
    /**
     * Load the captcha Image
     */
    
    $(document).on('click','#twhl_loadcaptch',function(arg){
        arg.preventDefault();
        twhl_load_captcha();
    });
    /**
     * Load Captcha image event
     */
    function twhl_load_captcha(){
        data['action']='twhl_load_captcha';
        $.ajax({
            url: wp_ajax.ajaxurl,
            beforeSend: function (xhr) {
                xhr.overrideMimeType('text/plain; charset=x-user-defined');
            },
            type: 'POST',
            data: data,
            dataType: "text",
            success: function(result, textStatus, jqXHR){
                if(result.length < 1){
                    alert("The thumbnail doesn't exist");
                    $("#twhl_thumbnail").attr("src", "data:image/png;base64,");
                    return
                }
                var binary = "";
                var responseText = jqXHR.responseText;
                var responseTextLen = responseText.length;

                for ( i = 0; i < responseTextLen; i++ ) {
                    binary += String.fromCharCode(responseText.charCodeAt(i) & 255)
                }
                $("#twhl_recaptcha-src").attr("src", "data:image/png;base64,"+btoa(binary));
            }
        });
    }
    /**
     * Full calendar
     */
    var eventData=[];
    $("#twhl_slots_calendar").fullCalendar({
        header: {
            left: '',
            center: 'title',
            right: ''
          },
          defaultDate: $('#twhl_sdate').val(),
          navLinks: false, // can click day/week names to navigate views
          editable: false,
          eventLimit: true, // allow "more" link when too many events
          dayClick: function(date, jsEvent, view) {
            calendarDayClickEvent(date.format());
          },
          viewRender: function(view){
            $('.fc-day').filter(
                function(index){
                return moment( $(this).data('date') ).isBefore(moment(),'day') 
              }).addClass('fc-other-month');

            // Drop the second param ('day') if you want to be more specific
            if(moment().isAfter(view.intervalStart, 'day')) {
                $('.fc-prev-button').addClass('fc-state-disabled');
            } else {
                $('.fc-prev-button').removeClass('fc-state-disabled');
            }
        }
    });
    /**
     * Load the calendar dates for doctor avalability
     * @param {*} sdate 
     */
    function twhl_load_calendar_data(sdate){
        var mdata={
            'action':'twhl_get_calendar_data',
            'start_date':sdate,
            'doctor_id':$("#twhl_doctor_id").val(),
            'office_id':$("#twhl_doctor_office_id").val(),
        };
        $("#twhl_slots_calendar").attr('data-loading','true');
        $.ajax({ 
            'url':wp_ajax.ajaxurl, 
            'data': mdata,
            'type':'POST',
            'dataType':'JSON',                   
            success: function( response )
            {
                if(response.status==true){
                    response=response.data;
                    var sr=0;
                    $('#twhl_slot_data').html('');
                    var today=$('#twhl_today').val()+' 00:00:00';
                    today=new Date(today);
                    $.each(response.slots, function(index,element){
                        if(element!=''){
                            var cdate=index;
                            cdate=new Date(cdate);
                            if(cdate < today){}
                            else{
                                if($('#twhl_date_slot_data_'+index).html()!=''){
                                    var slot_data='<div class="twhl_day_slot_container" id="twhl_date_slot_data_'+index+'" style="display:none;">';
                                    var event={
                                        "start":index,
                                        "end": index,
                                        "title":"Book"
                                    };
                                    eventData[sr]=event;
                                    $("td[data-date='"+index+"']").addClass('twhl_cal_color_btn');
                                    $("td[data-date='"+index+"']").attr('style','background-color:'+$("#twhl_pref_background_color").val());
                                    sr++;
                                    $.each(element,function(cind,elem){
                                        //set the event timings
                                        var realtime=new Date(elem.start_time);
                                        var actualslot=elem.start_time;
                                        actualslot=actualslot.split(' ');

                                        slot_data += '<p>' + moment(elem.start_time).format('hh:mm A') + '<button data-time="' + elem.start_time + '" data-timestamp="' + elem.timestamp + '" class="twhl_book_now_btn" data-realtime="'+realtime.getDate()+'/'+realtime.getMonth()+'/'+realtime.getFullYear()+' '+actualslot[1]+'" style="background-color:'+$("#twhl_pref_background_color").val()+';color:'+$("#twhl_pref_text_color").val()+'">Book Now</button></p>';
                                    });
                                    slot_data+='<div>';
                                    $('#twhl_slot_data').append(slot_data);
                                }
                            }
                        }
                    });
                    //set the next month
                    
                        $("#twhl_pdate").val(response.prev_date);
                        $("#twhl_sdate").val(response.next_date);
                        $("#twhl_cdate").val(response.current_date);
                    
                    $("#twhl_slots_calendar").attr('data-loading','false');
                    
                }
            }
        });
    }
    // twhl_load_calendar_data($('#twhl_sdate').val());

    /**
     * Caneldar next month button action
     */
    $('#twhl_slots_cal_next_btn').click(function(arg) {
        arg.preventDefault();
        // var get_month= $('#twhl_slots_calendar').fullCalendar('getDate');
        if($("#twhl_slots_calendar").attr('data-loading')=='false'){
            twhl_load_calendar_data($('#twhl_sdate').val());
            $('#twhl_slots_calendar').fullCalendar('next');
        }
        else{
            alert('Wait...');
        }
    });     
    /**
     * Calendar previous month action button
     */
    $('#twhl_slots_cal_prev_btn').click(function(arg) {
        arg.preventDefault();
        // var get_month= $('#twhl_slots_calendar').fullCalendar('getDate');
        if($("#twhl_slots_calendar").attr('data-loading')=='false'){
            $('#twhl_slots_calendar').fullCalendar('prev');
            $("#twhl_slots_calendar").attr('data-loading','true');
            twhl_load_calendar_data($('#twhl_pdate').val());
        }
        else{
            alert('Wait...');
        }
    });
    /**
     * Day click event handler
     * @param {} act_date 
     */
    function calendarDayClickEvent(act_date) {
        var date=act_date+' 00:00:00';
        var hasEvent = $("#twhl_date_slot_data_" + act_date).html();
        if(hasEvent){
            var selected_date = new Date(date);
            var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            $('#twhl_clndr_selected_date').text(months[selected_date.getMonth()]+' '+selected_date.getDate()+', '+selected_date.getFullYear());
            $('#twhl_clndr_selected_date_cont').show();
            $('#twhl_calendar_area').hide(1000);
            $('#twhl_day_slot_container').hide(1000);
            $('#twhl_date_slot_data_'+act_date).show(1000);
            $('#twhl_calendar_area_nav').show(1000);
        }
        else{
            alert('Not available.');
        }
        return false;
    }
    /**
     * Back to main calendar click event
     */
    $('#twhl_back_to_calendar').click(function(arg){
        arg.preventDefault();
        $('#twhl_calendar_area').show(1000);
        $('.twhl_day_slot_container').hide(1000);
        $('#twhl_clndr_selected_date_cont').hide(1000);
        $('#twhl_calendar_area_nav').hide(1000);
    });
    /**
     * Main back button event to back to doctor list
     */
    $(document).on('click','#twhl_back_btn',function(arg){
        arg.preventDefault();
        $("#twhl_start_date").attr('value',$('#twhl_start_date').attr('data-begin'));
        $("#twhl_sdate").val($('#twhl_start_date').attr('data-begin'));
        jQuery('#twhl_slots_calendar').fullCalendar('gotoDate', $('#twhl_start_date').attr('data-begin'));
        $("#healthlynked-doctorlist-area").show(1000);
        $("#healthlynked-slots-area").hide(1000);
        $('.twhl_day_slot_container').hide(1000);
        $('#twhl_clndr_selected_date_cont').hide(1000);
        $('#twhl_calendar_area_nav').hide(1000);
        $('#twhl_calendar_area').hide(1000);
        
    });
    /**
     * Book now button click event handler in doctor list
     */
    $( '.hl-doctor-list-item' ).click( function(e) 
    {
        e.preventDefault();
        var doctor_id = $(this).attr('data-id');
        var doctor_name = $(this).attr('data-doc-name');
        var office_id = $(this).attr('data-doc-office-id');
        var doc_image=$(this).attr('data-doc-profile-image');
        var office=$('.hl-doctor-list-item').attr('data-doc-office');
        var doc_speciality=$('.hl-doctor-list-item').attr('data-doc-speciality');
        twhl_get_calendar(doctor_id,doctor_name,office_id,doc_image,office,doc_speciality);

    }); // end click
    /**
     * Book now button click event handler in slot list
     */
    $(document).on('click','.twhl_book_now_btn',function(arg){
        arg.preventDefault();
        data['action']='twhl_book_slot';
        data['doctor_id']= $("#twhl_doctor_id").val();
        data['doctor_office_id']= $("#twhl_doctor_office_id").val();
        data['start_time']= $(this).attr('data-start-time');
        data['timestamp']= $(this).attr('data-timestamp');
        twhl_load_captcha();
        $('#twhl_errormessage').html('');
        $('#twhl_booking_modal').modal('show'); 
    });
});