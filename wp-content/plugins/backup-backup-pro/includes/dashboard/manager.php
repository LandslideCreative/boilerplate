<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Use
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\Zipper\Zip as Zip;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * BMI_Dashboard_Pro
   */
  class BMI_Dashboard_Pro {

    /**
     * __construct - Initialize all hooks
     *
     * @return void
     */
    public function __construct() {

      require_once BMI_PRO_INC . 'actions.php';
      add_action('bmi_pro_db_exclude_tables', [&$this, 'exclude_tables_from_backup']);
      add_action('bmi_pro_files_exclude', [&$this, 'exclude_files_from_backup']);
      add_action('bmi_pro_backup_triggers', [&$this, 'bmi_pro_backup_triggers']);

    }

    private function get_bmi_free_asset($base, $asset) {

      return BMI_ASSETS . '/' . $base . '/' . $asset;

    }

    public function check_for_labels_db_table($prefix, $tablename, $size, $rows) {

      $largeSize = 'bmi_large_size_table';
      $coreTable = 'bmi_core_table';
      $partOfWPTable = 'bmi_plugin_part_table';

      $labels = [];
      $core = ['commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'termmeta', 'terms', 'usermeta', 'users'];

      for ($i = 0; $i < sizeof($core); ++$i) {
        $core[$i] = $prefix . $core[$i];
      }

      if (floatval($size) > 1.900) $labels[] = $largeSize;
      else if (intval($rows) > 3999) $labels[] = $largeSize;

      if (in_array($tablename, $core)) {
        $labels[] = $coreTable;
      } else if (substr($tablename, 0, strlen($prefix)) == $prefix) {
        $labels[] = $partOfWPTable;
      }

      $html = ' class="' . implode(' ', $labels) . '"';

      return $html;

    }

    public function exclude_tables_from_backup() {

      ?>
      <div class="flex file-checkboxes">
        <div class="premium-function">
          <label class="autowidth premium-img">
            <input id="bmi-pro-db-tables-exclusion"<?php bmi_cb_collapsible('bmi_db_tables_exclusion_list'); ?> type="checkbox"<?php bmi_try_checked('BACKUP:DATABASE:EXCLUDE'); ?>>
            <span>
              <span class="regular"><?php _e("Exclude specific tables from backups", 'backup-backup'); ?></span>
            </span>
          </label>
        </div>
      </div>

      <div id="bmi_db_tables_exclusion_list">
        <div class="mm lh14 f16">
          <div class="bmi_pro_tables_container">
            <div class="bmi_pro_tables_header">
              <?php
                $excludedTables = '<span id="bmi_currently_excluded_tables">0</span>';
                $allTables = '<span id="bmi_all_tables_count">0</span>';
                $totalSize = '<span id="bmi_total_size_excluded">0/0</span>';
              ?>
              <?php _e("Currently you excluded", 'backup-backup'); echo '&nbsp' . $excludedTables . '&nbsp;'; echo str_replace('%s', $allTables, __("out of %s tables.", 'backup-backup')); ?>
              <?php echo str_replace('%s', $totalSize, __("In total you will save %s MB.", 'backup-backup')); ?>
            </div>
            <div class="bmi_pro_tables_display">
              <?php
                global $wpdb;
                $prefix = $wpdb->prefix;

                $sql = "SELECT TABLE_NAME AS `table`, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 3) AS `size`, TABLE_ROWS AS `rows`, (DATA_LENGTH + INDEX_LENGTH) AS `bytes` ";
                $sql .= "FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s;";

                $sql = $wpdb->prepare($sql, array(DB_NAME));

                $tables = $wpdb->get_results($sql);
                $i = 1;

                $rowsTxt = __('rows', 'backup-backup');
                $excludedTables = Dashboard\bmi_get_config('BACKUP:DATABASE:EXCLUDE:LIST');

                if (!is_array($excludedTables) || empty($excludedTables)) $excludedTables = [];

                foreach ($tables as $row) {
                  $labels = $this->check_for_labels_db_table($prefix, $row->table, $row->size, $row->rows);
                  $isCore = (strpos($labels, "bmi_core_table") !== false) ? "*" : "";
                  $html = '<label' . $labels . '>';
                  $html .= '<input type="checkbox" value="' . $row->table . '" data-order="' . $i . '" data-size="' . $row->bytes . '"' . (in_array($row->table, $excludedTables) ? ' checked' : '') . '>';
                  $html .= '&nbsp;<span class="bmi_in_table_name">' . $row->table . $isCore . '</span> <span class="bmi_in_table_size">(' . $row->rows . ' ' . $rowsTxt . ', ' . $row->size . ' MB)</span>';
                  $html .= '</label>';

                  echo $html;
                  $i++;
                }
              ?>
            </div>
          </div>
          <div class="">
            <span style="color: #d0302d;"><?php _e("Red tables", 'backup-backup'); ?></span> <?php _e("marked with asterisk* are part of WordPress core, exclude them only if youre 100% sure what you are doing.", 'backup-backup'); ?><br>
            <span style="color: #3470e6;"><?php _e("Blue tables", 'backup-backup'); ?></span> <?php _e("are related to your WordPress instance, most of them are related to your plugins.", 'backup-backup'); ?><br>
            <b><?php _e("Black tables", 'backup-backup'); ?></b> <span><?php _e("are recommended for exclusion.", 'backup-backup'); ?></span><br>
            <span><?php _e("Hint: Hold shift+click to select many tables at once.", 'backup-backup'); ?></span>
          </div>
        </div>
      </div>
      <?php

    }

    public function exclude_files_from_backup() {


      ?>      
      <div id="bmi-file-explorer" style="display:none;">

        <div class="mm lh14 f16">
          <div class="bmi_pro_tables_container">
            <div class="bmi_pro_tables_header center f18 bold">
              <span class="plugins-header" style="display:none;"><?php _e("Explore Plugin Files", 'backup-backup'); ?></span>
              <span class="uploads-header" style="display:none;"><?php _e("Explore Uploads Files", 'backup-backup'); ?></span>
              <span class="themes-header" style="display:none;"><?php _e("Explore Themes Files", 'backup-backup'); ?></span>
              <span class="other-contents-header" style="display:none;"><?php _e("Explore Other Contents Files", 'backup-backup'); ?></span>
              <span class="wp-install-header" style="display:none;"><?php _e("Explore WP Installation Files", 'backup-backup'); ?></span>
            </div>
            <div class="bmi_pro_tables_display">
            </div>
          </div>
          <div>
            <ul id="bmi_file_explorer_instructions">
              <li><?php _e("- Select the items you wish to exclude from the backup.", 'backup-backup'); ?></li>
              <li><?php _e("- Your selections will only apply if the options 'Exclude by Directory Path' and 'Exclude by File Path' are kept <b>turned ON</b> in the settings below.", 'backup-backup'); ?></li>
              <li><?php _e("- Ensure you <b>Save changes</b> before switching to a different section.", 'backup-backup'); ?></li>
              <li class="double-click-instructions"><?php _e("- Double-click a directory to browse its contents.", 'backup-backup'); ?></li>
              <li><?php _e("- Currently displaying the 100 largest files and directories.", 'backup-backup'); ?></li>
            </ul>
          </div>
        </div>
      </div>
      <?php

      
    }

    public function bmi_pro_backup_triggers() {
      ?>
        <div class="mm mbl">
        <div class="mm mm-border">
          <div>
            <div class="cf">
              <div class="left">
                <div class="f20 bold mr20 premium-function">
                  <?php _e("Before updates", 'backup-backup'); ?>
                  <span class="premium premium-img premium-ntt"></span>
                </div>
              </div>
              <div class="left">
                <label for="before-updates-switch" class="bmi-switch">
                  <input type="checkbox" id="before-updates-switch" <?php bmi_try_checked('OTHER:TRIGGER:BEFORE:UPDATES', true); ?>> 
                  <div class="bmi-switch-slider round">
                    <span class="on"><?php _e("On", 'backup-backup'); ?></span>
                    <span class="off"><?php _e("Off", 'backup-backup'); ?></span>
                  </div>
                </label>
              </div>
            </div>
          </div>
          
          <div>
            <div class="mtll f16">
              <?php _e("When enabled, this feature will create a <b>partial</b> backup before the automatic updates of WordPress core, plugins, or themes on your website. <b>Only</b> the item(s) about to get updated will be backed up.", 'backup-backup'); ?>
            </div>
          </div>
          
          <table>
            <tbody>
              <tr>
                <td style="vertical-align: top;">
                  <div class="f20 bold mw250 lh65 premium-function" >
                    <?php _e("Trigger by URI", 'backup-backup'); ?>
                    <span class="premium premium-img premium-ntt"></span>
                  </div>
                </td>
                <td>
                  <div class="">
                    <div class="cf">
                      <div class="left mr20">
                        <?php 
                          $key = Dashboard\bmi_get_config('BACKUP:URL:TRIGGER:KEY');
                          if (empty($key)) {
                            $key = wp_generate_password(16, false);
                            Dashboard\bmi_set_config('BACKUP:URL:TRIGGER:KEY', $key);
                          }
                          $url = get_home_url(null, sprintf('/?backup-migration=BACKUP_BY_URL_REQUEST&_wpnonce=%s&sk=%s&uk=%s', 'AIo32MMfe', Dashboard\bmi_get_config('REQUEST:SECRET'), $key));
                        ?>
                        <input type="text" autocomplete="off" class="bmi-text-input backup-url-trigger small" readonly id="backup-url-trigger" value="<?php esc_attr_e($url); ?>">
                      </div>
                      <div class="left">
                        <a href="#" class="btn inline btn-with-img btn-img-low-pad btn-pad left bmi-copper othersec mm30" data-copy="backup-url-trigger">
                          <div class="text">
                          <img src="<?php echo $this->get_bmi_free_asset('images', 'copy-icon.png'); ?>" alt="copy-img">
                            <div class="f18 semibold"><?php _e('Copy', 'backup-backup') ?></div>
                          </div>
                        </a>
                      </div>
                    </div>
                    <div class="f16 mtlll">
                      <?php _e("Copy and paste this URL into a browser and press Enter to trigger the backup creation.", 'backup-backup'); ?><br>
                      <?php _e("Make sure to keep this URL secret. For safety reasons, this trigger works only once per hour, and you will be emailed when it is used.", 'backup-backup'); ?>
                    </div>
                    <div class="mtll cf">
                      <div class="left lh60 mr20"><?php _e("Key:", 'backup-backup'); ?></div>
                      <div class="left mr20">
                        <input type="text" autocomplete="off" class="bmi-text-input small uk" value="<?php esc_attr_e($key); ?>">
                      </div>
                      <div class="left">
                        <a class="btn mm30 othersec save-new-uk"><?php _e("Save", 'backup-backup'); ?></a>
                      </div>
                    </div>
                    <div class="f16 mtlll">
                      <?php _e("Change the Key (which is part of the URL above) if you suspect that an unauthorized person got access to it.", 'backup-backup'); ?>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    
      <?php
    }

    

  }
