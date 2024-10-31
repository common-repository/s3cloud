<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 * 
 */
if (!defined('ABSPATH')) {
  die('We\'re sorry, but you can not directly access this file.');
}
?>
<div id="s3cloud-logo">
  <img src="<?php echo esc_attr(S3CLOUDIMAGES); ?>/logo.png" width="175">
</div>
<?php



if (current_user_can('administrator')) {
  // The current user is an administrator
  //echo 'You are an administrator.';
} else {
  // The current user is not an administrator
  die('You are not an administrator.');
  return;
}

if (isset($_GET['tab']))
  $active_tab = sanitize_text_field($_GET['tab']);
else
  $active_tab = 'dashboard';
?>
<h2 class="nav-tab-wrapper">
  <a href="tools.php?page=s3cloud_admin_page&tab=dashboard" class="nav-tab">Dashboard</a>
  <a href="tools.php?page=s3cloud_admin_page&tab=settings" class="nav-tab">Settings</a>
  <a href="tools.php?page=s3cloud_admin_page&tab=debug" class="nav-tab">Debug</a>
  <a href="tools.php?page=s3cloud_admin_page&tab=contabo" class="nav-tab">Contabo</a>
  <!-- <a href="tools.php?page=s3cloud_admin_page&tab=transf" class="nav-tab">Transf</a> -->
  <a href="tools.php?page=s3cloud_admin_page&tab=transf&s3cloud_nonce=<?php echo esc_attr(wp_create_nonce('s3cloud_action')); ?>" class="nav-tab">Transf</a>

</h2>
<?php
if ($active_tab == 'settings') {
  require_once(S3CLOUDPATH . 'dashboard/settings.php');
} elseif ($active_tab == 'contabo') {
  echo '<div class=wrap-s3cloud>';
  require_once(S3CLOUDPATH . 'dashboard/contabo.php');
  echo '</div>';
} elseif ($active_tab == 'debug') {
  echo '<div class=wrap-s3cloud>';
  require_once(S3CLOUDPATH . 'dashboard/debug.php');
  echo '</div>';
} elseif ($active_tab == 's3cloud_delete') {
  echo '<div class=wrap-s3cloud>';
  require_once(S3CLOUDPATH . "/s3api/s3cloud_delete.php");
  echo '</div>';
} elseif ($active_tab == 'transf') {
  echo '<div class=wrap-s3cloud>';
  require_once(S3CLOUDPATH . 'dashboard/transf.php');
  echo '</div>';
} elseif ($active_tab == 'transfer_debug') {
  echo '<div class=wrap-s3cloud>';
  require_once(S3CLOUDPATH . 's3api/transfer_debug.php');
  echo '</div>';


} else {
  require_once(S3CLOUDPATH . 'dashboard/dashboard.php');
}
