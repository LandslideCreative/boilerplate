<?php

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  // Script to be inserted when plugin failed to load
  add_action('in_admin_footer', function () {
    ?>
    <script type='text/javascript'>
      jQuery(document).ready(function($) {
        $('#bmi-pro-quick-action').on('click', function () {

          $('#bmi-install-p').hide(300);
          $('#bmi-ongoing-p').show(300);

          let o = $(this).attr('data-action');
          $.post(ajaxurl, { action: 'bmi_pro_plugin_install', nonce: '<?php echo wp_create_nonce('backup-migration-pro-ajax'); ?>', type: o }).done((res) => {

            if (typeof res.status === 'undefined') {
              res = JSON.parse(res.slice(res.indexOf('{')));
            }

            $('#bmi-ongoing-p').hide(300);
            if (res.status === 'success') window.location.reload();
            else {
              $('#bmi-ongoing-p').text('Plugin failed to install, please try again or do it manually.');
              setTimeout(function () { window.location = res.url; }, 2000);
            }
            $('#bmi-ongoing-p').show(300);

          }).fail(() => {
            $('#bmi-ongoing-p').hide(300);
            setTimeout(function () {
              $('#bmi-ongoing-p').text('We could not install the plugin, please check your internet connection and server status.');
            }, 310);
          });

        });
      });
    </script>
    <?php
  });
