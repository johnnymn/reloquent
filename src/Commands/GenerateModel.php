<?php

namespace Reloquent\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\AppNamespaceDetectorTrait;

class GenerateModel extends GeneratorCommand
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reloquent:model {className=User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new Reloquent Model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/Model.stub';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $stub = $this->files->get($this->getStub());
        $stub = $this->fillStub($stub);

        $name = $this->argument('className');

        $path = $this->getPath($name);

        if ($this->pathExists($path)) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $stub);

        $this->info('Reloquent model created successfully.');
    }

    public function fillStub($stub)
    {
        $className = $this->argument('className');
        $namespace = str_replace('\\', '', $this->getAppNamespace());
        $prefix = Str::plural($className);

        $stub = str_replace(
            '{{namespace}}', $namespace, $stub
        );

        $stub = str_replace(
            '{{class}}', $className, $stub
        );

        $stub = str_replace(
            '{{prefix}}', '\''.$prefix.'\'', $stub
        );

        return $stub;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return str_replace('\\', '/', $this->laravel->basePath().'/'.Str::lower($this->getAppNamespace()).$name.'.php');
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $path
     * @return bool
     */
    protected function pathExists($path)
    {
        return $this->files->exists($path);
    }
}
