/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2022-12-05
 * */
jQuery(document).ready(function ($) {
    // console.log('copy.js');
    $('html, body').scrollTop(0);
    jQuery('#transfer-form').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    });
    /* Open modal  */
    jQuery('*').on('click', function(event) {
        target = jQuery(event.target);
        buttonclicked = target.closest("button").attr('id');
        // console.log(buttonclicked);
        if (buttonclicked === 'valid_open_transfer') {
            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            // console.log(buttonclicked);
            // console.log('L 18');
            var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
            var s3cloud_server_folder = jQuery("#s3cloud_selected").text();
            var s3cloud_cloud_folder = jQuery("#s3cloud_selected_cloud").text();
            var s3cloud_bucket_name = jQuery("#select_bucket").val();
            var s3cloud_test = s3cloud_server_folder.substring(0, 7);
            // console.log(s3cloud_test);
            if (s3cloud_test === 'Please,') {
               jQuery("#s3cloud_selected").css('color', 'red');
               alert('Please, choose one folder on server!');
                return false;
            }
            var s3cloud_test = s3cloud_cloud_folder.substring(0, 7);
            if (s3cloud_test === 'Please,') {
                jQuery("#s3cloud_selected_cloud").css('color', 'red');
                alert('Please, choose one folder on cloud (after choose a Bucket).');
                return false;
            }
            //console.log(buttonclicked);
					document.getElementById("s3cloud_tansferring").innerHTML = '';
					document.getElementById("s3cloud_log").innerHTML = '';
					jQuery(".s3cloud_log").hide();
					jQuery("#s3cloud_log").hide();
                    jQuery("#tranfer_form").show();
                    jQuery("#open_transfer").click();
            var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
            if (s3cloud_radioValue == "cloud") {
                document.getElementById("s3cloud_server_cloud").innerHTML = "Transfer from Cloud to Server";
            } else {
                document.getElementById("s3cloud_server_cloud").innerHTML = "Transfer from Server to Cloud";
            }
            var s3cloud_server_folder_modal = jQuery("#s3cloud_selected").text();
            jQuery("#s3cloud_server_folder_modal").text(s3cloud_server_folder_modal);
            var s3cloud_cloud_folder_modal = jQuery("#s3cloud_selected_cloud").text();
            jQuery("#s3cloud_cloud_folder_modal").text(s3cloud_cloud_folder_modal);
        } // end if clicked open transfer
        if (buttonclicked === 'close_transfer') {


            if (event.isDefaultPrevented()) return;
            event.preventDefault();

            jQuery("#s3cloud-transfer-spinner").hide();

            jQuery("#close_transfer").css('opacity', '0.4');
            $("#s3cloud_tansferring_status").hide();

            // alert('Close Button Clicked. Please, wait few seconds to clear temp files.');
            var s3cloud_alert = jQuery(".alert-container");
            jQuery('#basicToast').css("margin-top", "20px");
            jQuery('.toast-header').css("background", "#1E90FF");
            jQuery('.toast-header').text("INFO");
            jQuery('.toast-body').text("Job Cancelled by User... Please, wait. Cleaning and Reloading page...");
            jQuery('.toast-header').css("color", "white");
            jQuery('#basicToast').slideDown();
            window.setTimeout(function() {
                jQuery('#basicToast').slideUp();
                   jQuery('#wait').show();
                   location.reload(); 
                }, 10000);
            

            
            clearInterval(window.s3cloud_Interval);
            var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
            var s3cloud_server_folder = jQuery("#s3cloud_selected").text();
            var s3cloud_cloud_folder = jQuery("#s3cloud_selected_cloud").text();
            var s3cloud_bucket_name = jQuery("#select_bucket").val();
            var radValue = $(".speed:checked").val();
            var nonce2 = $("#s3cloud_truncate").text();

            console.log('Job Cancelled by user!');

            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 's3cloud_ajax_truncate',
                    'server_cloud':s3cloud_radioValue,
					'folder_server':s3cloud_server_folder,
					'folder_cloud':s3cloud_cloud_folder,
					'bucket_name':s3cloud_bucket_name,
                    'nonce':nonce2
                },
                success: function (data) {
                    parent.location.reload(1);
                },
                error: function (xhr, status, error) {
                    console.log('Ajax Error (s3cloud_ajax_truncate): '+error);
                    console.log('Status: '+status);
                    console.log('Error Status Code: '+xhr.status);
                },
                timeout: 15000
            });
            jQuery("#tranfer-form").hide();
        }




        if (buttonclicked === 'start_transfer') {

            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            // console.log(buttonclicked);
            jQuery("#s3cloud-transfer-spinner").show();

            $("#s3cloud_tansferring_status").text('Cleaning temp files...');

      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            'action': 's3cloud_ajax_truncate_inic'
        },
        success: function (data ) {
              console.log('Cleaned temp files...');
              $("#s3cloud_tansferring_status").text('Beginning...');
        },
        error: function (xhr, status, error) {
            console.log('Ajax Error (s3cloud_ajax_truncate_inic): '+error);
            console.log('Status: '+status);
            console.log('Error Status Code: '+xhr.status);
        },
        timeout: 5000
        });



            /* Begin to copy */
            var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
            var s3cloud_server_folder = jQuery("#s3cloud_selected").text();
            var s3cloud_cloud_folder = jQuery("#s3cloud_selected_cloud").text();
            var s3cloud_bucket_name = jQuery("#select_bucket").val();
            var s3cloud_test = s3cloud_server_folder.substring(0, 7);
            var s3cloud_test = s3cloud_cloud_folder.substring(0, 7);
            jQuery("#start_transfer").prop('disabled', true);
			document.getElementById("s3cloud_tansferring").innerHTML = '';
			document.getElementById("s3cloud_log").innerHTML = '';
			jQuery(".s3cloud_log").hide();
			jQuery("#s3cloud_log").hide();
			jQuery("#s3cloud_status_label").show();
            var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
            var s3cloud_server_folder = jQuery("#s3cloud_selected").text();
            var s3cloud_cloud_folder = jQuery("#s3cloud_selected_cloud").text();
            var s3cloud_bucket_name = jQuery("#select_bucket").val();
            var radValue = $(".speed:checked").val();
            // console.log('R.V '+radValue);
           window.$frequency = 40000;
           if (radValue == 'very_slow') {
            window.$frequency = 90000;
           }
           if (radValue == 'slow') {
            window.$frequency = 60000;
           }
           if (radValue == 'normal') {
            window.$frequency = 40000;
           }
           if (radValue == 'fast') {
            window.$frequency = 20000;
           }
           if (radValue == 'very_fast') {
               window.$frequency = 5000;
           }
            s3cloud_copy_run();
            window.s3cloud_Interval =  setInterval(s3cloud_copy_run, $frequency);
        } // end Transfer
    });
    /* end open modal  */
    $(".spinner").addClass("is-active");
    function s3cloud_copy_run() {
        // console.log('12345');
      var s3cloud_radioValue = jQuery("input[name='s3cloud_server_cloud']:checked").val();
      var s3cloud_server_folder = jQuery("#s3cloud_selected").text();
      var s3cloud_cloud_folder = jQuery("#s3cloud_selected_cloud").text();
      var s3cloud_bucket_name = jQuery("#select_bucket").val();
      var radValue = $(".speed:checked").val();
      var nonce = $("#s3cloud_nonce").text();



            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 's3cloud_ajax_transf_files_to_cloud',
                    'speed': radValue,
                    'server_cloud':s3cloud_radioValue,
					'folder_server':s3cloud_server_folder,
					'folder_cloud':s3cloud_cloud_folder,
					'bucket_name':s3cloud_bucket_name,
                    'nonce':nonce
                },
                success: function (data) {
                    // console.log(data);
                    // console.log($('#transfer-form').is(':visible'));
                    if ($('#transfer-form').is(':visible')) {
                       if (data == 'End of Job!') {
                            clearInterval(window.s3cloud_Interval);
                            jQuery("#s3cloud-transfer-spinner").hide();
                            $("#s3cloud_tansferring_status").text(data);
                            alert('End of Job!');
                            parent.location.reload(1);
                        } 
                        else{
                                data = data.replace( /(<([^>]+)>)/ig, '');
                                $("#s3cloud_tansferring_status").text(data);
                                function sleep(milliseconds) {
                                    const date = Date.now();
                                    let currentDate = null;
                                    do {
                                        currentDate = Date.now();
                                    } while (currentDate - date < milliseconds);
                                }
                                sleep(3000);
                                clearInterval(window.s3cloud_Interval);
                                setInterval(window.s3cloud_Interval, window.$frequency);
                                s3cloud_copy_run();
                                // console.log(data);
                        }
                    }
                },
                error: function (xhr, status, error) {


                    //clearInterval(window.s3cloud_Interval);
                    function sleep(milliseconds) {
                        const date = Date.now();
                        let currentDate = null;
                        do {
                            currentDate = Date.now();
                        } while (currentDate - date < milliseconds);
                    }
                    
                    jQuery('*').on('click', function(event) {
                        target = jQuery(event.target);
                        buttonclicked = target.closest("button").attr('id');
                        // console.log(buttonclicked);
                        //alert(buttonclicked);

                        if(buttonclicked == 'close_transfer')
                        {
                            clearInterval(window.s3cloud_Interval);
                            sleep(5);
                            return;
                        }
                        else{

                            console.log('Ajax Error (s3cloud_ajax_transf_files_to_cloud): '+error);
                            console.log('Status: '+status);
                            console.log('Error Status Code: '+xhr.status);

                            sleep(5);
                            clearInterval(window.s3cloud_Interval);
                            setInterval(window.s3cloud_Interval, window.$frequency);
                            s3cloud_copy_run();

                        }



                    });

                    console.log('Ajax Error (s3cloud_ajax_transf_files_to_cloud): '+error);
                    console.log('Status: '+status);
                    console.log('Error Status Code: '+xhr.status);

                    sleep(5);
                    clearInterval(window.s3cloud_Interval);
                    setInterval(window.s3cloud_Interval, window.$frequency);
                    s3cloud_copy_run();
 

                    
                },
                timeout: 180000
            });
      //  }
    }
});