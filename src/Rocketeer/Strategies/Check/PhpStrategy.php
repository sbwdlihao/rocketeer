<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Rocketeer\Strategies\Check;

use Illuminate\Container\Container;
use Rocketeer\Abstracts\Strategies\AbstractCheckStrategy;
use Rocketeer\Interfaces\Strategies\CheckStrategyInterface;

class PhpStrategy extends AbstractCheckStrategy implements CheckStrategyInterface
{
    /**
     * @type string
     */
    protected $description = 'Checks if the server is ready to receive a PHP application';

    /**
     * The language of the strategy.
     *
     * @type string
     */
    protected $language = 'PHP';

    /**
     * The PHP extensions loaded on server.
     *
     * @type array
     */
    protected $extensions = [];

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app     = $app;
        $this->manager = $this->binary('composer');
    }

    //////////////////////////////////////////////////////////////////////
    /////////////////////////////// CHECKS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Get the version constraint which should be checked against.
     *
     * @param string $manifest
     *
     * @return string
     */
    protected function getLanguageConstraint($manifest)
    {
        return $this->getLanguageConstraintFromJson($manifest, 'require.php');
    }

    /**
     * Get the current version in use.
     *
     * @return string
     */
    protected function getCurrentVersion()
    {
        return $this->php()->runLast('version');
    }

    /**
     * Check for the required extensions.
     *
     * @return array
     */
    public function extensions()
    {
        $extensions = $this->app['rocketeer.rocketeer']->getOption('php.extensions');
        if (empty($extensions)) {
            return [];
        }

        // Check PHP extensions
        $errors = [];
        foreach ($extensions as $method=>$extension) {
            if (empty($extension)) {
                continue;
            }
            if (!method_exists($this, $method)) {
                $errors = array_merge($errors, $extension);
            }
            $r = $this->$method($extension);
            if (is_array($r)) {
                $errors = array_merge($errors, $r);
            } else if ($r === FALSE) {
                $errors[] = $extension;
            }
        }

        return $errors;
    }

    /**
     * Check for the required drivers.
     *
     * @return array
     */
    public function drivers()
    {
        return [];
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// HELPERS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Check the presence of the correct database PHP extension.
     *
     * @param string $database
     *
     * @return bool
     */
    public function checkDatabaseDriver($database)
    {
        switch ($database) {
            case 'sqlite':
                return $this->checkPhpExtension('pdo_sqlite');

            case 'mysql':
                return $this->checkPhpExtension('mysql') && $this->checkPhpExtension('pdo_mysql');

            default:
                return true;
        }
    }

    /**
     * Check the presence of the correct cache PHP extension.
     *
     * @param string $cache
     *
     * @return bool|string
     */
    public function checkCacheDriver($cache)
    {
        switch ($cache) {
            case 'memcached':
            case 'apc':
                return $this->checkPhpExtension($cache);

            case 'redis':
                return $this->which('redis-server');

            default:
                return true;
        }
    }

    /**
     * Check the presence of a PHP extension.
     *
     * @param string $extension The extension
     *
     * @return bool
     */
    public function checkPhpExtension($extension)
    {
        $error = [];
        if (is_string($extension)) {
            $extension = (array)$extension;
        }
        // Check for HHVM and built-in extensions
        if ($this->php()->isHhvm()) {
            $this->extensions = [
                '_hhvm',
                'apache',
                'asio',
                'bcmath',
                'bz2',
                'ctype',
                'curl',
                'debugger',
                'fileinfo',
                'filter',
                'gd',
                'hash',
                'hh',
                'iconv',
                'icu',
                'imagick',
                'imap',
                'json',
                'mailparse',
                'mcrypt',
                'memcache',
                'memcached',
                'mysql',
                'odbc',
                'openssl',
                'pcre',
                'phar',
                'reflection',
                'session',
                'soap',
                'std',
                'stream',
                'thrift',
                'url',
                'wddx',
                'xdebug',
                'zip',
                'zlib',
            ];
        }

        // Get the PHP extensions available
        if (!$this->extensions) {
            $this->extensions = (array) $this->bash->run($this->php()->extensions(), false, true);
        }

        foreach ($extension as $val) {
            if (!in_array($val, $this->extensions, true)) {
                $error[] = $val;
            }
        }
        return $error;
    }

    public function checkService($serviceName) {
        $error = [];
        if (is_string($serviceName)) {
            $serviceName = (array)$serviceName;
        }
        foreach($serviceName as $val) {
            if (!empty($val) && !$this->rawWhich($val)) {
                $error[] = $val;
            }
        }
        return $error;
    }
}
