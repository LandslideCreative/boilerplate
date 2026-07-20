<?php

// Namespace
namespace BMI\Plugin\Dashboard;

// use SFTP
use BMI\Plugin\External\BMI_External_SFTP as SFTP;
// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('bmi_pro_sftp_template', function () {

    $username = get_option('bmip_sftp_username');
    $fingerPrint = get_option('bmip_sftp_fingerprint');
    $authType = get_option('bmip_sftp_authType', 'password');
    $isEnabled = bmi_get_config('STORAGE::EXTERNAL::SFTP');
    $privateKeyInstructions = sprintf(
        __('To generate an OpenSSH key from a common %sPuTTY generator%s, use the feature <b>Conversions > Export OpenSSH key.</b><br>
        From the generated key file, copy and paste the content between these lines:<br>
        -----BEGIN RSA PRIVATE KEY-----<br>
        and<br>
        -----END RSA PRIVATE KEY-----', 'backup-backup'),
         '<a href="https://www.youtube.com/watch?v=1wQ8wQfa7lw" target="_blank">', 
         '</a>');

    require_once BMI_PRO_INC . 'external/sftp.php';


    if ($isEnabled === true || $isEnabled === 'true') {
        $isEnabled = ' checked';
    } else {
        $isEnabled = '';
    }

    ?>
    <!-- External: SFTP -->
    <div class="tab2-item d-flex jst-sb ia-center<?php echo (($isEnabled == ' checked') ? ' activeList' : ''); ?>">

        <div class="d-flex ia-center">
            <img src="<?php echo $this->get_asset('images', 'sftp-scp.svg') ?>" alt="logo" class="tab2-img"> <span
                class="ml25 title_whereStored">SFTP</span>
            <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="logo" class="crown2">
        </div>

        <div class="ia-center">
            <div class="b2 bmi-switch"><input type="checkbox" class="checkbox storage-checkbox" <?php echo $isEnabled; ?>
                    data-toggle="storage-sftp-row" id="bmi-pro-storage-sftp-toggle">
                <div class="bmi-knobs"><span></span></div>
                <div class="bmi-layer_str"></div>
            </div>
        </div>

    </div>

    <div class="bg_grey storage_target" id="storage-sftp-row" <?php echo (($isEnabled == ' checked') ? '' : ' style="display: none;"'); ?>>
        <div class="container-40 lh30 pt30">

            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Backup directory path:</span></div>

                <div class="w100">
                    <div class="w100 pos-r">
                        <input id="bmip-sftp-backup-dir" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field(get_option('bmip_sftp_remote_path')); ?>"
                            placeholder="Directory_Name_Of_My_Backups_In_FTP">
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Host IP Address:</span></div>
                <div>
                    <div class="pos-r" style="width: 20rem;">
                        <input id="bmip-sftp-host" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field(get_option('bmip_sftp_host')); ?>"
                            placeholder="192.168.100.100" required>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Host Port:</span></div>
                <div>
                    <div class="pos-r" style="width: 20rem;">
                        <input id="bmip-sftp-host-port" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo sanitize_text_field(get_option('bmip_sftp_port')) ?? '22'; ?>"
                            placeholder="22" required>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>User Name:</span></div>
                <div>
                    <div class="pos-r" style="width: 20rem;">
                        <input id="bmip-sftp-username" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo $username; ?>" placeholder="User Name" required>
                    </div>
                    <div class="mt10">
                    </div>
                </div>
            </div>

            <div class="sftp-authentication-container">
                <div class="d-flex" style="position: relative;bottom: 3px;">
                    <div class="w270" style="margin-top: 23px;margin-bottom:23px;width: 30%">
                        <span>
                            <?php _e("Choose Your Authentication Method", 'backup-backup'); ?>
                        </span>
                    </div>
                    <div>
                        <div class="w100">
                            <div class="d-flex mr60 ia-center auth-type-radio-container" style="margin-top: 23px;">
                                <label class="container-radio">
                                    <?php _e("Password Protection", 'backup-backup'); ?>
                                    <input type="radio" name="authType" class="password-authentication-radio" value="password" <?php echo ($authType === 'password') ? 'checked' : ''; ?>>
                                    <span class="checkmark-radio"></span>
                                </label>

                                <label class="container-radio ml25">
                                    <?php _e("Key", 'backup-backup'); ?>
                                    <input type="radio" name="authType" class="key-authentication-radio" value="key" <?php echo ($authType === 'key') ? 'checked' : ''; ?>>
                                    <span class="checkmark-radio"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>






                <div class="d-flex" id="password-authentication" <?php echo ($authType === 'password') ? '' : 'style="display: none;"'; ?>>
                    <div class="w270" style="margin-top: 23px;"><span>Password:</span></div>
                    <div>
                        <div class="pos-r" style="width: 20rem;">
                            <!-- bmip-sftp-password related to js -->
                            <input id="bmip-sftp-password" name="bmip_sftp_password" class="input-ftpdrive_storage"
                                type="password" autocomplete="off" placeholder="Password" required>
                        </div>
                    </div>
                </div>

                <div id="key-authentication" <?php echo ($authType === 'key') ? '' : 'style="display: none;"'; ?>>
                    <div class="d-flex">
                        <div class="w270" style="margin-top: 23px;"><span>Private Key:</span></div>
                        <div>
                            <div class="pos-r" style="width: 20rem;">
                                <textarea id="bmip-sftp-key" name="bmip_sftp_key" class="input-ftpdrive_storage"
                                    type="password" autocomplete="off" placeholder="Private Key" required style="height:15rem;"></textarea>
                            </div>
                            <div class="f16">
                                <span>Enter the contents of your OpenSSH private key file.</span>
                                <span class="bmi-info-icon premium-wrapper" tooltip="<?php echo esc_attr($privateKeyInstructions); ?>" style="cursor: pointer;top:1px;height:14px;width:14px;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="w270" style="margin-top: 23px;"><span>Passphrase:</span></div>
                        <div>
                            <div class="pos-r" style="width: 20rem;">
                                <!-- bmip-sftp-passphrase related to js -->
                                <input id="bmip-sftp-passphrase" name="bmip_sftp_passphrase" class="input-ftpdrive_storage"
                                    type="password" autocomplete="off" placeholder="Passphrase">
                            </div>
                            <div class="f16">
                                <span>If applicable, enter the passphrase for your encrypted private key.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <div class="w270" style="margin-top: 23px;"><span>Fingerprint:</span>
                </div>
                <div>
                    <div class="pos-r" style="width: 20rem;">
                        <!-- bmip-sftp-fingerprint related to js -->
                        <input id="bmip-sftp-fingerprint" class="input-ftpdrive_storage" type="text" autocomplete="off"
                            value="<?php echo $fingerPrint; ?>"
                            placeholder="eb:b1:4a:84:71:6e:03:56:61:.........ed:d1:cd:83">
                    </div>
                    <div class="f16">
                        <span>
                            <?php _e('Optional but recommended: Enter the SFTP server\'s fingerprint to help prevent man-in-the-middle attacks. Supports MD5 and SHA256 algorithms.', 'backup-backup'); ?>
                        </span> <span class="bmi-info-icon premium-wrapper" style="cursor: pointer;"
                         tooltip="<?php echo esc_attr(__('You can generate the fingerprint using the following command: <code>ssh-keyscan &lt;server_ip&gt; | ssh-keygen -lf -</code>. Contact your SFTP server administrator if unsure.', 'backup-backup')); ?>"></span>
                    </div>
                </div>
            </div>
        </div>

        <div id="sftp-unauthenticated-box" class="container-40 lh30 pt30 pb30">

            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;"><span id="sftpdrive-not-authed-content-box" class="external-storage-not-authed-content">Current
                        status:&nbsp;<b>Inactive</b></span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="sftp-connect-btn" class="btn external-storage-btn-connection"><?php _e("Connect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <blockquote class="bmi-ftpdrive-info">
                    SFTP external storage allows for the automated transfer of backup files to a remote SFTP server,
                    providing enhanced security encrypting both data transfer and authentication. This ensures your backups
                    are safely stored off-site.
                </blockquote>
            </div>
        </div>

        <div id="sftp-authenticated-box" class="container-40 lh30 pt30 pb30" style="display: none;">

            <div class="d-flex">
                <div class="w270" style="margin-top: 11px;"><span id="sftpdrive-authed-content-box" class="external-storage-authed-content">Current
                        status:&nbsp;<b>Active</b></span>
                </div>
                <div>
                    <div class="w100 pos-r">
                        <a href="#" id="sftp-disconnect-btn" class="btn external-storage-btn-connection"><?php _e("Disconnect", 'backup-backup'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

});