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

class RemoteCopySyncStrategy extends SyncStrategy
{
    /**
     * @type string
     */
    protected $description = 'First copy remote current release and then uses rsync to create or update a release from the local files';

    /**
     * @type int
     */
    protected $port;

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

        // Copy current folder
        $previous = $this->releasesManager->getPathToRelease($this->releasesManager->getPreviousRelease());
        $previous = rtrim($previous, '/').'/**';
        $this->copy($previous, $destination);

        return $this->rsyncTo($destination, $source, $this->exclude, true);
    }
}
