<?php

namespace BMI\Plugin\External\Stream;

/**
 * Supported cloud storage providers.
 * 
 */
class BMIZipStreamProvider
{
    public const BACKUPBLISS = 'backupbliss';
    public const GDRIVE = 'gdrive';
    public const DROPBOX = 'dropbox';
    public const S3 = 's3';
    public const WASABI = 'wasabi';
    public const FTP = 'ftp';
    public const SFTP = 'sftp';
    public const ONEDRIVE = 'onedrive';
    public const LOCAL = 'local';
    public const PCLOUD = 'pcloud';

    /**
     * Get all supported provider identifiers.
     * 
     * @return string[]
     */
    public static function all(): array
    {
        return [
            // self::LOCAL,
            self::BACKUPBLISS,
            self::GDRIVE,
            self::DROPBOX,
            self::S3,
            self::WASABI,
            self::FTP,
            self::SFTP,
            self::ONEDRIVE,
            self::PCLOUD
        ];
    }

    /**
     * Check if a provider is supported.
     * 
     * @param string $provider Provider identifier
     * @return bool
     */
    public static function isSupported(string $provider): bool
    {
        return in_array($provider, self::all(), true);
    }

    /**
     * Check if a provider is currently implemented.
     * 
     * @param string $provider Provider identifier
     * @return bool
     */
    public static function isImplemented(string $provider): bool
    {
        // Currently only BackupBliss is implemented
        return in_array($provider, [
            self::BACKUPBLISS, 
            // self::LOCAL
        ], true);
    }

    public static function getDefaultProvider(): string
    {
        // Default to BackupBliss if no provider is set
        return self::BACKUPBLISS;
    }

    /**
     * Get human-readable labels for all providers, ordered for display.
     * Uses WordPress translation functions when available.
     * 
     * @return array<string, string> Map of provider identifier => display label
     */
    public static function labels()
    {
        return [
            // self::LOCAL       => __('Local - For Testing Purposes', 'backup-backup'),
            self::BACKUPBLISS => __('BackupBliss', 'backup-backup'),
            self::GDRIVE      => __('Google Drive', 'backup-backup'),
            self::DROPBOX     => __('Dropbox', 'backup-backup'),
            self::ONEDRIVE    => __('OneDrive', 'backup-backup'),
            self::SFTP        => __('SFTP', 'backup-backup'),
            self::FTP         => __('FTP', 'backup-backup'),
            self::S3          => __('Amazon S3', 'backup-backup'),
            self::WASABI      => __('Wasabi', 'backup-backup'),
            self::PCLOUD      => __('pCloud', 'backup-backup'),
        ];
    }
}