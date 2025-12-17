<?php
declare(strict_types=1);

/**
 * Minimal PHPUnit bootstrap for a custom‑module test suite.
 *
 * It only loads Composer's autoloader so that all classes that were
 * installed via Composer (including Drupal core, PHPUnit, and any
 * third‑party libraries) can be found.
 */
require __DIR__ . '/../vendor/autoload.php';
