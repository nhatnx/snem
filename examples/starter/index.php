<?php
/**
 * Front controller — every request enters here.
 */

// When you copy this starter out of the SNEM repo, keep only the first branch.
$autoload = file_exists(__DIR__ . '/vendor/autoload.php')
    ? __DIR__ . '/vendor/autoload.php'
    : dirname(__DIR__, 2) . '/vendor/autoload.php';

require $autoload;

use app\core\SEDbException;
use app\core\SNEMLibrary;

try {
    SNEMLibrary::createApplication(__DIR__ . '/config.php')->run();
} catch (SEDbException $e) {
    // Any failed connection or query not handled closer to where it happened.
    error_log($e->getMessage());
    require __DIR__ . '/templates/dbError.php';
}
