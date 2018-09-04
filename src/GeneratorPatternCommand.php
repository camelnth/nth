<?php

namespace Camel\Pattern;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class GeneratorPatternCommand
 *
 * @package Camel\Pattern
 *
 * @author CamelNTH <camelnth@gmail.com>
 */
abstract class GeneratorPatternCommand extends Command
{

    /**
     * Path default service and repository
     */
    const PATH_SERVICE = 'Services/Internals';
    const PATH_REPOSITORY = 'Services/Repositories';

    /**
     * Types
     */
    const TYPE_INTERNAL = 'internal';
    const TYPE_REPOSITORY = 'repository';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type;

    /**
     * Path file internal and service
     */
    protected $pathFileInternal;
    protected $pathFileRepository;

    /**
     * Path stubs
     */
    protected $implementRepositoryStub;
    protected $implementServiceStub;
    protected $interfaceRepositoryStub;
    protected $interfaceServiceStub;

    /**
     * Path stub internal service provider and repository service provider
     */
    protected $providerInternalStub;
    protected $providerRepositoryStub;

    /**
     * Namespace internal and repository
     */
    protected $namespaceInternal;
    protected $namespaceRepository;

    /**
     * BaseRepository stub
     */
    protected $baseRepositoryStub;
    protected $baseInternalStub;

    /**
     * Table
     */
    protected $tableName;
    protected $tableDetail;
    protected $modelName;
    protected $modelNamespace;

    /**
     * Model stub
     */
    protected $modelStub;

    /**
     * Controller stub
     */
    protected $controllerStub;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    abstract protected function getStub();

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $this->getStub();
        $this->createServiceProvider();

        $this->setNamespace($this->getNameInput());
        $this->setPath($this->getNameInput());

        $this->createFileRepository();
        $this->replaceRepositoryProvider();

        $this->createFileInternal();
        $this->replaceInternalProvider();

        $this->createBaseRepository();
        $this->createBaseInternalService();

        exec('composer dump-autoload > /dev/null 2>&1');
        $this->info("Pattern {$this->getNameInput()} created successfully.");
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param $path
     * @return mixed
     *
     * @throws \Exception
     */
    protected function makeDirectory($path)
    {
        if ($this->files->exists($path)) {
            $this->error("Service {$this->getNameInput()} already exist !");

            exit();
        }

        $this->files->makeDirectory(dirname($path), 0777, true, true);

        return $path;
    }

    /**
     * Set path cho internal service và internal repository
     *
     * @param $name
     *
     * @return $this
     */
    protected function setPath($name)
    {
        $this->pathFileInternal = $this->laravel['path'] . '/' . $this->getPathService() . $name;
        $this->pathFileRepository = $this->laravel['path'] . '/' . $this->getPathRepository() . $name;

        return $this;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    protected function setNamespace($name)
    {
        $this->namespaceInternal = $this->rootNamespace() . $this->getNamespaceByFolder($this->getPathService()) . $name;
        $this->namespaceRepository = $this->rootNamespace() . $this->getNamespaceByFolder($this->getPathRepository()) . $name;

        return $this;
    }

    /**
     * Tạo path cho internal service
     *
     * @return string
     */
    protected function getPathService()
    {
        return config('pattern.path_file_internal', self::PATH_SERVICE) . '/';
    }

    /**
     * Tạo path cho internal repository
     *
     * @return string
     */
    protected function getPathRepository()
    {
        return config('pattern.path_file_repository', self::PATH_REPOSITORY) . '/';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Tạo file service trong internal
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createFileInternal()
    {
        $this->type = self::TYPE_INTERNAL;

        // Put file service interface
        $this->putInterface($this->pathFileInternal, $this->getFileNameInternalService('interface'));

        // Put file service class
        $this->putClass($this->pathFileInternal, $this->getFileNameInternalService('class'));
    }

    /**
     * Tạo file repository
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createFileRepository()
    {
        $this->type = self::TYPE_REPOSITORY;

        // Put file repository interface
        $this->putInterface($this->pathFileRepository, $this->getFileNameRepositoryService('interface'));

        // Put file repository class
        $this->putClass($this->pathFileRepository, $this->getFileNameRepositoryService('class'));
    }

    /**
     * Tạo class service và repository implement interface tương ứng
     *
     * @param $path
     * @param $fileName
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function putClass($path, $fileName)
    {
        $folderFile = $this->makeDirectory($this->mergePath(...func_get_args()));

        $this->files->put($folderFile, $this->buildClass());
    }

    /**
     * Tạo interface cho internal service và internal repository
     *
     * @param $path
     * @param $fileName
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function putInterface($path, $fileName)
    {
        $folderFile = $this->makeDirectory($this->mergePath(...func_get_args()));

        $this->files->put($folderFile, $this->buildInterface());
    }

    /**
     * Gộp đường dẫn và tên file
     *
     * @param $path
     * @param $fileName
     *
     * @return string
     */
    protected function mergePath($path, $fileName)
    {
        return $path . '/' . $fileName;
    }

    /**
     * Tạo class internal service và internal repository theo mẫu stub
     *
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass()
    {
        if ($this->type == self::TYPE_INTERNAL) {
            $stub = $this->implementServiceStub;
        } else {
            $stub = $this->implementRepositoryStub;
        }

        $stub = $this->files->get($stub);
        $stub = $this->replaceNamespace($stub);
        $stub = $this->replaceInterfaceName($stub);
        $stub = $this->replaceClassName($stub);
        $stub = $this->replaceNameServiceRepository($stub);
        $stub = $this->replaceRepositoryInterface($stub);
        $stub = $this->replaceNameSpaceBaseRepository($stub);
        $stub = $this->replaceNameSpaceBaseInternalService($stub);
        // TODO : replaceModelInRepository
        // $stub = $this->replaceModelInRepository($stub);

        return $stub;
    }

    /**
     * Tạo class internal interface service và internal interface repository theo mẫu stub
     *
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildInterface()
    {
        if ($this->type == self::TYPE_INTERNAL) {
            $stub = $this->interfaceServiceStub;
        } else {
            $stub = $this->interfaceRepositoryStub;
        }

        $stub = $this->files->get($stub);
        $stub = $this->replaceNamespace($stub);
        $stub = $this->replaceInterfaceName($stub);

        return $stub;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createServiceProvider()
    {
        $pathInternalProvider = $this->getPathInternalServiceProvider();
        $pathRepositoryProvider = $this->getPathRepositoryServiceProvider();

        // Kiểm tra và tạo file InternalServiceProvider
        if (! $this->files->exists($pathInternalProvider)) {
            $stub = $this->files->get($this->providerInternalStub);

            $this->files->put($pathInternalProvider, $stub);
        }

        // Kiểm tra và tạo file RepositoryServiceProvider
        if (! $this->files->exists($pathRepositoryProvider)) {
            $stub = $this->files->get($this->providerRepositoryStub);

            $this->files->put($pathRepositoryProvider, $stub);
        }
    }

    /**
     * @return string
     */
    protected function getPathInternalServiceProvider()
    {
        return $this->laravel['path'] . '/Providers/InternalServiceProvider.php';
    }

    /**
     * @return string
     */
    protected function getPathRepositoryServiceProvider()
    {
        return $this->laravel['path'] . '/Providers/RepositoryServiceProvider.php';
    }

    /**
     * Get file name of internal service
     *
     * @param $option
     *
     * @return string
     */
    private function getFileNameInternalService($option)
    {
        if ($option == 'interface') {
            return $this->getNameInput() . 'ServiceInterface.php';
        }

        return $this->getNameInput() . 'Service.php';
    }
    /**
     * Get file name of internal service
     *
     * @param $option
     *
     * @return string
     */
    private function getFileNameRepositoryService($option)
    {
        if ($option == 'interface') {
            return $this->getNameInput() . 'RepositoryInterface.php';
        }

        return $this->getNameInput() . 'EloquentRepository.php';
    }

    /**
     * @param $option
     *
     * @return string
     */
    private function getFileNamespaceRepositoryService($option)
    {
        if ($option == 'interface') {
            return $this->getNameInput() . 'RepositoryInterface';
        }

        return $this->getNameInput() . 'EloquentRepository';
    }

    /**
     * @param $option
     *
     * @return string
     */
    private function getFileNamespaceInternalService($option)
    {
        if ($option == 'interface') {
            return $this->getNameInput() . 'ServiceInterface';
        }

        return $this->getNameInput() . 'Service';
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     *
     * @return $this
     */
    protected function replaceNamespace($stub)
    {
        $namespace = $this->type == self::TYPE_INTERNAL ? $this->namespaceInternal : $this->namespaceRepository;

        return str_replace(
            ['#Namespace'], [$namespace], $stub
        );
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceNameSpaceBaseRepository($stub)
    {
        return str_replace(
            ['#NameUseBaseRepository'], [$this->getNamespaceBaseRepository()], $stub
        );
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceNameSpaceBaseInternalService($stub)
    {
        return str_replace(
            ['#NameUseBaseInternalService'], [$this->getNamespaceBaseInternalService()], $stub
        );
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function replaceRepositoryProvider()
    {
        $stub = $this->files->get($this->getPathRepositoryServiceProvider());

        $stub = str_replace(
            ['#InterfaceNamespace', '#ClassNamespace', '#Singleton', '#InterfaceProvides'],
            [
                $this->getNamespace('interface'),
                $this->getNamespace('class'),
                $this->getSingletonProvider(),
                $this->getInterfaceProvides()
            ],
            $stub
        );

        $this->files->put($this->getPathRepositoryServiceProvider(), $stub);
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceModelInRepository($stub)
    {
        if ($this->modelName) {
            return str_replace(
                ['#ModelNamespace', '#ModelName'],
                [
                    $this->modelNamespace . '\\' . $this->modelName,
                    $this->modelName . '::class',
                ],
                $stub
            );
        }

        return str_replace(
            ['#ModelNamespace', '#ModelName'],
            ['', ''],
            $stub
        );
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function replaceInternalProvider()
    {
        $stub = $this->files->get($this->getPathInternalServiceProvider());

        $stub = str_replace(
            ['#InterfaceNamespace', '#ClassNamespace', '#Singleton', '#InterfaceProvides'],
            [
                $this->getNamespace('interface'),
                $this->getNamespace('class'),
                $this->getSingletonProvider(),
                $this->getInterfaceProvides()
            ],
            $stub
        );

        $this->files->put($this->getPathInternalServiceProvider(), $stub);
    }

    /**
     * @return string
     */
    protected function getInterfaceProvides()
    {
        return $this->getNamespaceServiceProvider('interface') . ',' . "\n\t\t\t" . '#InterfaceProvides';
    }

    /**
     * @return string
     */
    protected function getSingletonProvider()
    {
        return '$this->app->singleton(' .
            $this->getNamespaceServiceProvider('interface') .
            ', ' .
            $this->getNamespaceServiceProvider('class') .
        ');' . "\n\t\t" . '#Singleton';
    }

    /**
     * @param string $option
     * 
     * @return mixed
     */
    protected function getNamespace($option = 'interface')
    {
        $className = ($this->type == self::TYPE_INTERNAL)
            ? $this->getFileNamespaceInternalService($option)
            : $this->getFileNamespaceRepositoryService($option);

        $currentNamespace = ($this->type == self::TYPE_INTERNAL) ? $this->namespaceInternal : $this->namespaceRepository;
        $tag = ($option == 'interface') ? '#InterfaceNamespace' : '#ClassNamespace';

        return "use " . $currentNamespace . '\\' . explode('.', $className)[0] . ";" . "\n" . $tag;
    }

    /**
     * @return string
     */
    protected function getNamespaceRepositoryInterface()
    {
        $className = $this->getFileNamespaceRepositoryService('interface');

        return "use " . $this->namespaceRepository . '\\' . $className;
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceInterfaceName($stub)
    {
        $interfaceName = ($this->type == self::TYPE_INTERNAL )
            ? $this->getFileNameInternalService('interface')
                : $this->getFileNameRepositoryService('interface');
        $interfaceName = explode('.', $interfaceName)[0];

        return str_replace(
            ['#InterfaceName'], [$interfaceName], $stub
        );
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceClassName($stub)
    {
        $className = ($this->type == self::TYPE_INTERNAL )
            ? $this->getFileNameInternalService('class')
            : $this->getFileNameRepositoryService('class');
        $className = explode('.', $className)[0];

        return str_replace(
            ['#ClassName'], [$className], $stub
        );
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceNameServiceRepository($stub)
    {
        return str_replace(
            ['#NameService'], [lcfirst($this->getNameInput())], $stub
        );
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceRepositoryInterface($stub)
    {
        return str_replace(
            ['#UseRepositoryInterface', '#NameRepositoryInterface'],
            [
                $this->getNamespaceRepositoryInterface(),
                $this->getFileNamespaceRepositoryService('interface')
            ],
            $stub
        );
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace();
    }

    /**
     * @param $folder
     *
     * @return string
     */
    protected function getNamespaceByFolder($folder)
    {
        return implode('\\', explode('/', $folder));
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createBaseRepository()
    {
        $path = $this->getPathBaseRepository();

        // Kiểm tra và tạo file BaseRepository
        if (! $this->files->exists($path)) {
            $stub = $this->files->get($this->baseRepositoryStub);
            $stub = $this->replaceBaseRepository($stub);

            $this->files->put($path, $stub);
        }
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceBaseRepository($stub)
    {
        $namespace = $this->getNamespaceBaseRepository();

        return str_replace(
            ['#Namespace'], [$namespace], $stub
        );
    }

    /**
     * @return string
     */
    protected function getPathBaseRepository()
    {
        return $this->laravel['path'] . '/' . self::PATH_REPOSITORY . '/BaseRepository.php';
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseRepository()
    {
        return $this->rootNamespace() . $this->getNamespaceByFolder(self::PATH_REPOSITORY);
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createBaseInternalService()
    {
        $path = $this->getPathBaseInternalService();

        // Kiểm tra và tạo file BaseRepository
        if (! $this->files->exists($path)) {
            $stub = $this->files->get($this->baseInternalStub);
            $stub = $this->replaceBaseInternalService($stub);

            $this->files->put($path, $stub);
        }
    }

    /**
     * @param $stub
     *
     * @return mixed
     */
    protected function replaceBaseInternalService($stub)
    {
        $namespace = $this->getNamespaceBaseInternalService();

        return str_replace(
            ['#Namespace'], [$namespace], $stub
        );
    }

    /**
     * @return string
     */
    protected function getPathBaseInternalService()
    {
        return $this->laravel['path'] . '/' . self::PATH_SERVICE . '/BaseInternalService.php';
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseInternalService()
    {
        return $this->rootNamespace() . $this->getNamespaceByFolder(self::PATH_SERVICE);
    }

    /**
     * @param string $option
     *
     * @return string
     */
    protected function getNamespaceServiceProvider($option = 'interface')
    {
        if ($this->type == self::TYPE_INTERNAL) {
            $fileName = $this->getFileNamespaceInternalService($option);
        } else {
            $fileName = $this->getFileNamespaceRepositoryService($option);
        }

        return $fileName . '::class';
    }

    /**
     * Create form request
     */
    protected function createFormRequest()
    {
        if (! empty($this->tableName)) {
            foreach ($this->getListFormRequest() as $formRequest) {
                $this->call('camel:form-request', [
                    'name' => $this->getNameInput(),
                    '--table' => $this->tableName,
                    '--type' => $formRequest
                ]);
            }
        }
    }

    /**
     * Get list form request need create
     *
     * @return array
     */
    protected function getListFormRequest()
    {
        return [
            'create', 'update'
        ];
    }
}