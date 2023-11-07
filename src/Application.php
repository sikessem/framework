<?php

declare(strict_types=1);

namespace Sikessem;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application extends BaseApplication
{
    protected static self $INSTANCE;

    /**
     * {@inheritDoc}
     */
    protected $namespace = 'App\\';

    public static function create(string $basePath = null): static
    {
        if (! isset(self::$INSTANCE)) {
            if (! isset($basePath)) {
                $basePath = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
            }
            self::$INSTANCE = new static($basePath);
            self::$INSTANCE->useLangPath(value(function () {
                if (is_dir($directory = self::$INSTANCE->resourcePath('locales'))) {
                    return $directory;
                }

                return self::$INSTANCE->basePath('locales');
            }));
        }

        return self::$INSTANCE;
    }

    public function run(): void
    {
        define('LARAVEL_START', microtime(true));

        $this->runningInConsole() ? $this->handleCommand() : $this->handleRequest();
    }

    public function handleCommand(): void
    {
        $kernel = $this->makeConsoleKernel();

        $status = $kernel->handle(
            $input = new ArgvInput,
            new ConsoleOutput
        );

        $kernel->terminate($input, $status);

        exit($status);
    }

    public function handleRequest(): void
    {
        $kernel = $this->makeHttpKernel();

        $response = $kernel->handle(
            $request = Request::capture()
        )->send();

        $kernel->terminate($request, $response);
    }

    public function makeKernel(): ConsoleKernel|HttpKernel
    {
        return $this->runningInConsole() ? $this->makeConsoleKernel() : $this->makeHttpKernel();
    }

    public function makeConsoleKernel(): ConsoleKernel
    {
        return $this->make(ConsoleKernel::class);
    }

    public function makeHttpKernel(): HttpKernel
    {
        return $this->make(HttpKernel::class);
    }

    /**
     * {@inheritDoc}
     */
    public function path($path = '')
    {
        if (! isset($this->appPath) && is_dir($this->rootDir('src'))) {
            $this->appPath = $this->rootDir('src');
        }

        return parent::path($path);
    }

    /**
     * {@inheritDoc}
     */
    public function resourcePath($path = '')
    {
        if (is_dir($this->rootDir('res'))) {
            return $this->rootDir('res').($path != '' ? DIRECTORY_SEPARATOR.$path : '');
        }

        return parent::resourcePath($path);
    }

    /**
     * Get the path to the templates directory.
     */
    public function templatePath(string $path = ''): string
    {
        if (is_dir($this->rootDir('tpl'))) {
            return $this->rootDir('tpl').($path != '' ? DIRECTORY_SEPARATOR.$path : '');
        }

        return $this->joinPaths($this->basePath('templates'), $path);
    }

    public function rootDir(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * {@inheritDoc}
     */
    public function viewPath($path = '')
    {
        foreach ($this['config']->get('view.paths', [$this->templatePath()]) as $viewPath) {
            if (is_dir($viewPath)) {
                $viewPath = rtrim($viewPath, DIRECTORY_SEPARATOR);

                return $this->joinPaths($viewPath, $path);
            }
        }

        return $this->templatePath($path);
    }
}
