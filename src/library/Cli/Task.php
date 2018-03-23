<?php

namespace ICTool\Cli;

use Phalcon\Cli\Task as PhTask;
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;
use GetOptionKit\Exception\RequireValueException;
use GetOptionKit\Exception\InvalidOptionException;
use ICTool\Cli\Exception\PrintHelpException;


class Task extends PhTask
{
    protected function parseArgs($argv, $cmdDef)
    {
        // Configure the command line definition
        $specs = new OptionCollection();

        foreach ($cmdDef['opts'] as $option => $help) {
            $specs->add($option, $help);
        }

        // Every program will have an auto-generated help
        $specs->add('h|help', 'Display this help and exit');

        // Assign the command definition
        try {
            $parse = new OptionParser($specs);
        } catch (\Exception $e) {
            error_log("$cmd: The program has misconfigured options.");
            exit(1);
        }

        if (isset($argv[1]) && in_array($argv[1], ['--help', '-h'])) {
            throw new PrintHelpException($cmdDef, $specs);
        }

        // Use the options definition to parse the programa  arguments
        try {
            $result = $parse->parse($argv);
        } catch (RequireValueException $e) {
            throw new PrintHelpException($cmdDef, $specs, $e->getMessage(), 1);
        } catch (InvalidOptionException $e) {
            throw new PrintHelpException($cmdDef, $specs, $e->getMessage(), 1);
        } catch (\Exception $e) {
            throw new PrintHelpException($cmdDef, $specs, $e->getMessage(), 1);
        }

        // Ensure that the required arguments are supplied
        if (count($result->arguments) < count($cmdDef['args']['required'])) {
            throw new PrintHelpException($cmdDef, $specs, 'missing operand', 1);
        }

        // Clean arguments
        $args = array_map(function ($arg) {return $arg->arg; }, $result->arguments);

        // Clean options
        $opts = array_map(function ($opt) { return $opt->value; }, $result->keys);

        // The final result to be used in Tasks
        return [
            'args' => $args,
            'opts' => $opts
        ];
    }
}