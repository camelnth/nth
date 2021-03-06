<?php

namespace Camel\Pattern\Console;

use Camel\Pattern\GeneratorPatternCommand;
use Illuminate\Support\Facades\DB;

/**
 * Class CreatePatternCommand
 *
 * @package Camel\Pattern\Console
 *
 * @author Hieu Nguyen <tronghieudev@gmail.com>
 */
class CreatePatternCommand extends GeneratorPatternCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'camel:pattern {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service';

    /**
     * Get stubs
     */
    protected function getStub()
    {
        $this->implementRepositoryStub = __DIR__ . '/stubs/implement.repository.stub';
        $this->implementServiceStub = __DIR__ . '/stubs/implement.service.stub';
        $this->interfaceRepositoryStub = __DIR__ . '/stubs/interface.repository.stub';
        $this->interfaceServiceStub = __DIR__ . '/stubs/interface.service.stub';

        // Path stub provider
        $this->providerInternalStub = __DIR__ . '/stubs/provider.internal.stub';
        $this->providerRepositoryStub = __DIR__ . '/stubs/provider.repository.stub';

        // Path stub base repository
        $this->baseRepositoryStub = __DIR__ . '/stubs/base.repository.stub';

        // Path stub base internal service
        $this->baseInternalStub = __DIR__ . '/stubs/base.internal.stub';

        // Path stub model
        $this->modelStub = __DIR__ . '/stubs/model.stub';

        // Path stub controller
        $this->controllerStub = __DIR__ . '/stubs/controller.stub';
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->askCreateModel();
//        $this->createFormRequest();

        parent::handle(); // TODO: Change the autogenerated stub
    }

    /**
     * @throws \Exception
     */
    protected function askCreateModel()
    {
        if ($this->confirm('Do you want to create model?')) {
            $this->modelName = $this->ask('What is name model ?');
            $this->tableName = $this->ask('What is name table ?');
            
            $this->modelNamespace = $this->createModel();
        }
    }

    protected function createModel()
    {
        $this->call('camel:model', [
            'name' => $this->modelName,
            '--table' => $this->tableName
        ]);
    }
}