<?php

use Illuminate\Foundation\Application as BaseApplication;
use Sikessem\Application;

it('should be an instance of the Laravel application', function () {
    expect(new Application)->toBeInstanceOf(BaseApplication::class);
});
