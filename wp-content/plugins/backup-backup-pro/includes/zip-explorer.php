<?php


namespace BMI\Plugin;



if (!defined('ABSPATH')) exit;

use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;

interface ZipStrategyInterface
{
    public function getFirstLevel();
    public function explore($path);
    public function scanDir($path, $savepath, $ignore = []);
    public function exploreCore();
    public function exploreOtherContents();
    public function isSubFile($path, $subPath);
    public function close();
}

abstract class AbstractZipLibrary implements ZipStrategyInterface
{
    protected function addFileToContents($path, $filename, $mtime, $size, &$files, &$directories)
    {
        $path = rtrim($path, '/');
        $filename = BMP::fixSlashes($filename);
        if ($this->isSubFile($path, $filename) && $filename !== $path) {
            $filename = str_replace($path . DIRECTORY_SEPARATOR, '', $filename);
            $files[$path . '/' . $filename] = ['s' => $size, 'm' => $mtime];
        } else if (strpos($filename, $path) === 0 && substr($filename, -1) !== '/' && rtrim($filename, '/') !== $path) {
            $subDirName = $this->getSubDirName($path, $filename);
            if ($subDirName != '') {
                if (!isset($directories[$path . '/' . $subDirName])) {
                    $directories[$path . '/' . $subDirName] = ['s' => $size, 'm' => $mtime];
                } else {
                    $directories[$path . '/' . $subDirName]['s'] += $size;
                    if ($directories[$path . '/' . $subDirName]['m'] < $mtime) {
                        $directories[$path . '/' . $subDirName]['m'] = $mtime;
                    }
                }
            }
        }
    }

    public function exploreOtherContents()
    {
        $wpContent = $this->explore('wordpress/wp-content');
        unset($wpContent['directories']['wordpress/wp-content/plugins']);
        unset($wpContent['directories']['wordpress/wp-content/themes']);
        unset($wpContent['directories']['wordpress/wp-content/uploads']);
        return $wpContent;
    }

    public function exploreCore()
    {
        $wordpress =  $this->explore('wordpress');
        unset($wordpress['directories']['wordpress/wp-content']);
        return $wordpress;
    }

    protected function analyzeZipContents($path, $mtime, $size, &$dirs)
    {
        $path = BMP::fixSlashes($path);
        if (strpos($path, 'wordpress/wp-content/plugins/') === 0) {
            if (!isset($dirs['wordpress/wp-content/plugins'])){
                $dirs['wordpress/wp-content/plugins'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['wordpress/wp-content/plugins']['s'] += $size;
                if ($dirs['wordpress/wp-content/plugins']['m'] < $mtime) {
                    $dirs['wordpress/wp-content/plugins']['m'] = $mtime;
                }
            }
        } elseif (strpos($path, 'wordpress/wp-content/themes/') === 0 ) {
            if (!isset($dirs['wordpress/wp-content/themes'])){
                $dirs['wordpress/wp-content/themes'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['wordpress/wp-content/themes']['s'] += $size;
                if ($dirs['wordpress/wp-content/themes']['m'] < $mtime) {
                    $dirs['wordpress/wp-content/themes']['m'] = $mtime;
                }
            }
        } elseif (strpos($path, 'wordpress/wp-content/uploads/') === 0) {
            if (!isset($dirs['wordpress/wp-content/uploads'])){
                $dirs['wordpress/wp-content/uploads'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['wordpress/wp-content/uploads']['s'] += $size;
                if ($dirs['wordpress/wp-content/uploads']['m'] < $mtime) {
                    $dirs['wordpress/wp-content/uploads']['m'] = $mtime;
                }
            }
        } elseif (strpos($path, 'wordpress/wp-content/') === 0 && strpos($path, 'plugins/') === false && strpos($path, 'themes/') === false && strpos($path, 'uploads/') === false ) {
            if (!isset($dirs['every_thing_else'])){
                $dirs['every_thing_else'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['every_thing_else']['s'] += $size;
                if ($dirs['every_thing_else']['m'] < $mtime) {
                    $dirs['every_thing_else']['m'] = $mtime;
                }
            }
        } elseif ((strpos($path, 'wordpress/wp-includes/') === 0 || strpos($path, 'wordpress/wp-admin/') === 0)) {
            if (!isset($dirs['wp_installation'])){
                $dirs['wp_installation'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['wp_installation']['s'] += $size;
                if ($dirs['wp_installation']['m'] < $mtime) {
                    $dirs['wp_installation']['m'] = $mtime;
                }
            }
        } elseif (strpos($path, 'db_tables') === 0) {
            if (!isset($dirs['db_tables'])){
                $dirs['db_tables'] = ['s' => $size, 'm' => $mtime];
            }else{
                $dirs['db_tables']['s'] += $size;
                if ($dirs['db_tables']['m'] < $mtime) {
                    $dirs['db_tables']['m'] = $mtime;
                }
            }
        }
    }

    protected function getSubDirName($path, $subPath) {
        $subPath = rtrim($subPath, '/');
        $path = rtrim($path, '/');
        $subDirName = str_replace($path . DIRECTORY_SEPARATOR, '', $subPath);
        $subDirName = explode(DIRECTORY_SEPARATOR, $subDirName);
        return $subDirName[0];
    }

    public function isSubFile($path, $subPath)
    {
        return dirname($subPath) === $path && $subPath[strlen($subPath) - 1] !== '/'; // Ignore empty directories
    }
}

class ZipArchiveLibrary extends AbstractZipLibrary
{
    private $zip;

    public function __construct($zipFilePath)
    {
        $this->zip = new \ZipArchive();
        if ($this->zip->open($zipFilePath) !== true) {
            throw new \Exception("Failed to open ZIP file");
        }
    }

    public function getFirstLevel()
    {
        $dirs = [];
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file = $this->zip->statIndex($i);
            $this->analyzeZipContents($file['name'], $file['mtime'], $file['size'], $dirs);
        }
        return ['files' => [], 'directories' => $dirs];
    }

    public function explore($path)
    {
        $files = [];
        $directories = [];
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file = $this->zip->statIndex($i);
            $this->addFileToContents($path, $file['name'], $file['mtime'], $file['size'], $files, $directories);
        }
        return ['files' => $files, 'directories' => $directories];
    }

    public function scanDir($path, $savepath, $ignore = [])
    {
        $tmpf = fopen($savepath, 'a+');
        $amount = 0;
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file = $this->zip->statIndex($i);
            if (strpos($file['name'], $path) === 0) {
                $ignoreFile = false;
                foreach ($ignore as $ignorePath) {
                    if (strpos($file['name'], $ignorePath) === 0) {
                        $ignoreFile = true;
                        break;
                    }
                }
                if ($ignoreFile) {
                    continue;
                }
                fwrite($tmpf, $file['name'] . "\n");
                $amount++;
            }
            unset($file);
        }
        fclose($tmpf);
        return $amount;
    }

    public function close()
    {
        $this->zip->close();
    }
}

class PclZipLibrary extends AbstractZipLibrary
{
    private $zip;

    public function __construct($zipFilePath)
    {
        if (!class_exists('PclZip')) {
            if (!defined('PCLZIP_TEMPORARY_DIR')) {
                $bmi_tmp_dir = BMI_TMP;
                if (!file_exists($bmi_tmp_dir)) {
                    @mkdir($bmi_tmp_dir, 0775, true);
                }
                define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . DIRECTORY_SEPARATOR . 'bmi-');
            }
            if (defined('BMI_PRO_PCLZIP') && file_exists(BMI_PRO_PCLZIP)) {
                require_once BMI_PRO_PCLZIP;
            } else {
                require_once trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
            }
        }
        $this->zip = new \PclZip($zipFilePath);
        // Validate the zip by checking if the manifest file exists
        if ($this->zip->extract(PCLZIP_OPT_BY_NAME, 'bmi_backup_manifest.json', PCLZIP_OPT_EXTRACT_AS_STRING) <= 0) {
            throw new \Exception("Failed to open ZIP file");
        }
    }

    public function getFirstLevel()
    {
        $contents = $this->zip->listContent();
        $dirs = [];
        foreach ($contents as $fileInfo) {
            $this->analyzeZipContents($fileInfo['filename'], $fileInfo['mtime'], $fileInfo['size'], $dirs);
        }

        return ['files' => [], 'directories' => $dirs];
    }

    public function explore($path)
    {
        $files = [];
        $directories = [];
        $contents = $this->zip->listContent();
        foreach ($contents as $fileInfo) {
            $this->addFileToContents($path, $fileInfo['filename'], $fileInfo['mtime'], $fileInfo['size'], $files, $directories);
        }
        return ['files' => $files, 'directories' => $directories];
    }

    public function scanDir($path, $savepath, $ignore = [])
    {
        $contents = $this->zip->listContent();
        $tmpf = fopen($savepath, 'a+');
        $amount = 0;
        foreach ($contents as $fileInfo) {
            if (strpos($fileInfo['fileInfo'], $path) === 0) {
                $ignoreFile = false;
                foreach ($ignore as $ignorePath) {
                    if (strpos($fileInfo['fileInfo'], $ignorePath) === 0) {
                        $ignoreFile = true;
                        break;
                    }
                }
                if ($ignoreFile) {
                    continue;
                }

                fwrite($tmpf, $fileInfo['filename'] . "\n");
                $amount++;
            }
        }
        fclose($tmpf);
        return $amount;
    }

    public function close()
    {
        // No need to close PclZip
    }
}

class BMI_Zip_Explorer
{
    private $zipFile;
    private $zipLibrary;

    public function __construct($zipFilePath,$archiveType=null)
    {
        $this->zipFile = $zipFilePath;
        if($archiveType == 'zip'){
            $this->zipLibrary = new ZipArchiveLibrary($this->zipFile);
        }else if ($archiveType == 'pclzip'){
            $this->zipLibrary = new PclZipLibrary($this->zipFile);
        } else if ($archiveType == null) {
            if (class_exists('\ZipArchive')) {
                $this->zipLibrary = new ZipArchiveLibrary($this->zipFile);
            } else {
                $this->zipLibrary = new PclZipLibrary($this->zipFile);
            }
        }

    }

    public function getFirstLevel()
    {
        return $this->zipLibrary->getFirstLevel();
    }

    public function explore($path = null)
    {
        if ($path === null) {
            return $this->zipLibrary->getFirstLevel();
        }

        if (dirname($path) === 'wordpress/wp-content/plugins' || dirname($path) === 'wordpress/wp-content/themes') {
            throw new \Exception(__("No further detailing is possible, as you cannot restore parts of a plugin or theme.", 'backup-backup'));
        }

        if ($path === 'every_thing_else') {
            return $this->zipLibrary->exploreOtherContents();
        }

        if ($path === 'wp_installation') {
            return $this->zipLibrary->exploreCore();
        }


        return $this->zipLibrary->explore($path);
    }

    public function scanDir($path, $savepath)
    {
        if ($path == 'wp_installation') {
            $path = 'wordpress';
            return $this->zipLibrary->scanDir($path, $savepath, ['wordpress/wp-content']);
        }else if ($path == 'every_thing_else') {
            $path = 'wordpress/wp-content';
            return $this->zipLibrary->scanDir($path, $savepath, ['wordpress/wp-content/plugins', 'wordpress/wp-content/themes', 'wordpress/wp-content/uploads']);
        }else{
            return $this->zipLibrary->scanDir($path, $savepath);
        }
        
    }

    public function __destruct()
    {
        $this->zipLibrary->close();
    }
}