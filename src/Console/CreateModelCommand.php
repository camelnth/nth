<?php

namespace Camel\Pattern\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\DB;

/**
 * Class CreateModelCommand
 *
 * @package Camel\Pattern\Console
 *
 * @author Hieu Nguyen <tronghieudev@gmail.com>
 */
class CreateModelCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'camel:model {name} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model';

    /**
     * @var string
     */
    protected $type = 'Model';

    protected $tableName;
    protected $tableDetail;

    /**
     * Get stubs
     */
    protected function getStub()
    {
        // Path stub model
        return __DIR__ . '/stubs/model.stub';
    }

    /**
     * @return bool|null
     *
     * @throws \Exception
     */
    public function handle()
    {
        $this->askTableName();

        parent::handle();
    }

    /**
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceTableName($stub)
            ->replaceFillables($stub)
            ->replaceClass($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     *
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['#Namespace'],
            [$this->getNamespace($name)],
            $stub
        );

        return $this;
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     *
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace('#ModelName', $class, $stub);
    }

    protected function replaceTableName(&$stub)
    {
        $stub = str_replace('#TableName', $this->tableName, $stub);

        return $this;
    }

    protected function replaceFillables(&$stub)
    {
        $fillable = $this->tableDetail->whereNotIn('Field', $this->getFieldNoNeed())->pluck('Field')->all();

        foreach ($fillable as $key => $field) {

            $this->replaceFillable($stub, $field, ($key == count($fillable) - 1) ? true : false);
        }

        return $this;
    }

    /**
     * @param $stub
     * @param $field
     *
     * @param bool $end
     *
     * @return $this
     */
    protected function replaceFillable(&$stub, $field, $end = false)
    {
        $end = ($end == true) ? '' : ",\n\t\t#Fillable";
        $stub = str_replace('#Fillable', "'{$field}'{$end}", $stub);

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function askTableName()
    {
        $this->tableName = ($this->option('table')) ? $this->option('table') : $this->ask('What is name table ?');

        $this->getFieldOnTable();
    }

    /**
     * @throws \Exception
     */
    protected function getFieldOnTable()
    {
        try {
            $this->tableDetail = collect(DB::select("SHOW COLUMNS FROM {$this->tableName}"));
        } catch (\Exception $exception) {
            $this->error('Table is not exist !');
            exit();
        }
    }

    /**
     * @return array
     */
    protected function getFieldNoNeed()
    {
        return [
            'id', 'created_at', 'updated_at', 'deleted_at'
        ];
    }
}