<?php

namespace AlterationDream\LaravelDevTools\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;

class MakeAPIControllerCommand extends GeneratorCommand
{
    protected $signature = 'make:api-controller {name : Name of the model to make requests for} {--extends=} {--force}';
    protected $name = 'make:api-controller';
    protected $description = 'Create a new set of FormRequest classes for a controller';
    protected $type = 'Controller';

    protected function getExtendsInput(): string|null
    {
        $extendsInput = trim($this->option('extends'));
        return !empty($extendsInput) ? $extendsInput : 'BaseAPIController';
    }

    protected function getNameInput(): string
    {
        return trim($this->argument('name'));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Controllers';
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/api-controller.stub');
    }

    protected function getBaseStub(): string
    {
        return $this->resolveStubPath('/stubs/BaseAPIController.stub');
    }

    protected function resolveStubPath($stub): string
    {
        var_dump(__DIR__.$stub);
        return file_exists( dirname(__FILE__).$stub);
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());
        $namespace = $this->rootNamespace().'Http\Controllers';

        $this->replaceNamespace($stub, $namespace);
        $this->replaceExtends($stub);
        return $this->replaceClass($stub, $this->getNameInput());
    }

    protected function getBaseClass(): string
    {
        return $this->files->get($this->getBaseStub());
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

    protected function replaceClass($stub, $name): array|string
    {
        return str_replace(['DummyName', '{{ name }}', '{{name}}'], $name, $stub);
    }

    protected function replaceExtends(&$stub): array|string
    {
        $extends = $this->getExtendsInput();
        $stub = str_replace('{{ extends }}', $extends, $stub);
        return true;
    }

    public function handle(): bool|null
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->components->error('The name "'.$this->getNameInput().'" is reserved by PHP.');
            return false;
        }

        $name = $this->qualifyClass($this->getClassName());

        $path = $this->getPath($name);
        $this->makeDirectory($path);

        if ((!$this->hasOption('force') || !$this->option('force')) &&
        $this->alreadyExists($this->getClassName()))
        {
            $this->components->error($name.' already exists.');
            return false;
        }

        if (!$this->alreadyExists($this->getBaseName()))
        {
            $basePath = $this->getPath($this->qualifyClass($this->getBaseName()));
            $baseInfo = 'Base Controller';
            $this->files->put($basePath, $this->getBaseClass());
            $this->showCreatedInfo($basePath, $baseInfo);
        }

        $this->files->put($path, $this->sortImports($this->buildClass($name)));
        $info = $this->type;
        $this->showCreatedInfo($path, $info);

        return true;
    }

    protected function showCreatedInfo($path, $info) {
        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            if ($this->handleTestCreation($path)) {
                $info .= ' and test';
            }
        }
        $this->components->info(sprintf('%s [%s] created successfully.', $info, $path));
    }

    protected function getClassName(): string
    {
        return $this->getNameInput().'Controller';
    }

    protected function getBaseName(): string
    {
        return 'BaseAPIController';
    }
}
