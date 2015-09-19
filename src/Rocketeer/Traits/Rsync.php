<?php

namespace Rocketeer\Traits;

use Illuminate\Support\Arr;
use Rocketeer\Traits\BashModules\Binaries;
use Rocketeer\Bash;

/**
 *
 * @property \Rocketeer\Bash                                     bash
 * @property \Rocketeer\Services\Connections\ConnectionsHandler  connections
 *
 */
trait Rsync
{
    use Binaries;
    use HasLocator;

    /**
     * Rsyncs the local folder to a remote one.
     *
     * @param string $destination
     * @param string $source
     * @param array $exclude
     * @param bool $isUpdate
     *
     * @return bool
     */
    public function rsyncTo($destination, $source = './', $exclude = null, $isUpdate = false)
    {
        // Build host handle
        $arguments = [];
        $handle    = $this->getSyncHandle();

        // Create options
        $options = ['--verbose' => null, '--recursive' => null, '--rsh' => 'ssh', '--compress' => null];
        if ($isUpdate) {
            $options['--update'] = null;
        }

        // Create SSH command
        $options['--rsh'] = $this->getTransport();

        // Build arguments
        $arguments[] = $source;
        $arguments[] = $handle.':'.$destination;

        // Set excluded files and folders
        if (!empty($exclude)) {
            $options['--exclude'] = $exclude;
        }

        // Create binary and command
        $rsync   = $this->binary('rsync');
        $command = $rsync->getCommand(null, $arguments, $options);

        return $this->bash->onLocal(function (Bash $bash) use ($command) {
            return $bash->run($command);
        });
    }

    /**
     * Get the handle to connect with.
     *
     * @return string
     */
    protected function getSyncHandle()
    {
        $credentials    = $this->connections->getServerCredentials();
        $handle         = array_get($credentials, 'host');
        $explodedHandle = explode(':', $handle);

        // Extract port
        if (count($explodedHandle) === 2) {
            $this->port = $explodedHandle[1];
            $handle     = $explodedHandle[0];
        }

        // Add username
        if ($user = array_get($credentials, 'username')) {
            $handle = $user.'@'.$handle;
        }

        return $handle;
    }

    /**
     * @return string
     */
    protected function getTransport()
    {
        $ssh = 'ssh';

        // Get port
        if ($port = $this->getOption('port', true) ?: $this->port) {
            $ssh .= ' -p '.$port;
        }

        // Get key
        $key = $this->connections->getServerCredentials();
        $key = Arr::get($key, 'key');
        if ($key) {
            $ssh .= ' -i '.$key;
        }

        return $ssh;
    }
}
