<?php //
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}

/*
[13-Jul-2024 07:48:11 UTC] WordPress database error 
You have an error in your SQL syntax; 
check the manual that corresponds to your MariaDB server version for the 
right syntax to use near 
''wp_s3cloud_copy'' at line 1 for query 
TRUNCATE TABLE 'wp_s3cloud_copy' made by 
do_action('wp_ajax_s3cloud_ajax_truncate_inic'), 
WP_Hook->do_action, WP_Hook->apply_filters, s3cloud_ajax_truncate_inic
*/

s3cloud_create_db_copy_files();
s3cloud_create_db_copy();





use Aws\Exception\AwsException;
if (is_admin()) {
	add_action('wp_head', 's3cloud_ajaxurl');
	function s3cloud_ajaxurl()
	{
		echo '<script type="text/javascript">
           var ajaxurl = "' . esc_attr(admin_url('admin-ajax.php')) . '";
         </script>';
	}
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_upload_handle');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_delete_handle');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_create_handle');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_create_filesys');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_create_filesys_cloud');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_files_to_cloud');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_transf_progress');
    add_action('wp_ajax_s3cloud', 's3cloud_ajax_transf_progress_log');
  //  add_action('wp_ajax_s3cloud', 's3cloud_ajax_truncate_table');



    add_action( 'wp_ajax_s3cloud_ajax_upload_handle', 's3cloud_ajax_upload_handle' );
    add_action( 'wp_ajax_s3cloud_ajax_delete_handle', 's3cloud_ajax_delete_handle' );
    add_action( 'wp_ajax_s3cloud_ajax_create_handle', 's3cloud_ajax_create_handle' );
    add_action( 'wp_ajax_s3cloud_ajax_create_filesys', 's3cloud_ajax_create_filesys' );
    add_action( 'wp_ajax_s3cloud_ajax_create_filesys_cloud', 's3cloud_ajax_create_filesys_cloud' );
    add_action( 'wp_ajax_s3cloud_ajax_transf_files_to_cloud', 's3cloud_ajax_transf_files_to_cloud' );

    add_action( 'wp_ajax_s3cloud_ajax_transf_progress', 's3cloud_ajax_transf_progress' );
    add_action( 'wp_ajax_s3cloud_ajax_transf_progress_log', 's3cloud_ajax_transf_progress_log' );
    
    add_action( 'wp_ajax_s3cloud_ajax_truncate', 's3cloud_ajax_truncate' );
    add_action( 'wp_ajax_s3cloud_ajax_truncate_inic', 's3cloud_ajax_truncate_inic' );
    
}
function s3cloud_init()
{
    add_management_page(
        'S3 cloud',
        'S3 cloud',
        'manage_options',
        's3cloud_admin_page', // slug
        's3cloud_admin_page'
    );
}
function s3cloud_admin_page()
{
            require_once S3CLOUDPATH . "/dashboard/dashboard_container.php";
}
function s3cloud_updated_message()
{
    echo '<div class="notice notice-success is-dismissible">';
    echo '<br /><b>';
    echo esc_attr(__('Database Updated!', 's3cloud'));
    echo '<br /><br /></div>';
}

//
function s3cloud_ajax_upload_handle() {

    if (isset($_FILES["file"])) {

        $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
        require_once esc_url($path);



        if (current_user_can('administrator')) {
            // The current user is an administrator
            //echo 'You are an administrator.';
        } else {
            // The current user is not an administrator
            die('You are not an administrator. (1)');
        }

        if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
            // debug2();
           // wp_die('The nonce verification failed. Please try again.');
         }

         // debug2();


        if (isset($_GET['bucket'])) {
            $s3cloud_bucket_name =  sanitize_text_field($_GET['bucket']);
        }

        // debug2($s3cloud_bucket_name);


        try{
            // TEST
            $buckets = $s3cloud_s3->listBuckets();
            // // debug2($buckets);
        } catch (AWSException $e) {
            echo '<div class="s3cloud_alert">';
            echo "<b>" . esc_attr($e->getStatusCode()) . "\n" .  esc_attr($e->getAwsErrorCode()) . "</b>";
            echo esc_attr(explode(';', $e->getMessage())[1]);
            echo "</div>";
            // debug2();

            return;
        }


        $url = "$endpoints/{$s3cloud_bucket_name}";



       // foreach ($_POST as $key => $value) {
            // debug2($key . ': ' . $value);
       // }



        if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
            // debug2();
           // wp_die('The nonce verification failed. Please try again.');
        }
        if (isset($_POST['prefix'])) {
            $prefix = sanitize_text_field($_POST['prefix']);
        } else {
            $prefix = '';
        }

        // debug2();

        if (current_user_can('administrator')) {
            // The current user is an administrator
            //echo 'You are an administrator.';
        } else {
            // The current user is not an administrator
            // debug2();
            die('You are not an administrator. (2)');
        }
        

        // debug2();



                try {
                    $file_name = sanitize_text_field($_GET['prefix'].$_FILES['file']['name']);
                    $file_name = str_replace(' ', '_', $file_name);
                    $file_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $file_name);
                    $size = sanitize_text_field($_FILES['file']['size']);
                    $tmp = sanitize_text_field($_FILES['file']['tmp_name']);
                    $type = sanitize_text_field($_FILES['file']['type']);
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $request_status = $s3cloud_s3->putObject([
                        'Bucket' => $s3cloud_bucket_name,
                        'ContentType' => $type,
                        'Key' => $file_name,
                        'Body' => fopen($tmp, 'rb'), //rb to open binary file (same as c)
                    ]); 
                    // debug2();

                    die('upload ok ');
                } catch (AWSException $e) {
                    error_log($e->getMessage());
                    die('Fail to Open file (-2090)');
                }
    }
    die('Nothing to do ');
}



function s3cloud_ajax_upload_handle3() {


    if (isset($_FILES["file"])) {


        $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
        require_once esc_url($path);
        
        $url = "$baseurl/{$s3cloud_bucket_name}";






        if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
            // debug2();
            wp_die('The nonce verification failed. Please try again.');
         }
         if (isset($_POST['prefix'])) {
             $prefix = sanitize_text_field($_POST['prefix']);
         } else {
             $prefix = '';
         }
 
         if (current_user_can('administrator')) {
             // The current user is an administrator
             //echo 'You are an administrator.';
         } else {
             // The current user is not an administrator
             die('You are not an administrator. (3)');
         }





        try {
            $file_name = sanitize_text_field($_GET['prefix'].$_FILES['file']['name']);
            $file_name = str_replace(' ', '_', $file_name);
            $file_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $file_name);
            $size = sanitize_text_field($_FILES['file']['size']);
            $tmp = sanitize_text_field($_FILES['file']['tmp_name']);
            $type = sanitize_text_field($_FILES['file']['type']);
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $request_status = $s3cloud_s3->putObject([
                'Bucket' => $s3cloud_bucket_name,
                'ContentType' => $type,
                'Key' => $file_name,
                'Body' => fopen($tmp, 'rb'), //rb to open binary file (same as c)
            ]); 
            die('upload ok ');
        } catch (AWSException $e) {
            error_log($e->getMessage());
            die('Fail to open file (2991)');
        }
    }
    die('Nothing to do ');
}

function s3cloud_ajax_upload_handle2() {
    if (isset($_FILES["file"])) {




//$credjson = base64_decode($s3cloud_access_key);
//$credarray = json_decode($credjson);




if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
    // debug2();
    wp_die('The nonce verification failed. Please try again.');
 }
 
 if (isset($_GET['bucket'])) {
    $s3cloud_bucket_name =  sanitize_text_field($_GET['bucket']);
}

 if (current_user_can('administrator')) {
     // The current user is an administrator
     //echo 'You are an administrator.';
 } else {
     // The current user is not an administrator
     die('You are not an administrator. (4)');
 }







$path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
require_once esc_url($path);


try{

    // TEST
    $buckets = $s3cloud_s3->listBuckets();
} catch (AWSException $e) {
      echo '<div class="s3cloud_alert">';
      echo "<b>" . esc_attr($e->getStatusCode()) . "\n" .  esc_attr($e->getAwsErrorCode()) . "</b>";
      echo esc_attr(explode(';', $e->getMessage())[1]);
      echo "</div>";
      return;
  }
if (isset($_POST['prefix'])) {
    $prefix = sanitize_text_field($_POST['prefix']);
} else {
    $prefix = '';
}
        try {
            $file_name = sanitize_text_field($_GET['prefix'].$_FILES['file']['name']);
            $file_name = str_replace(' ', '_', $file_name);
            $file_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $file_name);
            $size = sanitize_text_field($_FILES['file']['size']);
            $tmp = sanitize_text_field($_FILES['file']['tmp_name']);
            $type = sanitize_text_field($_FILES['file']['type']);
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $request_status = $s3cloud_s3->putObject([
                'Bucket' => $s3cloud_config['s3-access']['bucket'],
                'ContentType' => $type,
                'Key' => $file_name,
                'Body' => fopen($tmp, 'rb'), //rb to open binary file (same as c)
            ]); 
            die('upload ok ');
        } catch (AWSException $e) {
            error_log($e->getMessage());
            die('Fail to Open File -3000');
        }
    }
    die('Nothing to do ');
}

function s3cloud_ajax_delete_handle() {



// error_log($_POST['s3cloud_nonce']);



    if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
        // debug2();
       // wp_die('The nonce verification failed. Please try again.');
     }
     
     if (current_user_can('administrator')) {
         // The current user is an administrator
         //echo 'You are an administrator.';
     } else {
         // The current user is not an administrator
         die('You are not an administrator.(5)');
     }


    
    if(!isset($_POST['delete-list']))
      wp_die('empty_list');
      try {
            if(count($_POST['delete-list']) < 1)
                 wp_die('empty_list');
             }
      catch(Exception $e) {
                 wp_die(esc_attr($e->getMessage()));
             } 

    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once esc_url($path);

    if(empty($s3cloud_region) or empty($s3cloud_secret_key) or empty($s3cloud_access_key)) {
        wp_die('fields_blank');
    }

    // $path = S3CLOUDPATH."/vendor/autoload.php";
    // require_once($path);


    try {
            $buckets = $s3cloud_s3->listBuckets();
    } catch (AWSException $e) {
         wp_die('fail_s3');
    }
    $todo = array();
    for($i=0;$i < count($_POST['delete-list']); $i++){
        $todo[] = s3cloud_sanitize_text_or_array($_POST['delete-list'][$i]);
    }
    // Main loop

    /*
    for ($i = 0; $i < count($_POST['delete-list']); $i++) {
        $prefix = sanitize_text_field($_POST['prefix']);
        $key = trim($_POST['delete-list'][$i]);
    */

    if (isset($_POST['delete-list']) && is_array($_POST['delete-list'])) {
        $delete_list = array_map('sanitize_text_field', $_POST['delete-list']);
        
        for ($i = 0; $i < count($delete_list); $i++) {
            $key = trim($delete_list[$i]);
            // Continue with the rest of your code using $prefix and $key
        
               if(substr($key, -1) =='/') {
                        $objects = $s3cloud_s3->listObjects([
                            'Bucket' => $s3cloud_bucket_name,
                            "Prefix" => $key,
                        ]);
                        foreach ($objects['Contents']  as $object) {
                            $fcontent = $object['Key'];
                            if(substr($fcontent,0,strlen($key)) == $key and strlen($fcontent) > strlen($key))
                            {
                               wp_die('not_empty_folder');
                            } 
                        }
               } 
               try{
                $x = $s3cloud_s3->deleteObject([
                    'Bucket' => $s3cloud_config['s3-access']['bucket'],
                    'Key' => $key,
                ]);
            } catch (S3Exception $e) {
                error_log("Fail to Delete: ".$key);
                error_log($e->getMessage());
                wp_die('fail_delete');
            }
        }  // end for next

        wp_die('delete_ok');
    } // end if
} // end function
function s3cloud_ajax_create_handle() {


    if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
        // debug2();
       // wp_die('The nonce verification failed. Please try again.');
    }
    if(empty($_POST['folder-name']))
      wp_die('empty: empty_folder_name');
    else
    $prefix = sanitize_text_field($_POST['prefix']);
      $folder_name = sanitize_text_field($_POST['folder-name']);

      if (current_user_can('administrator')) {
        // The current user is an administrator
        //echo 'You are an administrator.';
    } else {
        // The current user is not an administrator
        die('You are not an administrator. (7)');
    }

    

    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once esc_url($path);

    if(empty($s3cloud_region) or empty($s3cloud_secret_key) or empty($s3cloud_access_key)) {
        wp_die('fields_blank');
    }
    
    if (isset($_POST['bucket'])) {
     $s3cloud_bucket_name =  sanitize_text_field($_POST['bucket']);
    }
    if(isset($_POST['prefix']))
       $prefix = sanitize_text_field($_POST['prefix']);
    else
      wp_die('empty_prefix1');
    //$credjson = base64_decode($s3cloud_access_key);
    //$credarray = json_decode($credjson);

 

    try {
        // TEST
        $buckets = $s3cloud_s3->listBuckets();
    } catch (AWSException $e) {
        wp_die('fail_s3');
    }
            $prefix_name = trim($prefix.$folder_name);
            $prefix_name = str_replace(' ', '_', $prefix_name);
            $prefix_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $prefix_name);
            if($prefix.$folder_name != $prefix_name)
              die('wrong_name');
            try {
                $result = $s3cloud_s3->doesObjectExist(
                    $s3cloud_config['s3-access']['bucket'],  $prefix_name . '/');
                    if ($result)
                      wp_die('folder_exist');
                // create
                $result = $s3cloud_s3->putObject([
                    'Bucket' => $s3cloud_config['s3-access']['bucket'], // Defines name of Bucket
                    'Key' => $prefix_name . '/', //Defines Folder name
                ]); 
               wp_die('created');
            } catch (AWSException $e) {
                error_log(explode(';', $e->getMessage())[1]);
                wp_die(esc_attr(explode(';', $e->getMessage())[1]));
            }
}

function s3cloud_ajax_create_filesys() {

    // wp_die("OK !!!");

   require_once S3CLOUDPATH . "/functions/tree_data_filesys.php";
  // die();


}

function s3cloud_ajax_create_filesys_cloud() {


    require_once S3CLOUDPATH . "/functions/tree_data_filesys_cloud.php";


}

function s3cloud_ajax_transf_files_to_cloud() {

    require_once S3CLOUDPATH . "/functions/transfer_to_cloud.php";

}

function s3cloud_ajax_truncate_inic() {
    global $wpdb;


    
    if (current_user_can('administrator')) {
        // The current user is an administrator
        //echo 'You are an administrator.';
    } else {
        // The current user is not an administrator
        die('You are not an administrator. (8)');
    }


    $table_name = $wpdb->prefix . "s3cloud_copy";   
    if (s3cloud_tablexist($table_name)) {
     $query = "TRUNCATE TABLE " . $table_name;
     $r = $wpdb->query($query);
    // $r = $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $table_name));


    }
    $table_name = $wpdb->prefix . "s3cloud_copy_files";   
    if (s3cloud_tablexist($table_name)) {
     $query = "TRUNCATE TABLE " . $table_name;
     $r = $wpdb->query($query);
     // $r = $wpdb->query($wpdb->prepare("TRUNCATE TABLE %s", $table_name));
 
    }
    die('OK!');
}


function s3cloud_ajax_truncate() {
    global $wpdb;
    global $s3cloud_folder_server;
    global $s3cloud_folder_cloud;
    global $s3cloud_server_cloud;
    global $s3cloud_bucket_name;
    global $s3cloud_time_limit;
    global $s3cloud_config;




    if (!isset($_POST['s3cloud_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['s3cloud_nonce'])), 's3cloud_action')) {
        // debug2();
        wp_die('The nonce verification failed. Please try again.');
     }





    if (isset($_POST["radValue"])) {
        $s3cloud_copy_speed = sanitize_text_field($_POST["radValue"]);
    } else {
        $s3cloud_copy_speed = "normal!!";
    }

    if (current_user_can('administrator')) {
        // The current user is an administrator
        //echo 'You are an administrator.';
    } else {
        // The current user is not an administrator
        die('You are not an administrator. (9)');
    }

    $s3cloud_time_limit = 120; 
    ini_set("max_execution_time", $s3cloud_time_limit);
    set_time_limit($s3cloud_time_limit); 

    if (isset($_POST["server_cloud"])) {
        $s3cloud_server_cloud = sanitize_text_field($_POST["server_cloud"]);
    } else {
        die("Missing Post server_cloud");
    }
    if (isset($_POST["folder_server"])) {
        $s3cloud_folder_server = sanitize_text_field($_POST["folder_server"]);
    } else {
        die("Missing Post folder_server");
    }
    if ($s3cloud_folder_server == "Root") {
        $s3cloud_folder_server = substr(ABSPATH, 0, strlen(ABSPATH) - 1);
    }
    if (isset($_POST["folder_cloud"])) {
        $s3cloud_folder_cloud = sanitize_text_field($_POST["folder_cloud"]);
    } else {
        die("Missing Post folder_cloud");
    }
    if (isset($_POST["bucket_name"])) {
        $s3cloud_bucket_name = sanitize_text_field($_POST["bucket_name"]);
    } else {
        die("Missing Post bucket_name");
    }

    if (
        !isset($_POST["nonce"]) ||
        !wp_verify_nonce(sanitize_text_field($_POST["nonce"]), "s3cloud_ajax_truncate")
    ) {
       // die("Nonce Fail");
    }

    if(! function_exists('s3cloud_getHumanReadableSize')){
        function s3cloud_getHumanReadableSize($bytes)
        {
            if ($bytes > 0) {
                $base = floor(log($bytes) / log(1024));
                $units = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]; //units of measurement
                return number_format($bytes / pow(1024, floor($base)), 3) .
                    " $units[$base]";
            } else {
                return "0 bytes";
            }
        }
    }

    if(!function_exists('s3cloud_record_debug')){
        function s3cloud_record_debug($text)
        {
            global $wpdb;
    
            $table_name = $wpdb->prefix . "s3cloud_copy";
            if (!s3cloud_tablexist($table_name)) {
                return;
            }
    
            $txt = PHP_EOL . date("Y-m-d H:i:s") . " " . PHP_EOL;
            $txt .= __("Memory Usage Now:", "s3cloud");
    
            $txt .= function_exists("memory_get_usage")
                ? s3cloud_getHumanReadableSize(round(memory_get_usage(), 0))
                : 0;
            $txt .= PHP_EOL;
            $txt .= __("Memory Peak Usage:", "s3cloud") . " ";
            $txt .= s3cloud_getHumanReadableSize(memory_get_peak_usage());
            $txt .= PHP_EOL . $text . PHP_EOL;
            $txt .= "------------------------------";
    
            //$query = "select debug from $table_name ORDER BY id DESC limit 1";
            $query = $wpdb->prepare("SELECT debug FROM %s ORDER BY id DESC LIMIT 1", $table_name);
            $debug = $wpdb->get_var($query);
            $content = $debug . $txt;
            /*
            $r = $wpdb->query(
                $wpdb->prepare("UPDATE  `$table_name` SET debug = %s", $content)           
            );
            */
            $r = $wpdb->query($wpdb->prepare("UPDATE %s SET debug = %s", $table_name, $content));


        }
    }


    
    $table_name = $wpdb->prefix . "s3cloud_copy"; 
    //$query = "update " . $table_name . " SET mystatus = 'end'";
    //$r = $wpdb->query($query);
    $r = $wpdb->query($wpdb->prepare("UPDATE %s SET mystatus = 'end'", $table_name));
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    //$query =
    //    "select * from " . $table_name;
    //$r = $wpdb->get_results($query, ARRAY_A);
    $r = $wpdb->get_results($wpdb->prepare("SELECT * FROM %s", $table_name), ARRAY_A);

    
    if ($r === false) {
        die("Fail to read table (to clear)");
    }

    if (count($r) < 1) {
        // end of job ...
        die('OK');
    }

    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;
    


    for ($i = 0; $i < count($r); $i++) {
        $r[$i]["name"] = base64_decode($r[$i]["name"]);
    }
    //sort...
    usort($r, function ($a, $b) {
        return strnatcasecmp($a["name"], $b["name"]);
    });
    for ($i = 0; $i < count($r); $i++) {
        $id = $r[$i]["id"];
        $name = $r[$i]["name"];
        $complete_name = $name;
        $pos2 = strrpos($name, "/");
        $filepath = trim(substr($name, 0, $pos2 + 1));
        if ($s3cloud_server_cloud == "cloud") {
            if ($pos2 === false) {
                $filepath = "";
                $namefile = $name;
            } else {
                $filepath = trim(substr($name, 0, $pos2 + 1));
                $namefile = trim(substr($name, $pos2));
            }
        } else {
            $namefile = trim(substr($name, $pos2 + 1));
        }
        $pos = strrpos($namefile, ".");
        if ($pos === false) {
            $part = "";
        } else {
            $part = trim(substr($namefile, $pos + 1));
        }
        if ($part == ".s3cloudpart") {
            $original_name = trim(substr($namefile, 0, $pos));
        } else {
            $original_name = $namefile;
        }
        $newarray[$i]["originalname"] = $original_name;
        $newarray[$i]["filepath"] = $filepath;
        $newarray[$i]["namefile"] = $namefile;
        $newarray[$i]["part"] = $part;
        $newarray[$i]["id"] = $id;
        $newarray[$i]["complete_name"] = $complete_name;
    } // end loop
    // main loop
    for ($i = 0; $i < count($newarray); $i++) {
        if (!isset($newarray[$i])) {
            continue;
        }
        if (strpos($r[$i]["name"], ".s3cloudpart") === false) {
            continue;
        }
        $s3cloud_original_name = $newarray[$i]["originalname"];
        $newarray_todo = $newarray;
        for ($j = 0; $j < count($newarray); $j++) {
            if ($newarray[$j]["originalname"] == $s3cloud_original_name) {
                $newarray_todo[$j] = $newarray[$j];
            }
        }
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>   Clean Server  <<<<<<<<<<<<<<<<<<<<<<<
            $original_name = $newarray[$i]["originalname"];
            $file_path = $newarray[$i]["filepath"];
            if (empty($original_name)) {
                continue;
            }
            $pos = strpos($original_name, ".s3cloudpart");
            if ($pos === false) {
                continue;
            }
            for ($j = 0; $j < count($newarray_todo); $j++) {
                if (empty($newarray_todo[$j]["originalname"])) {
                     continue;
                }
                if (strpos($newarray_todo[$j]["namefile"], ".s3cloudpart") === false) {
                    continue;
                }
                $file_part_name = $newarray_todo[$j]["namefile"];
                $filepath = trim($newarray_todo[$j]["filepath"]);
                $s3cloud_complete_name = $newarray[$j]["complete_name"];
                if (substr($s3cloud_folder_cloud, 0, 4) == "Root") {
                    $s3cloud_folder_cloud = substr($s3cloud_folder_cloud, 5);
                }
                if($s3cloud_server_cloud == 'server') {
                    $filetemp = $s3cloud_complete_name; 
                }
                else
                {
                   $filetemp = $s3cloud_folder_server . $s3cloud_folder_cloud . "/" . $file_part_name;
                }
                while (strpos($filetemp, "//") !== false) {
                    $filetemp = str_replace("//", "/", $filetemp);
                }
                try {
                    if (file_exists($filetemp)) {
                        unlink ($filetemp);
                    }
                } catch (Exception $exception) {
                    $msg =
                        "Failed Erase Temp File (1), with error: " .
                        $exception->getMessage();
                    s3cloud_record_debug($msg);
                   // die($msg); // with error: " . $exception->getMessage();
                    // return "-1";
                }
            } // end for next do it
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>   Clean Cloud  <<<<<<<<<<<<<<<<<<<<<<<
            if (empty($original_name)) {
                continue;
            }
            for ($j = 0; $j < count($newarray_todo); $j++) {
                if (empty($newarray_todo[$j]["namefile"])) {
                    continue;
                }
                if (strpos($newarray_todo[$j]["namefile"], ".s3cloudpart") === false) {
                    continue;
                }
                if (substr($s3cloud_folder_cloud, 0, 4) == "Root") {
                    $s3cloud_folder_cloud = ''; //  substr($s3cloud_folder_cloud, 5);
                }
                $s3cloud_name_file = $newarray_todo[$j]["namefile"];
                $s3cloud_original_name = $newarray_todo[$j]["originalname"];
                $s3cloud_complete_name =  $newarray_todo[$j]["complete_name"];
                $pos = strrpos($s3cloud_folder_server, "/");
                $capar = substr($s3cloud_folder_server, 0, $pos + 1);
                $s3cloudkey =
                        $s3cloud_folder_cloud .
                        "/" .
                        str_replace($capar, "", $s3cloud_complete_name);
                while (strpos($s3cloudkey, "//") !== false) {
                    $s3cloudkey = str_replace("//", "/", $s3cloudkey);
                }
                if(substr($s3cloudkey,0,1) == '/')
                   $s3cloudkey = substr($s3cloudkey,1); 
                    // delete cloud temp
                    try {
                        $objInfo = $s3cloud_s3->doesObjectExist(
                            $s3cloud_bucket_name,
                            $s3cloudkey
                        );
                        if ($objInfo) {
                            $result = $s3cloud_s3->deleteObject([
                                "Bucket" => $s3cloud_bucket_name,
                            "Key" => $s3cloudkey,
                            ]);
                        }
                    } catch (Exception $exception) {
                        $msg =
                            "Failed Cloud Delete Temp Cloud File with error: " .
                            $exception->getMessage();
                            s3cloud_record_debug($msg);
                        die(
                            "Failed to Delete Temporary Object and join part " .
                            esc_attr($s3cloudkey)
                        ); // with error: " . $exception->getMessage();
                        return "-1";
                    }
            } // end for next do it
       //  } // end Server or cloud
}   //   } // end main loop
   die('OK');  
} // end function




function s3cloud_sanitize_text_or_array($array_or_string) {
    if( is_string($array_or_string) ){
        $array_or_string = sanitize_text_field($array_or_string);
    }elseif( is_array($array_or_string) ){
        foreach ( $array_or_string as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = s3cloud_sanitize_text_or_array($value);
            }
            else {
                $value = sanitize_text_field( $value );
            }
        }
    }
    return $array_or_string;
}

function s3cloud_create_db_copy_files()
{

    global $wpdb;


    if (current_user_can('administrator')) {
        // The current user is an administrator
        //echo 'You are an administrator.';
    } else {
        // The current user is not an administrator
        return;
        die('You are not an administrator.(10)');
    }


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table = $wpdb->prefix . "s3cloud_copy_files";
    if (s3cloud_tablexist($table)){

        //error_log( $table . '  exist!');

        //$query = "DROP TABLE " . $table; 
        //$r = $wpdb->query($query);   
        // return;

        $query = "SHOW COLUMNS FROM " . $table . " LIKE 'splited'";
        $query = "SHOW COLUMNS FROM " . $table . " LIKE 'splited'";
        $wpdb->query($query);
        if (empty($wpdb->num_rows)) {
            //$alter = "ALTER TABLE " . $table . " ADD splited varchar(1) NOT NULL";
            ob_start();
            //$wpdb->query($alter);
            $wpdb->query($wpdb->prepare("ALTER TABLE %s ADD splited varchar(1) NOT NULL", $table));
  
            ob_end_clean();
        }


        //$query = "SHOW COLUMNS FROM " . $table . " LIKE 'etag'";
        //$query = $wpdb->prepare("SHOW COLUMNS FROM %s LIKE %s", $table, 'etag');
        /*
        $query = $wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            'etag'
        );
        */

        $query = "SHOW COLUMNS FROM " . $table . " LIKE 'etag'";

        $wpdb->query($query);



        if (empty($wpdb->num_rows)) {
            // $alter = "ALTER TABLE " . $table . " ADD etag varchar(255) NOT NULL";
            ob_start();
            // $wpdb->query($alter);
            $wpdb->query($wpdb->prepare("ALTER TABLE %s ADD etag varchar(255) NOT NULL", $table));

            ob_end_clean();
        }

        //$query = "SHOW COLUMNS FROM " . $table . " LIKE 'part_number'";
        //$wpdb->query($query);
        //$wpdb->query($wpdb->prepare("SHOW COLUMNS FROM %s LIKE 'part_number'", $table));

        /*
        $query = $wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            'part_number'
        );

        $query = $wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            'part_number'
        );
        */

        $query = "SHOW COLUMNS FROM " . $table . " LIKE 'part_number'";


        $result = $wpdb->query($query);


        if (empty($wpdb->num_rows)) {
            //$alter = "ALTER TABLE " . $table . " ADD part_number varchar(255) NOT NULL";
            ob_start();
            //$wpdb->query($alter);
            $wpdb->query($wpdb->prepare("ALTER TABLE %s ADD part_number varchar(255) NOT NULL", $table));
 
            ob_end_clean();
        }

        //$query = "SHOW COLUMNS FROM " . $table . " LIKE 'upload_id'";
        //$wpdb->query($query);

       // $wpdb->query($wpdb->prepare("SHOW COLUMNS FROM %s LIKE 'upload_id'", $table));
       /*
        $query = $wpdb->prepare(
            "SHOW COLUMNS FROM ".$table_name. " LIKE %s",
            'upload_id'
        );
        */
        
        $query = "SHOW COLUMNS FROM " . $table . " LIKE 'upload_id'"; 
        // Execute the query
        $result = $wpdb->query($query);

        //
        

        if (empty($wpdb->num_rows)) {
            //$alter = "ALTER TABLE " . $table . " ADD upload_id varchar(255) NOT NULL";
            ob_start();
            $wpdb->query($wpdb->prepare("ALTER TABLE %s ADD upload_id varchar(255) NOT NULL", $table));

            //$wpdb->query($alter);
            ob_end_clean();
        }


        return;
    }
    //error_log( $table . ' NAO  exist!');
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE " . $table . " (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `splited` varchar(1) NOT NULL,
        `etag` varchar(255) NOT NULL,  
        `part_number` varchar(255) NOT NULL,  
        `upload_id` varchar(255) NOT NULL,       
        `flag` varchar(1) NOT NULL,
        `obs` text NOT NULL,
        UNIQUE (`id`),
        UNIQUE (`name`)
    )";
    dbDelta($sql);
}
function s3cloud_create_db_copy()
{
    global $wpdb;


    if (current_user_can('administrator')) {
        // The current user is an administrator
        //echo 'You are an administrator.';
    } else {
        // The current user is not an administrator
        return;
        die('You are not an administrator. (11)');
    }

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $table = $wpdb->prefix . "s3cloud_copy";
    if (s3cloud_tablexist($table))
        return;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE " . $table . " (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `date_inic` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
        `from` varchar(10) NOT NULL, 
        `bucket` longtext NOT NULL, 
        `folder_server` longtext NOT NULL, 
        `folder_cloud` longtext NOT NULL, 
        `log` longtext NOT NULL,
        `qfiles` int(11) NOT NULL,
        `pointer` int(11) NOT NULL,
        `mystatus` varchar(20) NOT NULL,
        `debug` longtext NOT NULL,
        `flag` varchar(1) NOT NULL,
        `obs` text NOT NULL,
        UNIQUE (`id`)
    )";
    dbDelta($sql);
}


function s3cloud_tablexist($table)
{
    global $wpdb;
    $table_name = $table;
    //if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name)
        return true;
    else
        return false;
}


