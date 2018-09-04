<?php

namespace Camel\Pattern;

use Camel\Pattern\Console\CreateControllerCommand;
use Camel\Pattern\Console\CreateFormRequestCommand;
use Camel\Pattern\Console\CreateModelCommand;
use Camel\Pattern\Console\CreatePatternCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Class PatternService
 *
 * @package Camel\Pattern
 */
class PatternService extends ServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = true;

    /**
     * @var array
     */
    protected $commands = [
        'CreatePattern' => 'command.camel.pattern',
        'CreateModel' => 'command.camel.model',
        'CreateController' => 'command.camel.controller',
        'CreateFormRequest' => 'command.camel.form-request',
    ];

    public function register()
    {
        $this->registerCommands($this->commands);
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCreatePatternCommand()
    {
        $this->app->singleton('command.camel.pattern', function ($app) {
            return new CreatePatternCommand($app['files']);
        });
    }

    protected function registerCreateModelCommand()
    {
        $this->app->singleton('command.camel.model', function ($app) {
            return new CreateModelCommand($app['files']);
        });
    }

    protected function registerCreateControllerCommand()
    {
        $this->app->singleton('command.camel.controller', function ($app) {
            return new CreateControllerCommand($app['files']);
        });
    }

    protected function registerCreateFormRequestCommand()
    {
        $this->app->singleton('command.camel.form-request', function ($app) {
            return new CreateFormRequestCommand($app['files']);
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}