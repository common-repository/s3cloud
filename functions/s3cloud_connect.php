<?php 
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}

    $path = S3CLOUDPATH . "/vendor/autoload.php";
    require_once $path;
    // $region = "eu2";
    //$endpoints = "https://eu2.contabostorage.com";

    /* 
    use Aws\Common\Exception\MultipartUploadException;
    use Aws\S3\Model\MultipartUpload\UploadBuilder;
    use Aws\S3\S3Client;
    */

    /*
    use Aws\Common\Aws;
    use Aws\Common\Enum\Size;
    use Aws\Common\Exception\MultipartUploadException;
    use Aws\S3\Model\MultipartUpload\UploadBuilder;

    Status:
( ! ) Fatal error: Uncaught Error: 
Class 'Aws\Common\Aws\S3\S3Client' 
not found in /home/s3cloud/public_html/wp-content/plugins/s3cloud/functions/s3cloud_connect.php on line 57 ( ! ) Error: Class 'Aws\Common\Aws\S3\S3Client' not found in /home/s3cloud/public_html/wp-content/plugins/s3cloud/functions/s3cloud_connect.php on line 57 Call Stack #TimeMemoryFunctionLocation 10.0003432080{main}( ).../admin-ajax.php:0 20.600544765024do_action( $hook_name = &#39;wp_ajax_s3cloud_ajax_transf_files_to_cloud&#39; ).../admin-ajax.php:188 30.600544765400WP_Hook->do_action( $args = [0 =&gt; &#39;&#39;] ).../plugin.php:517 40.600544765400WP_Hook->apply_filters( $value = &#39;&#39;, $args = [0 =&gt; &#39;&#39;] ).../class-wp-hook.php:332 50.600544766528s3cloud_ajax_transf_files_to_cloud( &#39;&#39; ).../class-wp-hook.php:308 60.602644961928require_once( '/home/s3cloud/public_html/wp-content/plugins/s3cloud/functions/transfer_to_cloud.php ).../functions.php:369 70.602744962120s3cloud_ajax_copy( ).../transfer_to_cloud.php:120 80.604944971120s3cloud_make_transfer( $s3cloud_name_file = &#39;/home/s3cloud/public_html/tocloud/jornalda.tar.gz&#39;, $id_to_copy = &#39;1&#39;, $upload_id = &#39;&#39;, $splited = &#39;0&#39;, $part_number = &#39;&#39; ).../transfer_to_cloud.php:367 90.605044983216require_once( '/home/s3cloud/public_html/wp-content/plugins/s3cloud/functions/s3cloud_connect.php ).../transfer_to_cloud.php:463 There has been a critical error on this website.Learn more about troubleshooting WordPress.

  */
  
  use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\S3Client;



    global $s3cloud_region;
    global $s3cloud_secret_key;
    global $s3cloud_access_key;
    global $s3cloud_s3;
    global $s3cloud_config;


    if(!isset($s3cloud_region) or !isset($s3cloud_secret_key) or !isset($s3cloud_access_key)) {
        error_log("Fail to Connect to Cloud: (-51) ");
    }

    if(empty($s3cloud_region) or empty($s3cloud_secret_key) or empty($s3cloud_access_key)) {
        error_log("Fail to Connect to Cloud: (-52) ");
    }

    $endpoints = "https://" . $s3cloud_region . ".contabostorage.com";

    if (isset($_POST['bucket'])) 
        $s3cloud_bucket_name =  sanitize_text_field($_POST['bucket']);
    elseif (!isset($s3cloud_bucket_name))
       $s3cloud_bucket_name = '';  
    

    try{
        $s3cloud_config = [
        "s3-access" => [
            'key' => $s3cloud_access_key,
            'secret' => $s3cloud_secret_key,
            'bucket' => $s3cloud_bucket_name,
            'region' => $s3cloud_region,
            'version' => 'latest',
            'endpoint' => $endpoints
        ],
    ];


    $s3cloud_s3 = new Aws\S3\S3Client([
        "credentials" => [
            "key" => $s3cloud_config["s3-access"]["key"],
            "secret" => $s3cloud_config["s3-access"]["secret"],
        ],
        "use_path_style_endpoint" => true,
        "force_path_style" => true,
        "endpoint" => $s3cloud_config["s3-access"]["endpoint"],
        "version" => "latest",
        "region" => $s3cloud_config["s3-access"]["region"],
    ]);
    } catch (S3Exception $e) {
        error_log("Fail to Connect to Cloud: ".$key);
        error_log($e->getMessage());
        // wp_die('fail_delete');
    }

    if(!isset($s3cloud_s3)) {
       error_log("Fail to Connect to Cloud: (-5) ".$key);
    }
