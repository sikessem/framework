<?php

declare(strict_types=1);

test('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

test('classes')
    ->expect('Sikessem')
    ->toUseStrictTypes();

test('contracts')
    ->expect('Sikessem\Contracts')
    ->interfaces()
    ->toOnlyBeUsedIn('Sikessem', 'Sikessem\Contracts');

test('concerns')
    ->expect('Sikessem\Concerns')
    ->traits()
    ->toOnlyBeUsedIn('Sikessem', 'Sikessem\Concerns');
