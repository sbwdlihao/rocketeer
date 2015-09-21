<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Rocketeer\Strategies\Deploy;

use Illuminate\Container\Container;
use Rocketeer\Abstracts\Strategies\AbstractStrategy;
use Rocketeer\Interfaces\Strategies\DeployStrategyInterface;
use Rocketeer\Traits\Rsync;

class SyncStrategy extends AbstractStrategy implements DeployStrategyInterface
{
    use Rsync;

    /**
     * @type string
     */
    protected $description = 'Uses rsync to create or update a release from the local files';

    /**
     * @type int
     */
    protected $port;

    /**
     * @type array
     */
    protected $exclude;

    public function __construct(Container $app) {
        parent::__construct($app);

        $this->exclude = $this->app['rocketeer.rocketeer']->getOption('sync_exclude');
    }

    /**
     * Deploy a new clean copy of the application.
     *
     * @param string|null $destination
     * @param string|null $source
     *
     * @return bool
     */
    public function deploy($destination = null, $source = null)
    {
        if (!$destination) {
            $destination = $this->releasesManager->getCurrentReleasePath();
        }

        if (!$source) {
            $source = $this->app['rocketeer.rocketeer']->getOption('sync_source');
        }

        // Create receiveing folder
        $this->createFolder($destination, true);

        return $this->rsyncTo($destination, $source, $this->exclude, true);
    }

    /**
     * Update the latest version of the application.
     *
     * @param bool $reset
     *
     * @return bool
     */
    public function update($reset = true)
    {
        $release = $this->releasesManager->getCurrentReleasePath();

        return $this->rsyncTo($release, './', $this->exclude);
    }


}
