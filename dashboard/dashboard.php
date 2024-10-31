<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
echo '<div class="wrap-s3cloud ">' . "\n";
echo '<h2 class="title">S3Cloud Instructions</h2>' . "\n";
echo '<p class="description">';
echo esc_attr__("This plugin connect you with your Contabo S3-compatible Object Storage, using S3-compatible API.", "s3cloud") . '</p>' . "\n";
?>
<br />
<b>
    <?php echo  esc_attr__("To Start, after order their service, go to contabo.com object storage panel.", "s3cloud"); ?>
</b>
<br /><br />
<?php
echo  esc_attr__('Go to page "Security & Access.', "s3cloud");
echo '<br> ';
echo  esc_attr__("Look for: S3 Object Storage Credentials.", "s3cloud");
echo '<br> ';
echo  esc_attr__("Copy Access Key and Secret Key.", "s3cloud");
echo '<br>';
echo  esc_attr__("Then, paste them on the tab Settings of this plugin.", "s3cloud");
echo '<br> ';
echo  esc_attr__("After that, go to tab: Contabo to navigate on your Cloud.", "s3cloud");
echo '<br> ';
echo  esc_attr__("Or Go to Transfer to make folders transfer from/to Server and Cloud.", "s3cloud");
echo '<br>';
echo  esc_attr__("If you need cancel de transfer, click CANCEL BUTTON and wait.", "s3cloud");
echo '<br>';
echo  esc_attr__("Don't use the BACK or STOP buttons on your browser, neither close it.", "s3cloud");
echo '<br>';
echo  esc_attr__("Otherwise, temporary files will not be deleted.", "s3cloud");
echo '<br>';
echo '<br> ';
esc_attr_e('Visit the plugin site for more details, video, FAQ and Troubleshooting page.', 's3cloud');
echo '<br>';
echo '<br>';
echo '<a href="https://s3cloudPlugin.com/" class="button button-primary">' . esc_attr__('Plugin Site', 's3cloud') . '</a>';
echo '&nbsp;&nbsp;';
echo '<a href="https://s3cloudPlugin.com/help/" class="button button-primary">' . esc_attr__('Online Guide', 's3cloud') . '</a>';
echo '&nbsp;&nbsp;';
echo '<a href="https://billminozzi.com/dove/" class="button button-primary">' . esc_attr__('Support Page', 's3cloud') . '</a>';
echo '&nbsp;&nbsp;';
echo '<a href="https://siterightaway.net/troubleshooting/" class="button button-primary">' . esc_attr__('Troubleshooting Page', 's3cloud') . '</a>';
echo '<br>';
echo '<br>';
echo '</div>';
