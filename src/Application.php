<?php

declare(strict_types=1);

namespace Sikessem;

use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Http\Request;
use Sikessem\Contracts\IsApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application extends BaseApplication implements IsApplication
{
    protected static self $INSTANCE;

    /**
     * @var string
     */
    protected $namespace = 'App\\';

    public static function create(string $basePath = null): IsApplication
    {
        if (! isset(self::$INSTANCE)) {
            if (! $basePath) {
                $basePath = backtrace(limit: 1)->getDirectory();
            }
            self::$INSTANCE = new self($basePath);
            /** @var string */
            $langPath = value(static function (): string {
                $directory = is_dir(self::$INSTANCE->resourcePath('locales'))
                ? self::$INSTANCE->resourcePath('locales')
                : self::$INSTANCE->resourcePath('i18n');

                if (is_dir($directory)) {
                    return $directory;
                }

                return self::$INSTANCE->basePath('lang');
            });
            self::$INSTANCE->useLangPath($langPath);
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
            $input = new ArgvInput(),
            new ConsoleOutput()
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
        /** @var ConsoleKernel */
        $kernel = $this->make(ConsoleKernel::class);

        return $kernel;
    }

    public function makeHttpKernel(): HttpKernel
    {
        /** @var HttpKernel */
        $kernel = $this->make(HttpKernel::class);

        return $kernel;
    }

    /**
     * {@inheritDoc}
     */
    public function path($path = '')
    {
        if (empty($this->appPath) && is_dir($this->rootDir('src'))) {
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
            return $this->rootDir('res').($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
        }

        return parent::resourcePath($path);
    }

    /**
     * Get the path to the templates directory.
     */
    public function templatePath(string $path = ''): string
    {
        if (is_dir($this->rootDir('tpl'))) {
            return $this->rootDir('tpl').($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
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
        /** @var ConfigContract */
        $config = $this['config'];
        /** @var string[] */
        $viewPaths = $config->get('view.paths', [$this->templatePath()]);
        foreach ($viewPaths as $viewPath) {
            if (is_dir($viewPath)) {
                $viewPath = rtrim($viewPath, DIRECTORY_SEPARATOR);

                return $this->joinPaths($viewPath, $path);
            }
        }

        return $this->templatePath($path);
    }
}
