<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Sikessem\Application;

beforeEach(function () {
    $app = new Application(dirname(__DIR__));

    $app->singleton(
        ConsoleKernelContract::class,
        ConsoleKernel::class
    );

    $this->app = $app;
});

it('should be an instance of the Laravel application', function () {
    expect($this->app)->toBeInstanceOf(BaseApplication::class);
});

it('should make kernel', function () {
    expect($this->app->makeKernel())->toBeInstanceOf(ConsoleKernelContract::class);
});
