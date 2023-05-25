<?php
namespace Mythi\LaravelDevTools;

use Mythi\LaravelDevTools\Commands\MakeAPIControllerCommand;
use Mythi\LaravelDevTools\Commands\MakeRequestsCommand;
use Illuminate\Support\ServiceProvider;

class LaravelDevToolsServiceProvider extends ServiceProvider
{
    public static string $rootPath = __DIR__;
    public function boot(): void
    {
        $this->commands([
            MakeRequestsCommand::class,
            MakeAPIControllerCommand::class
        ]);
    }
}
