<?php

// Namespace
namespace BMI\Plugin;

if (!defined('ABSPATH')) {
  exit;
}

$response_title = __('Backup process will begin shortly', 'backup-migration');
$response_msg = __('An email notification will be sent upon backup completion', 'backup-migration');
$response_note = '';

if (defined('ONCE_PER_HOUR_MESSAGE')){
  $response_title = __('Unable to start backup', 'backup-migration');
  $response_msg = __('Backups can only be triggered once per hour', 'backup-migration');
}else if (defined('ALREADY_RUNNING_MESSAGE')){
  $response_title = __('Unable to start backup', 'backup-migration');
  $response_msg = __('A backup is currently in progress. Please wait until it completes.', 'backup-migration');
}else if (defined('CURL_METHOD_NOT_WORKING')){
  $response_note = __('If you encounter issues with the backup, please enable cURL on your server and try again', 'backup-migration');
}else if (defined('CHANGED_TO_CURL_METHOD')){
  $response_note = __("Note: The backup method has been switched to an experimental alternative method.", 'backup-migration');
}else if (defined('REQUEST_FAILED')){
  $response_title = __('Unable to start backup', 'backup-migration');
  $response_msg = __('Failed to send request to the server', 'backup-migration');
}else if (defined('INVALID_REQUEST')){
  $response_title = __('Invalid request', 'backup-migration');
  $response_msg = __('You are not authorized to perform this action', 'backup-migration');
} 

?>
<div class="container" id="bmi">
  <img src="<?php echo BMI_ASSETS . '/images/logo.png';?>" alt="Logo" class="logo">
  <p class="major"><?php echo $response_title;?></p>
  <p class="secondary"><?php echo  $response_msg; ?></p>
  <p class="notice"><?php echo $response_note; ?></p>
</div>
<style>
@import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap");
.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  text-align: center;
  font-family: 'Montserrat', sans-serif;
}

.logo {
  width: 100px;
  height: auto;
}

/* Animation */
.container {
  opacity: 0;
  transform: translateY(-20px);
  transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
}

.container.show {
  opacity: 1;
  transform: translateY(0);
}

.major {
  font-size: 24px;
  font-weight: 600;
  margin-top: 15px;
  margin-bottom: 5px;
}

.secondary {
  font-size: 18px;
  font-weight: 400;
  margin-top: 10px;
}

.notice {
  font-size: 14px;
  font-weight: 300;
  margin-top: 10px;
  color: #777;
}
</style>
<script>
// Add animation class after the page loads
document.addEventListener('DOMContentLoaded', function() {
  var container = document.querySelector('.container');
  container.classList.add('show');
});
</script>
