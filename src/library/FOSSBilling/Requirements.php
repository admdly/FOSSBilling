<?php declare(strict_types=1);
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace FOSSBilling;

use Pimple\Container;
use Symfony\Component\Filesystem\Path;

class Requirements implements InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public function setDi(Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Container
    {
        return $this->di;
    }

    private bool $_all_ok = true;
    private array $_options = [];

    public function __construct()
    {
        $this->_options = [
            'php' => [
                'extensions' => [
                    'pdo_mysql',
                    'zlib',
                    'openssl',
                    'dom',
                    'xml',
                ],
                'version' => PHP_VERSION,
                'min_version' => '8.0',
                'safe_mode' => ini_get('safe_mode'),
            ],
            'writable_folders' => [
                Path::normalize(PATH_CACHE),
                Path::normalize(PATH_LOG),
                Path::normalize(PATH_UPLOADS),
            ],
            'writable_files' => [
                Path::normalize(PATH_CONFIG),
            ],
        ];
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function getInfo(): array
    {
        $pathCache = Path::normalize(PATH_CACHE);
        $pathData = Path::normalize(PATH_DATA);
        $pathLog = Path:: normalize(PATH_LOG);
        $pathUploads = Path::normalize(PATH_UPLOADS);

        $data = [
            'ip' => $_SERVER['SERVER_ADDR'] ?? null,
            'PHP_OS' => PHP_OS,
            'PHP_VERSION' => PHP_VERSION,
            'FOSSBilling' => [
                'BB_LOCALE' => $this->di['config']['i18n']['locale'],
                'version' => Version::VERSION,
            ],
            'ini' => [
                'allow_url_fopen' => ini_get('allow_url_fopen'),
                'safe_mode' => ini_get('safe_mode'),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'permissions' => [
                $pathUploads => substr(sprintf('%o', fileperms($pathUploads)), -4),
                $pathData => substr(sprintf('%o', fileperms($pathData)), -4),
                $pathCache => substr(sprintf('%o', fileperms($pathCache)), -4),
                $pathLog => substr(sprintf('%o', fileperms($pathLog)), -4),
            ],
            'extensions' => [
                'apc' => extension_loaded('apc'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'zlib' => extension_loaded('zlib'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
            ],
        ];

        //determine php username
        if(function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $data['posix_getpwuid'] = posix_getpwuid(posix_geteuid());
        }

        return $data;
    }

    public function isPhpVersionOk(): bool
    {
        $current = $this->_options['php']['version'];
        $required = $this->_options['php']['min_version'];
        return version_compare($current, $required, '>=');
    }

    public function isFOSSBillingVersionOk(): bool
    {
        return Version::VERSION !== '0.0.1';
    }

    /**
     * What extensions must be loaded for FOSSBilling to function correctly
     */
    public function extensions(): array
    {
        $exts = $this->_options['php']['extensions'];

        $result = [];
        foreach($exts as $ext) {
            if(extension_loaded($ext)) {
                $result[$ext] = true;
            } else {
                $result[$ext] = false;
                $this->_all_ok = false;
            }
        }

        return $result;
    }

    /**
     * Files that must be writable
     */
    public function files(): array
    {
        $files = $this->_options['writable_files'];
        $result = [];

        foreach($files as $file) {
            clearstatcache();
            if ('0777' == substr(sprintf('%o', @fileperms($file)), -4)) {
                $result[$file] = true;
            } elseif (is_writable($file)) {
            	$result[$file] = true;
            } else if (!file_exists($file)){
                $written = @file_put_contents($file, 'Test?');
                if($written){
                    $result[$file] = true;
                } else {
                    $result[$file] = false;
                    $this->_all_ok = false;
                }
                @unlink($file);
            } else {
                $result[$file] = false;
                $this->_all_ok = false;
            }
        }

        return $result;
    }

    /**
     * Folders that must be writable
     */
    public function folders(): array
    {
        $folders = $this->_options['writable_folders'];

        $result = [];
        foreach($folders as $folder) {
            clearstatcache();
            if ('0777' == substr(sprintf('%o', @fileperms($folder)), -4)) {
                $result[$folder] = true;
            } elseif (is_writable($folder)) {
            	$result[$folder] = true;
            } else {
                $result[$folder] = false;
                $this->_all_ok = false;
            }
        }

        return $result;
    }

    /**
     * Check if we can continue with installation
     * @return bool
     */
    public function canInstall(): bool
    {
        $this->extensions();
        $this->folders();
        $this->files();
        return $this->_all_ok;
    }

    /**
     * Check permissions
     * @return bool
     */
    public function checkPerms(string $path, string $perm = '0777'): bool
    {
        clearstatcache();
        $configmod = substr(sprintf('%o', @fileperms($path)), -4);
        return ($configmod == $perm);
    }
}
