<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
global $s3cloud_region;
global $s3cloud_secret_key;
global $s3cloud_access_key;

use Aws\Exception\AwsException;


echo '<div class="wrap-s3cloud ">' . "\n";
echo '<h2 class="title">Contabo Settings</h2>' . "\n";
echo '<p class="description">';
echo esc_attr__("Fill out all this information below before open Contabo Tab.", "s3cloud");
echo '</p>' . "\n";
echo '<br />';
esc_attr_e("You can get this information at Contabo website.", "s3cloud");
echo '<br />';
echo '<br />';
if (isset($_GET['page']) && sanitize_text_field($_GET['page']) == 's3cloud_admin_page') {
    if (isset($_POST['process']) && $_POST['process'] == 's3cloud_admin_page') {
      
        debug2($_POST['s3cloud_nonce_field']);
            if (isset($_POST['s3cloud_nonce_field']) && !wp_verify_nonce(sanitize_text_field($_POST['s3cloud_nonce_field']), 's3cloud_nonce_action')) {
            die('Invalid Nonce!!!');
         } 
      
      
      
      
        if (isset($_POST['region'])) {
            $s3cloud_region = sanitize_text_field($_POST['region']);
            if (!update_option('s3cloud_region', $s3cloud_region))
                add_option('s3cloud_region', $s3cloud_region);
        }
        if (isset($_POST['secret_key'])) {
            $s3cloud_secret_key = sanitize_text_field($_POST['secret_key']);
            if (!update_option('s3cloud_secret_key', $s3cloud_secret_key))
                add_option('s3cloud_secret_key', $s3cloud_secret_key);
        }
        if (isset($_POST['access_key'])) {
            $s3cloud_access_key = sanitize_text_field($_POST['access_key']);
            if (!update_option('s3cloud_access_key', $s3cloud_access_key))
                add_option('s3cloud_access_key', $s3cloud_access_key);
        }
        s3cloud_updated_message();
        echo '<br /><br />';
    }
}
if (isset($_GET['page']) && $_GET['page'] == 's3cloud_admin_page') {
    if (isset($_POST['process']) && $_POST['process'] == 's3cloud_admin_page_test') {
        // Test
        try {
            global $s3cloud_region;
            global $s3cloud_secret_key;
            global $s3cloud_access_key;
            if (empty($s3cloud_region) or empty($s3cloud_secret_key) or empty($s3cloud_access_key)) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<br /><b>';
                echo esc_attr__('Please, fill out the 3 fields below', 's3cloud');
                echo '<br /><br /></div>';
                echo '<br /><br />';
            } else {

                $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
                require_once $path;

                $buckets = $s3cloud_s3->listBuckets();
                echo '<div class="notice notice-success is-dismissible">';
                echo '<br /><b>';
                echo esc_attr_e('Connection With Contabo S3 Successful!', 's3cloud');
                echo '<br /><br /></div>';
                echo '<br /><br />';
            }
        } catch (AWSException $e) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<br /><b>';
            echo "<b>" . esc_attr($e->getStatusCode()) . "\n" . esc_attr($e->getAwsErrorCode()) . "</b>";
            echo esc_attr(explode(';', $e->getMessage())[1]);
            echo '<br /><br /></div>';
            echo '<br /><br />';
        }
    }
}
?>
<form class="s3cloud -form" method="post" action="admin.php?page=s3cloud_admin_page&tab=settings">
    <input type="hidden" name="process" value="s3cloud_admin_page" />

    <?php
    // Gera e adiciona o campo nonce ao formulário
    wp_nonce_field('s3cloud_nonce_action', 's3cloud_nonce_field');
    //$nonce = wp_create_nonce('s3cloud_nonce_action');
    //echo '<input type="text" name="s3cloud_nonce_field" value="' . esc_attr($nonce) . '" />';

    ?>




    <label for="region"><?php esc_attr_e("Region", "s3cloud"); ?>:</label>
    <input type="text" id="region" name="region" value="<?php echo esc_attr($s3cloud_region); ?>">
    <br><br>
    <input type="hidden" name="process" value="s3cloud_admin_page" />
    <label for="secret_key"><?php esc_attr_e("Secret Key", "s3cloud"); ?>:</label>
    <input type="password" id="secret_key" name="secret_key" size="40" value="<?php echo esc_attr($s3cloud_secret_key); ?>">
    <br><br>
    <input type="hidden" name="process" value="s3cloud_admin_page" />
    <label for="access_key"><?php esc_attr_e("Access Key", "s3cloud"); ?>:</label>
    <input type="text" id="access_key" name="access_key" size="40" value="<?php echo esc_attr($s3cloud_access_key); ?>">
    <br><br>
    <br>
    <input type="hidden" name="process" value="s3cloud_admin_page" />
    <?php
    echo '<input class="s3cloud -submit button-primary" type="submit" value="Update" />';
    echo '</form>' . "\n";
    ?>
    <br><br>
    <form class="s3cloud -form" method="post" action="admin.php?page=s3cloud_admin_page&tab=settings">
        <input type="hidden" name="process" value="s3cloud_admin_page_test" />
        <?php
        echo '<input class="s3cloud -submit button-secondary" type="submit" value="Test Connection" />';
    echo '</form>' . "\n";
        echo '<div class="main-notice">';
        echo '</div>' . "\n";
        echo '</div>';
    function s3cloud_s3cloud_stripNonAlphaNumeric($string)
        {
            return preg_replace("/[^a-z0-9]/i", "", $string);
        }
