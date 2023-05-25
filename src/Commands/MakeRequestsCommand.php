<?php

namespace AlterationDream\LaravelDevTools\Commands;

use AlterationDream\LaravelDevTools\LaravelDevToolsServiceProvider as Provider;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;

class MakeRequestsCommand extends GeneratorCommand
{
    protected $signature = 'make:requests {name : Name of the model to make requests for} {--api} {--force} {--extends=}';
    protected $name = 'make:requests';
    protected $description = 'Create a new set of FormRequest classes for a controller';
    protected $type = 'Request';

    protected function getExtendsInput(): string|null
    {
        $extendsInput = trim($this->option('extends'));
        return !empty($extendsInput) ? $extendsInput : 'Illuminate\Foundation\Http\FormRequest';
    }

    protected function getAPIInput(): bool|array|string|null
    {
        return $this->option('api');
    }

    protected function getNameInput(): string
    {
        return trim($this->argument('name'));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests\\'.ucfirst($this->argument('name'));
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/requests.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(Provider::$packagePath.trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function buildClass($route): string
    {
        $stub = $this->files->get($this->getStub());
        $namespace = $this->rootNamespace().'Http\Requests\\'.ucfirst($this->argument('name'));

        $this->replaceNamespace($stub, $namespace);
        $this->replaceExtends($stub);
        return $this->replaceClass($stub, $route);
    }

    protected function replaceNamespace(&$stub, $namespace): bool
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$namespace, $this->rootNamespace(), $this->userProviderModel()],
                $stub
            );
        }

        return true;
    }

    protected function replaceClass($stub, $route): array|string
    {
        $class = ucfirst($this->getNameInput()) . $route . 'Request';
        return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
    }

    protected function replaceExtends(&$stub): array|string
    {
        $extendsUse = $this->getExtendsInput();
        $extendsClass = explode('\\', $extendsUse);
        $extendsClass = end($extendsClass);
        $stub = str_replace(['{{ extendsUse }}'], $extendsUse, $stub);
        $stub = str_replace('{{ extends }}', $extendsClass, $stub);
        return true;
    }

    /*protected function alreadyExists($rawName)
    {
        return $this->files->exists($this->getPath($rawName));
    }*/

    public function handle(): bool|null
    {
        $routes = ['Index', 'Show', 'Store', 'Update', 'Destroy'];
        if (!$this->getAPIInput()) {
            array_push($routes, 'Create', 'Edit');
        }
        foreach ($routes as $route) {
            if ($this->isReservedName($this->getNameInput())) {
                $this->components->error('The name "'.$this->getNameInput().'" is reserved by PHP.');
                return false;
            }

            $name = $this->qualifyClass($this->getClassName($route));
            $path = $this->getPath($name);

            if ((! $this->hasOption('force') ||
                    ! $this->option('force')) &&
                $this->alreadyExists($this->getClassName($route))) {
                $this->components->error($name.' already exists.');

                return false;
            }

            $this->makeDirectory($path);
            $this->files->put($path, $this->sortImports($this->buildClass($route)));
            $info = $this->type;

            if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
                if ($this->handleTestCreation($path)) {
                    $info .= ' and test';
                }
            }

            $this->components->info(sprintf('%s [%s] created successfully.', $info, $path));
        }
        return true;
    }

    protected function getClassName($route): string
    {
        return ucfirst($this->getNameInput()) . $route . 'Request';
    }
}
