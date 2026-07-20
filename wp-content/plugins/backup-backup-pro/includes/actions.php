<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  use BMI\Plugin\BMI_Logger;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\BMI_Ajax;
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\Services\BackupLifecycleManager as BackupLifecycleManager;
  use BMI\Plugin\Pro\Zip\BMIZipStream as ZipStream;
  use BMI\Plugin\Pro\Zip\BMIZipBatchProcessor;
  use BMI\Plugin\External\BMI_External_Storage_Manager as ExternalStorageManager;
  use BMI\Plugin\External\Stream\BMIZipStreamProvider;
  use BMI\Plugin\BMI_BackupMethodManager as MethodManager;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  add_filter('bmip_streaming_backup_space_check', function ($total_size_for_backup, $zip_progress) {
    $zip_progress->log(__("Checking free space, reserving...", 'backup-backup'), 'step');
    $provider = ZipStream::getConfiguredProvider();
    require_once BMI_INCLUDES . '/external/external-storage-manager.php';
    $availableSpace = ExternalStorageManager::getInstance()->getAvailableSpace($provider);
    update_option('bmi_required_space_stream', $total_size_for_backup);
    if ($availableSpace === false) {
      $zip_progress->log(__("Couldn't retrieve available space for the selected provider. Please make sure the provider is properly configured and try again.", 'backup-backup'), 'error');
      // Abort backup
      $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
      $zip_progress->log('not_able_to_retrieve_space_for_provider', 'verbose');

      $streamingSpaceSucceeded = false;
    } elseif ($availableSpace < $total_size_for_backup) {
      $zip_progress->log(__("Not enough free space in the selected provider to proceed with streaming backup. Required: ", 'backup-backup') . ($total_size_for_backup) . __(" bytes, Available: ", 'backup-backup') . ($availableSpace) . __(" bytes", 'backup-backup'), 'error');
      // Abort backup
      $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
      $zip_progress->log(__("There is no space for that backup, checked: ", 'backup-backup') . ($total_size_for_backup) . __(" bytes", 'backup-backup'), 'error');
      $zip_progress->log('not_enough_space_on_provider', 'verbose');
      $streamingSpaceSucceeded = false;
    } else {
      $zip_progress->log(__("Confirmed, there is more than enough space, checked: ", 'backup-backup') . ($availableSpace) . __(" bytes", 'backup-backup'), 'success');
      $streamingSpaceSucceeded = true;
    }

    return $streamingSpaceSucceeded;

  }, 10, 2);


  /**
   * Filter to check if streaming backup method is enabled and can be used.
   * 
   * all streaming backup filters, action and functions should be wrapped by the check of this filter to make sure they are only applied when streaming backup is enabled and can be used.
   */
  add_filter('bmip_streaming_backup_is_enabled', function($shouldStream) {
    require_once BMI_PRO_INC . 'zip/autoload.php';

    return ZipStream::canStream();
  });

  add_filter('bmip_streaming_backup_get_provider_label', function($provider) {
    require_once BMI_PRO_INC . 'zip/autoload.php';
    if (ZipStream::canStream()) {
      $provider = ZipStream::getConfiguredProvider();
      $labels = BMIZipStreamProvider::labels();
      return isset($labels[$provider]) ? $labels[$provider] : $provider;
    }

    return false;
  });
  
  add_action('bmip_streaming_backup_process_batch', function ($backupHeartBeat) {
    require_once BMI_PRO_INC . 'zip/autoload.php';
    
    BMIZipBatchProcessor::getInstance($backupHeartBeat)->process();
  });

  add_action('bmi_premium_after_process', function($success = false , $triggeredBy= "backup", $forceInform = false) {
      if ((bmi_get_config('OTHER:BACKUP:SUCCEED:NOTIS') || $forceInform)  && $triggeredBy == 'backup' && $success)
      {
        handleAfterProcessSuccess($triggeredBy);
      }
      if ((bmi_get_config('OTHER:BACKUP:FAILED:NOTIS') || $forceInform) && $triggeredBy == 'backup' && !$success)
      {
        $attachLog = Dashboard\bmi_get_config('OTHER:ATTACH:LOGS:TO:EMAIL');
        $attachDebugCode = Dashboard\bmi_get_config('OTHER:ATTACH:DEBUG:CODE:TO:EMAIL');
        handleAfterProcessFailure($triggeredBy, $attachLog == 'true' || $attachLog == true, $attachDebugCode == 'true' || $attachDebugCode == true);
      }
      if (bmi_get_config('OTHER:RESTORE:SUCCEED:NOTIS') && $triggeredBy == 'restore' && $success)
      {
        handleAfterProcessSuccess($triggeredBy);
      }
      if (bmi_get_config('OTHER:RESTORE:FAILED:NOTIS') && $triggeredBy == 'restore' && !$success)
      {
        $attachLog = Dashboard\bmi_get_config('OTHER:ATTACH:LOGS:TO:EMAIL');
        $attachDebugCode = Dashboard\bmi_get_config('OTHER:ATTACH:DEBUG:CODE:TO:EMAIL');
        handleAfterProcessFailure($triggeredBy, $attachLog == 'true' || $attachLog == true, $attachDebugCode == 'true' || $attachDebugCode == true);
      }
  });

  
  add_action('bmip_smart_exclusion_options', function ($post){
    $smart_exclusion = $post['smart-exclusion-enabled'] === 'true' ? true : false; // SMART:EXCLUSION:ENABLED
    $smart_exclusion_cache = $post['smart-exclusion-cache'] === 'true' ? true : false; // SMART:EXCLUSION:CACHE
    $smart_exclusion_deactivated_plugins = $post['smart-exclusion-deactivated-plugins'] === 'true' ? true : false; // SMART:EXCLUSION:DPLUGINS
    $smart_exclusion_debug_logs = $post['smart-exclusion-debug-logs'] === 'true' ? true : false; // SMART:EXCLUSION:DLOGS
    $smart_exclusion_non_used_themes = $post['smart-exclusion-non-used-themes'] === 'true' ? true : false; // SMART:EXCLUSION:NUTHEMES
    $smart_exclusion_post_revisions = $post['smart-exclusion-post-revisions'] === 'true' ? true : false; // SMART:EXCLUSION:PREVISIONS
    if (!bmi_set_config('SMART:EXCLUSION:ENABLED', $smart_exclusion)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `enable smart exclusion setting` with value ' . strval($smart_exclusion));
    }
    if (!bmi_set_config('SMART:EXCLUSION:CACHE', $smart_exclusion_cache)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `cache setting` with value ' . strval($smart_exclusion_cache));
    }
    if (!bmi_set_config('SMART:EXCLUSION:DPLUGINS', $smart_exclusion_deactivated_plugins)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `deactivated plugins setting` with value ' . strval($smart_exclusion_deactivated_plugins));
    }
    if (!bmi_set_config('SMART:EXCLUSION:DLOGS', $smart_exclusion_debug_logs)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `debug logs setting` with value ' . strval($smart_exclusion_debug_logs));
    }
    if (!bmi_set_config('SMART:EXCLUSION:NUTHEMES', $smart_exclusion_non_used_themes)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `non used themes setting` with value ' . strval($smart_exclusion_non_used_themes));
    }
    if (!bmi_set_config('SMART:EXCLUSION:PREVISIONS', $smart_exclusion_post_revisions)) {
      BMI_Logger::error('[BMI PRO] Couldn\'t save `post revisions setting` with value ' . strval($smart_exclusion_post_revisions));
    }
  });

  add_action('bmi_premium_other_options', function ($post) {
    
    $other_promotional_display = $post['hide-promotional-bmi-banners'] === 'true' ? true : false; // OTHER:PROMOTIONAL:DISPLAY
    $other_backup_succeed_notis = $post['backup_success_notify'] === 'true' ? true : false; // OTHER:BACKUP:SUCCEED:NOTIS
    $other_backup_failed_notis = $post['backup_failed_notify'] === 'true' ? true : false; // OTHER:BACKUP:FAILED:NOTIS
    $other_restore_succeed_notis = $post['restore_success_notify'] === 'true' ? true : false; // OTHER:RESTORE:SUCCEED:NOTIS
    $other_restore_failed_notis = $post['restore_failed_notify'] === 'true' ? true : false; // OTHER:RESTORE:FAILED:NOTIS
    $other_generate_debug_code = $post['generate_debug_code'] === 'true' ? true : false; // OTHER:ATTACH:DEBUG:CODE:TO:EMAIL
    $other_attach_logs_to_email = $post['add_logs_email'] === 'true' ? true : false; // OTHER:ATTACH:LOGS:TO:EMAIL
    $other_trigger_before_updates = $post['before_update_trigger'] === 'true' ? true : false; // OTHER:TRIGGER:BEFORE:UPDATES
    
    $error = 0;
    if (!bmi_set_config('OTHER:PROMOTIONAL:DISPLAY', $other_promotional_display)) {
      BMI_Logger::error('Backup Pro Other Promotional Display Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:BACKUP:SUCCEED:NOTIS', $other_backup_succeed_notis)) {
      BMI_Logger::error('Backup Pro Other Backup Succeed Notis Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:BACKUP:FAILED:NOTIS', $other_backup_failed_notis)) {
      BMI_Logger::error('Backup Pro Other Backup Failed Notis Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:RESTORE:SUCCEED:NOTIS', $other_restore_succeed_notis)) {
      BMI_Logger::error('Backup Pro Other Restore Succeed Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:RESTORE:FAILED:NOTIS', $other_restore_failed_notis)) {
      BMI_Logger::error('Backup Pro Other Restore Failed Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:ATTACH:DEBUG:CODE:TO:EMAIL', $other_generate_debug_code)) {
      BMI_Logger::error('Backup Pro Other Attach Debug Code To Email Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:ATTACH:LOGS:TO:EMAIL', $other_attach_logs_to_email)) {
      BMI_Logger::error('Backup Pro Other Attach Logs To Email Error');
      $error++;
    }
    if (!bmi_set_config('OTHER:TRIGGER:BEFORE:UPDATES', $other_trigger_before_updates)) {
      $error++;
    }
    
  });

  require_once BMI_PRO_INC . 'services/class-bmi-pro-backup-lifecycle-manager.php';
  add_filter('bmi_premium_store_config', function($response, $newStrategy) {
    return BackupLifecycleManager::getInstance()->saveStorageStrategyOption($response, $newStrategy);
  }, 10, 2);
  
  add_action('bmi_backup_upload_completed', function ($md5) {
    BackupLifecycleManager::getInstance()->handleUploadComplete($md5);
  });

  add_filter('bmi_external_storages', function ($storages) {
    $storages['sftp'] = [
      'option_key' => 'STORAGE::EXTERNAL::SFTP',
      'class' => 'BMI_External_SFTP',
      'file' => BMI_PRO_INC . 'external' . DIRECTORY_SEPARATOR . 'sftp.php',
      'label' => 'SFTP',
      'namespace' => '\\BMI\\Plugin\\External\\BMI_External_SFTP'
    ];

    $storages['onedrive'] = [
      'option_key' => 'STORAGE::EXTERNAL::ONEDRIVE',
      'class' => 'BMI_External_OneDrive',
      'file' => BMI_PRO_INC . 'external' . DIRECTORY_SEPARATOR . 'one-drive.php',
      'label' => 'OneDrive',
      'namespace' => '\\BMI\\Plugin\\External\\BMI_External_OneDrive'
    ];

    $storages['pcloud'] = [
      'option_key' => 'STORAGE::EXTERNAL::PCLOUD',
      'class' => 'BMI_External_PCloud',
      'file' => BMI_PRO_INC . 'external' . DIRECTORY_SEPARATOR . 'pcloud.php',
      'label' => 'pCloud',
      'namespace' => '\\BMI\\Plugin\\External\\BMI_External_PCloud'
    ];
    
    return $storages;
  });

  function handleAfterProcessSuccess($triggeredBy) {
    $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
    $subject = __('Backup Migration – Backup %s1 Successfully', 'backup-backup');
    $subject = str_replace(
      '%s1',
      $triggeredBy == 'backup' ? "Created" : "Restored",
      $subject
    );
    $message = __("Backup has been %s1 successfully for %s2.",'backup-backup');
    $message = str_replace(
      [
        '%s1',
        '%s2'
      ],
      [
        $triggeredBy == 'backup' ? "created" : "restored",
        get_bloginfo('name'),
      ],
      $message
    );
    BMP::send_notification_mail($email, $subject, $message, true);

  }

  function handleAfterProcessFailure($triggeredBy, $attachLog = false, $attachDebugCode = false) {
    $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
    $subject = __('Backup Migration – Backup %s1 Failed', 'backup-backup');
    $subject = str_replace(
      '%s1',
      $triggeredBy == 'backup' ? "Creation" : "Restoration",
      $subject
    );

    $message = __("There is something went wrong with the last backup during %s1 it for %s2.\n\r",'backup-backup');
    $message = str_replace(
      [
        '%s1',
        '%s2'
      ],
      [
        $triggeredBy == 'backup' ? "creating" : "restoring",
        get_bloginfo('name'),
      ],
      $message
    );


    $attachments = array();
    if ($attachLog){
      if ($triggeredBy == 'backup'){
        $latest = BMI_BACKUPS . DIRECTORY_SEPARATOR . 'latest.log';
      }else{
        $latest = BMI_BACKUPS . DIRECTORY_SEPARATOR . 'latest_migration.log';
      }

      if (file_exists($latest)){
        $message .= __("You can access the backup log from the attachments of this email\n\r",'backup-backup');
        array_push($attachments, $latest);
      }

    }


    if ($attachDebugCode){
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'ajax.php';
      $handler = new BMI_Ajax();
      $response = $handler->sendTroubleshootingDetails();
      if ($response['status'] == 'success'){
        $message .= str_replace(
          '%s1',
          $response['code'],
          __("Debug code: %s1\n\r",'backup-backup')
        );
        $message .= __("if you need support or clarification about the process you can forward the entire e-mail to \"support@backupbliss.com\"", 'backup-backup');
      }else{
        $message .= __("Debug code could not be generated.\n\r",'backup-backup');
      }    
    }
    

    BMP::send_notification_mail($email, $subject, $message, true);
  }


  add_action('bmip_zipping_methods', function () {
    $method = '.zip';
    $extension = bmi_get_config('BACKUP:EXTENSION:TYPE');

    if ($extension == '.tar') $method = '.tar';
    if ($extension == '.tar.gz') $method = '.tar.gz';

    ?>
      <div class="lh30">

        <div class="mbll">
          <label class="container-radio">
            Zip
            <input type="radio" name="backup_type_extension" value=".zip" <?php echo ($method == '.zip') ? 'checked' : '' ?>>
            <span class="checkmark-radio"></span>
          </label>
        </div>

        <div class="mbll">
          <span class="cf premium-function is-pro">
            <label class="left container-radio ml25">
              Tar
              <input type="radio" name="backup_type_extension" value=".tar" <?php echo ($method == '.tar') ? 'checked' : '' ?>>
              <span class="checkmark-radio"></span>
            </label>
            <span class="left premium premium-img premium-nt5"></span>
          </span>
        </div>

        <div class="" style="width: 185px;">
          <span class="cf premium-function is-pro">
            <label class="left container-radio ml25">
              Tar GZip
              <input type="radio" name="backup_type_extension" value=".tar.gz" <?php echo ($method == '.tar.gz') ? 'checked' : '' ?>>
              <span class="checkmark-radio"></span>
            </label>
            <span class="left premium premium-img premium-nt5"></span>
          </span>
        </div>

      </div>
    <?php
  });

  add_filter('bmip_backup_name', function ($name) {
    $name = substr($name, 0, -4);
    $ext = bmi_get_config('BACKUP:EXTENSION:TYPE');

    if ($ext == '.tar.gz') return $name . '.tar.gz';
    else if ($ext == '.tar') return $name . '.tar';
    else return $name . '.zip';
  });

  add_action('bmi_premium_errors', function() {
    
    if (file_exists(BMI_PRO_INC . 'notices/one-drive.php'))
      require_once BMI_PRO_INC . 'notices/one-drive.php';

    if (file_exists(BMI_PRO_INC . 'notices/pcloud-issues.php'))
      require_once BMI_PRO_INC . 'notices/pcloud-issues.php';

    if (file_exists(BMI_PRO_INC . 'notices/sftp-issues.php'))
      require_once BMI_PRO_INC . 'notices/sftp-issues.php';
  });

  add_action('bmip_storage_strategy_options', function () {
    $storageStrategy = bmi_get_config('STORAGE:STRATEGY');
    ?>
    <div class="lh40">
      <label for="storage-local-cloud" class="container-radio">
        <span class="f18">
          <?php esc_html_e("Store all backups locally and on all connected cloud storage(s)", 'backup-backup'); ?>
        </span>
        <input type="radio" name="backup_storage_strategy" id="storage-local-cloud" value="<?php echo BackupLifecycleManager::STORAGE_STRATEGY_LOCAL_AND_CLOUD; ?>" <?php echo ($storageStrategy == BackupLifecycleManager::STORAGE_STRATEGY_LOCAL_AND_CLOUD) ? 'checked' : ''; ?> />
        <span class="checkmark-radio"></span>
      </label>
    </div>

    <div class="lh40">
      <span class="cf premium-function is-pro">
        <label for="storage-cloud-only" class="container-radio left">
          <span class="f18">
            <?php esc_html_e("Store all backups only on connected cloud storage(s)", 'backup-backup'); ?>
          </span>
          <input type="radio" name="backup_storage_strategy" id="storage-cloud-only" value="<?php echo BackupLifecycleManager::STORAGE_STRATEGY_CLOUD_ONLY; ?>" <?php echo ($storageStrategy == BackupLifecycleManager::STORAGE_STRATEGY_CLOUD_ONLY) ? 'checked' : ''; ?> />
          <span class="checkmark-radio"></span>
        </label>
        <span class="left premium premium-img premium-nt5"></span>
      </span>
    </div>

    <div class="lh40">
      <span class="cf premium-function is-pro">
        <label for="storage-automatic-cloud" class="container-radio left">
          <span class="f18">
            <?php esc_html_e("Store automatic (scheduled) backups only on cloud storage and store manually created backups locally", 'backup-backup'); ?>
          </span>
          <input type="radio" name="backup_storage_strategy" id="storage-automatic-cloud" value="<?php echo BackupLifecycleManager::STORAGE_STRATEGY_HYBRID; ?>" <?php echo ($storageStrategy == BackupLifecycleManager::STORAGE_STRATEGY_HYBRID) ? 'checked' : ''; ?> />
          <span class="checkmark-radio"></span>
        </label>
        <span class="left premium premium-img premium-nt5"></span>
      </span>
    </div>

   <?php
  });

  add_action('bmip_direct_cloud_streaming_settings', function () {
      $isStreamEnabled = bmi_get_config('STREAM:DIRECT_CLOUD_STREAMING:ENABLED');

      function isRadioChecked($name, $isStreamEnabled)
      {
        $configValue = bmi_get_config('STREAM:DIRECT_CLOUD_STREAMING:PROVIDER');
        if ($configValue && $configValue === $name) {
          return 'checked';
        } else if ($configValue == false){
          $defaultProvider = BMIZipStreamProvider::getDefaultProvider();
          if ($defaultProvider === $name) {
            return 'checked';
          }
        }
        return '';
      }
      require_once BMI_PRO_INC . 'external/stream/autoload.php';

      $providerLabels = BMIZipStreamProvider::labels();
    ?>
      <div class="lh30 mbll">
    <div class="fo-title semibold">
      <span class="cf premium-function">
        <div class="left"><?php esc_html_e("Experimental: direct-to-cloud backup streaming", 'backup-backup'); ?></div>
        <span class="left premium premium-img"></span>
      </span>
    </div>
    <div class="f20">
       <?php esc_html_e("Stream backups directly to supported cloud storage providers without creating a local archive on your server.", 'backup-backup'); ?>
       <span class="f16 block">
        <?php esc_html_e("Note: Currently, only BackupBliss cloud storage is supported by this feature, and your backups will not be synced to other connected cloud storages with this feature.", 'backup-backup'); ?>
       </span>
    </div>
  </div>

  <div class="mm mm-border f20">
    <div class="lh30">
      <label class="bmi-checkbox-label">
        <input type="checkbox" id="direct-cloud-streaming" class="bmi_will_collapse"
          data-if-checked="direct_cloud_streaming_options" <?php echo ($isStreamEnabled) ? 'checked' : ''; ?>/>
        <span class="relative">
          <?php esc_html_e("Enable direct-to-cloud streaming", 'backup-backup'); ?>&nbsp;
          <span class="bmi-info-icon tooltip"
            tooltip="<?php esc_html_e('This reduces required disk space to near zero and is ideal for environments with limited storage. This feature is experimental and currently available only for selected providers.', 'backup-backup'); ?>"></span>
        </span>
      </label>
    </div>
  </div>

  <div class="mm mm-border mtl" id="direct_cloud_streaming_options" style="display: none;">
    <div class="lh30 mbl f20">
      <span
        class="medium"><?php esc_html_e("Select the cloud storage provider for direct streaming:", 'backup-backup'); ?></span>
    </div>
    <div class="lh40">

      <?php foreach ($providerLabels as $providerValue => $providerLabel):
        $isImplemented = BMIZipStreamProvider::isImplemented($providerValue);
      ?>
      <div class="mbll">
        <?php if (!$isImplemented): ?>
        <span class="cf tooltip" tooltip="<?php esc_html_e('Coming soon! Stay tuned.', 'backup-backup'); ?>">
        <?php endif; ?>
          <label class="container-radio<?php echo $isImplemented ? '' : ' not-allowed'; ?>">
            <?php echo esc_html($providerLabel); ?>
            <input type="radio" <?php echo $isImplemented ? '' : 'disabled '; ?>name="direct_cloud_provider" value="<?php echo esc_attr($providerValue); ?>" <?php echo isRadioChecked($providerValue, $isStreamEnabled); ?>>
            <span class="checkmark-radio"></span>
          </label>
        <?php if (!$isImplemented): ?>
        </span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

    </div>
  </div>


  <?php
  });

  add_filter('bmi_premium_store_streaming_configuration', function($response, $direct_cloud_streaming, $direct_cloud_provider) {
    if ($direct_cloud_streaming) {
      $storageStrategy = bmi_get_config('STORAGE:STRATEGY');
      if ($storageStrategy !== BackupLifecycleManager::STORAGE_STRATEGY_CLOUD_ONLY) {
        return [
          'status' => 'msg',
          'level' => 'error',
          'why' => esc_html__('To enable direct-to-cloud streaming, please set the option to store all backups in the connected cloud storage.', 'backup-backup')
        ];
      }
      bmi_set_config('STREAM:DIRECT_CLOUD_STREAMING:ENABLED', true);
        require_once BMI_INCLUDES . '/external/external-storage-manager.php';
        require_once BMI_PRO_INC . 'external/stream/autoload.php';

        if ($direct_cloud_provider === '') {
          return [
            'status' => 'msg',
            'level' => 'error',
            'why' => esc_html__('Please select a cloud storage provider for streaming.', 'backup-backup')
          ];
        }

        if (!BMIZipStreamProvider::isSupported($direct_cloud_provider) && !BMIZipStreamProvider::isImplemented($direct_cloud_provider)) {
          return [
            'status' => 'msg',
            'level' => 'error',
            'why' => esc_html__('Selected cloud storage provider is not supported or not implemented.', 'backup-backup')
          ];
        }
        
        if (!ExternalStorageManager::getInstance()->isStorageConfigured($direct_cloud_provider) && $direct_cloud_provider !== BMIZipStreamProvider::LOCAL) {
          return [
            'status' => 'msg',
            'level' => 'error',
            'why' => esc_html__('Selected cloud storage provider is not properly configured. Please first configure the provider.', 'backup-backup')
          ];
        }
        bmi_set_config('STREAM:DIRECT_CLOUD_STREAMING:PROVIDER', $direct_cloud_provider);

        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'class-backup-method-mananger.php';
        $backupMethodManager = new MethodManager();
        $backupMethodManager->changeBackupMethod(BMI_METHOD_BROWSER);

    } else {
      bmi_set_config('STREAM:DIRECT_CLOUD_STREAMING:ENABLED', false);
    }

    return $response;
  }, 10, 3);

  /**************************SMART EXCLUSION FILTERS**************************/

  /**
   * Filter to add cache folders as ignored paths while scanning for files to backup
   * 
   * @param array $ignored_paths_default Default ignored paths
   * @return array $ignored_paths_default Updated ignored paths with cache folders
   */
  add_filter('bmip_smart_exclusion_cache', function ($ignored_paths_default) {
    $cacheFolders = array(
      '***ABSPATH***/cache',
      '***ABSPATH***/tmp',
      '***ABSPATH***/temp',
      '***ABSPATH***/wp-content/cache',
      '***ABSPATH***/wp-content/et-cache',
      '***ABSPATH***/wp-content/wpo-cache',
      '***ABSPATH***/wp-content/wphb-cache',
      '***ABSPATH***/wp-content/uploads/cache',
      '***ABSPATH***/wp-content/uploads/wpfc-cache',
      '***ABSPATH***/wp-content/uploads/wp-super-cache',
      '***ABSPATH***/wp-content/uploads/w3tc',
      '***ABSPATH***/wp-content/uploads/lscache',
      '***ABSPATH***/wp-content/uploads/autoptimize',
      '***ABSPATH***/wp-content/uploads/br-live-cache',
      '***ABSPATH***/wp-content/uploads/br-cache',
      '***ABSPATH***/wp-content/uploads/br-temp'
    );

    return array_merge($ignored_paths_default, $cacheFolders);
  });

  /**
   * Filter to add deactivated plugins as ignored paths while scanning for files to backup
   * 
   * @param array $ignored_paths_default Default ignored paths
   * @return array $ignored_paths_default Updated ignored paths with deactivated plugins
   */
  add_filter('bmip_smart_exclusion_deactive_plugins', function ($ignored_paths_default) {
    $deactivated_plugin_dirs = array();

    $all_plugins = get_plugins();

    $active_plugins = get_option('active_plugins');

    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (!in_array($plugin_file, $active_plugins)) {
            $plugin_dir = dirname($plugin_file);
            $deactivated_plugin_dirs[] = WP_PLUGIN_DIR . '/' . $plugin_dir;
        }
    }

    return array_merge($ignored_paths_default, $deactivated_plugin_dirs);
  });

  /**
   * Filter to add non-used themes as ignored paths while scanning for files to backup
   * 
   * @param array $ignored_paths_default Default ignored paths
   * @return array $ignored_paths_default Updated ignored paths with non-used themes
   */
  add_filter('bmip_smart_exclusion_not_used_themes', function ($ignored_paths_default) {
    $non_used_themes = array();

    $all_themes = wp_get_themes();

    $active_theme = wp_get_theme();

    foreach ($all_themes as $theme) {
        if ($theme->get_stylesheet() !== $active_theme->get_stylesheet()) {
            $non_used_themes[] = $theme->get_stylesheet_directory();
        }
    }

    return array_merge($ignored_paths_default, $non_used_themes);
  });

  /**
   * Filter to add debug logs as ignored files while scanning for files to backup
   * 
   * @param array $ignored_files_default Default ignored files
   * @return array $ignored_files_default Updated ignored files with debug logs
   */
  add_filter('bmip_smart_exclusion_debug_logs', function ($ignored_files_default) {
    $debug_logs = array(  
      '***ABSPATH***/wp-content/debug.log',
    );

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && file_exists(WP_DEBUG_LOG)) {
      $debug_logs[] = WP_DEBUG_LOG;
    }


    return array_merge($ignored_files_default, $debug_logs);
  });

  /**
   * Filter to get the count of records within posts and postmeta tables excluding post revisions
   * 
   * @param string $query Query to get the count of records
   * @param string $table_name Name of the table
   * @return string $query Updated prepared query to get the count of records
   */
  add_filter('bmip_smart_exclusion_post_revisions_count', function ($query, $table_name) {
    global $wpdb;
    
    if ($table_name == $wpdb->posts) {
      $query = "SELECT table_name AS `table`, round(((data_length + index_length) / 1024 / 1024), 2) AS `size`, ";
      $query .= "(SELECT COUNT(*) FROM `$table_name` WHERE post_type != 'revision') AS `rows` ";
      $query .= "FROM information_schema.TABLES ";
      $query .= "WHERE table_schema = %s AND table_name = %s";
    } elseif ($table_name == $wpdb->postmeta) {
      $query = "SELECT table_name AS `table`, round(((data_length + index_length) / 1024 / 1024), 2) AS `size`, ";
      $query .= "(SELECT COUNT(*) FROM `$table_name` WHERE post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type != 'revision')) AS `rows` ";
      $query .= "FROM information_schema.TABLES ";
      $query .= "WHERE table_schema = %s AND table_name = %s";
    }

    return $query;
  }, 10, 2);


  /**
   * Filter to get the records within posts and postmeta tables excluding post revisions
   * 
   * @param string $query Query to get the records
   * @param string $table_name Name of the table
   * @param int $offset Offset for the query
   * @param int $limit Limit for the query
   * @return string $query Updated prepared query to get the records
   */
  add_filter('bmip_smart_exclusion_post_revisions', function ($query, $table_name, $offset, $limit) {
    global $wpdb;

    if ($table_name == $wpdb->posts) {
      $query = $wpdb->prepare("SELECT * FROM `$table_name` WHERE post_type != 'revision' LIMIT %d, %d", $offset, $limit);
    } elseif ($table_name == $wpdb->postmeta) {
      // $query = $wpdb->prepare("SELECT * FROM `%s` WHERE post_id IN (SELECT ID FROM `%s` WHERE post_type != 'revision') LIMIT %d, %d", $table_name, $wpdb->posts, $offset, $limit);
      $query = $wpdb->prepare("SELECT * FROM `$table_name` WHERE post_id IN (SELECT ID FROM `$wpdb->posts` WHERE post_type != 'revision') LIMIT %d, %d", $offset, $limit);
    }

    return $query;
  }, 10, 4);

  require_once BMI_PRO_INC . 'cron/bootstrap.php';
  \BMI\Plugin\CRON\TaskManager::boot();

