<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\DomainErrorCode;

test('domain error codes are globally unique', function (): void {
    $enumDirectory = __DIR__ . '/../../src/app/Enum';

    $enumClasses = [];
    foreach (glob($enumDirectory . '/*.php') ?: [] as $filePath) {
        $className = pathinfo($filePath, PATHINFO_FILENAME);
        $fqcn      = 'Kwaadpepper\\LaravelStorageManager\\Enum\\' . $className;

        if (! class_exists($fqcn)) {
            continue;
        }

        if (! enum_exists($fqcn) || ! is_subclass_of($fqcn, DomainErrorCode::class)) {
            continue;
        }

        $enumClasses[] = $fqcn;
    }

    $codes      = [];
    $duplicates = [];

    foreach ($enumClasses as $enumClass) {
        foreach ($enumClass::cases() as $case) {
            /** @var DomainErrorCode $case */
            $code  = $case->code();
            $owner = $enumClass . '::' . $case->name;

            if (isset($codes[$code])) {
                $duplicates[(string) $code] = [$codes[$code], $owner];

                continue;
            }

            $codes[$code] = $owner;
        }
    }

    expect($duplicates)
        ->toBeEmpty('Duplicate domain error codes found: ' . json_encode($duplicates, JSON_THROW_ON_ERROR));
});
