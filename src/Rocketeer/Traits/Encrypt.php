<?php

namespace Rocketeer\Traits;

use Rocketeer\Bash;

/**
 *
 * @property \Rocketeer\Bash                                     bash
 * @property \Rocketeer\Services\Pathfinder                      paths
 *
 */
trait Encrypt
{
    use Rsync;

    public function encryptCode($sourcePath, $destinationPath)
    {
        $command = sprintf('zendenc54 --asp-tags off --short-tags on --no-header --recursive --include-ext php --no-default-extensions --quiet %s %s',
            $sourcePath, $destinationPath);
        return $this->bash->onLocal(function (Bash $bash) use ($command) {
            return $bash->run($command);
        });
    }

    public function encryptUpload($sourcePath, $destinationPath, $exclude = null) {
        if (is_dir($sourcePath)) {
            $tmpEncryptPath = $this->paths->getUserHomeFolder().'/'.uniqid(rand()).'/';
        } else {
            $tmpEncryptPath = $this->paths->getUserHomeFolder().'/'.uniqid(rand()).'.php';
        }
        $this->encryptCode($sourcePath, $tmpEncryptPath);
        $this->rsyncTo($destinationPath, $tmpEncryptPath, $exclude);
        $this->bash->onLocal(function(Bash $bash) use ($tmpEncryptPath){
            return $bash->run('rm -rf '.$tmpEncryptPath);
        });
    }
}
