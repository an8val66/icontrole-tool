<?php

namespace ICTool\Cli;

use ICTool\Cli\Exception\PrintHelpException;
use Phalcon\Cli\Console as PhConsole;
use ICTool\Cli\Exception\ArgumentValidationException;


class Console extends PhConsole
{
    private $progPath;

    private $cmd;

    public function handle(array $arguments = null)
    {
        $config = $this->getDI()->getConfig();

        $this->progPath = $arguments['params'][0];

        if (isset($arguments['params'][1])) {
            $this->cmd = $arguments['params'][1];
        } else {
            if (!isset($arguments['defaultCmd'])) {
                throw new \Exception('The console was not given a command', 1);
            }

            $this->cmd = $arguments['defaultCmd'];
        }

        $params = array_merge(
            [$arguments['params'][0]],
            array_slice($arguments['params'], 2)
        );

        if (in_array($this->cmd, ['help', '--help', '-h'])) {
            $this->printCmdList();
            exit(0);
        }

        if (strpos($this->cmd, '.') !== false
            || strpos($this->cmd, '/') !== false) {
            throw new \Exception('Invalid command name', 1);
        }

        // All environments
        $cmdArr = require "{$config->path->appDir}/cmd.php";

        if (!array_key_exists($this->cmd, $cmdArr)) {
            throw new \Exception("The command '{$this->cmd}' does not exist", 1);
        }

        $taskParts = explode('::', $cmdArr[$this->cmd]);
        $task = $taskParts[0];
        $action = isset($taskParts[1]) ? $taskParts[1] : 'main';

        try {
            parent::handle([
                'task'   => $task,
                'action' => $action,
                'params' => $params
            ]);
        } catch (ArgumentValidationException $e) {
            $this->printHelpRecommend($e->getMessage());
            exit(1);
        } catch (PrintHelpException $e) {
            if ($e->getCode() == 1) {
                $this->printHelpRecommend($e->getMessage());
                exit(1);
            } else {
                $this->printHelp($e->getCmdDef(), $e->getSpecs());
                exit(0);
            }
        }
    }

    /**
     * Prints a the commads list
     */
    private function printCmdList()
    {
        $config = $this->getDI()->get('config');

        // All environments
        $cmdArr = require "{$config->path->appDir}/cmd.php";

        $cmdList = array_keys($cmdArr);
        sort($cmdList);

        echo "Available commands:\n";
        echo implode(', ', $cmdList) . "\n";
    }

    /**
     * Print an error message with a recommendation to access help
     *
     * @param string $message
     */
    private function printHelpRecommend($message)
    {
        error_log("{$this->progPath} {$this->cmd}: $message");
        error_log("Try {$this->progPath} {$this->cmd} --help' for more information");
    }

    /**
     * Prints a the program help
     *
     * @param array                          $cmdDef
     * @param \GetOptionKit\OptionCollection $specs
     */
    private function printHelp($cmdDef, $specs)
    {
        $reqArgs = array_map('strtoupper', $cmdDef['args']['required']);
        $optArgs = array_map(function ($arg) {
            return '[' . strtoupper($arg) . ']';
        }, $cmdDef['args']['optional']);

        $args = array_merge($reqArgs, $optArgs);
        $argNames = implode(' ', $args);

        echo "Usage: {$this->progPath} {$this->cmd} [OPTION] $argNames\n";
        echo "{$cmdDef['title']}\n";

        if (isset($cmdDef['help'])) {
            echo "{$cmdDef['help']}\n";
        }

        echo "\n";

        $widths = array_map(function ($spec) {
            return strlen($spec->renderReadableSpec());
        }, $specs->all());
        $width = max($widths);

        $lines = [];

        foreach ($specs->all() as $spec) {
            $c1 = str_pad($spec->renderReadableSpec(), $width);
            $line = sprintf("%s %s", $c1, $spec->desc);
            $lines[] = $line;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            echo " $line\n";
        }
    }
}