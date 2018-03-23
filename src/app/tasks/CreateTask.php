<?php

use ICTool\Cli\Task;


class CreateTask extends Task
{
    public function projectAction($argv)
    {
        $params = $this->parseArgs($argv, [
            'title' => 'Create a skeleton application',
            'args'  => [
                'required' => ['path'],
                'optional' => []
            ],
            'opts'  => [
                'p|path' => ''
            ]
        ]);

        list($path) = $params['args'];
        $opts = $params['opts'];
        $tmpDir = sys_get_temp_dir();

        if (!extension_loaded('zip')) {
            throw new Exception('You need to install the ZIP extension of PHP');
        }

        if (!extension_loaded('openssl')) {
            throw new Exception('You need to install the OpenSSL extension of PHP');
        }

        if (file_exists($path)) {
            throw new \Exception("The directory $path already exists. You cannot create a Icontrole project here.");
        }

        $commit = Skeleton::getLastCommit();

        if (false === $commit) {// error on github connection
            $tmpFile = Skeleton::getLastZip($tmpDir);

            if (!empty($tmpFile)) {
                throw new Exception('I cannot access the API of github.');
            }

            echo ("Warning: I cannot connect to github, I will use the last download of IControle Skeleton." . PHP_EOL);
        } else {
            $tmpFile = Skeleton::getTmpFileName($tmpDir, $commit);
        }

        if (!file_exists($tmpFile)) {
            if (!Skeleton::getSkeletonApp($tmpFile)) {
                throw new Exception('I cannot access the IControle Skeleton application in github.');
            }
        }

        $zip = new \ZipArchive;

        if ($zip->open($tmpFile)) {
            $stateindex0 = $zip->statIndex(0);
            $tmpSkeleton = $tmpDir . '/' . rtrim($stateindex0['name'], '/');

            if(!$zip->extractTo($tmpDir)) {
                throw new Exception("Error during the unzip of $tmpFile.");
            }

            $result = Utility::copyFiles($tmpSkeleton, $path);

            if (file_exists($tmpSkeleton)) {
                Utility::deleteFolder($tmpSkeleton);
            }

            $zip->close();

            if (false === $result) {
                throw new Exception("Error during the copy of the files in $path.");
            }
        }

        if (file_exists("$path/composer.phar")) {
            exec("php $path/composer.phar self-update");
        } else {
            if (!file_exists("$tmpDir/composer.phar")) {
                if (!file_exists("$tmpDir/composer_installer.php")) {
                    file_put_contents(
                        "$tmpDir/composer_installer.php",
                        '?>' . file_get_contents('https://getcomposer.org/installer')
                    );
                }

                exec("php $tmpDir/composer_installer.php --install-dir $tmpDir");
            }

            copy("$tmpDir/composer.phar", "$path/composer.phar");
        }

        chmod("$path/composer.phar", 0755);

        echo "IControle Skeleton installed in $path." . PHP_EOL;
        echo "Execute: \"composer.phar install\" in $path." . PHP_EOL;
    }
}