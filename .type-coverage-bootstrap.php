<?php

declare(strict_types=1);

// PHP 8.4 pcntl_fork() causes zend_mm_heap corruption (https://github.com/php/php-src/issues/17866).
// Force pest-plugin-type-coverage to use synchronous mode instead of forking.
$_ENV['__PEST_PLUGIN_ENV'] = '1';
