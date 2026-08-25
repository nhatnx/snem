<?php
/**
 * Front controller — every request enters here.
 */

// When you copy this starter out of the SNEM repo, keep only the first branch.
$autoload = file_exists(__DIR__ . '/vendor/autoload.php')
    ? __DIR__ . '/vendor/autoload.php'
    : dirname(__DIR__, 2) . '/vendor/autoload.php';

require $autoload;

use app\core\SNEMLibrary;

SNEMLibrary::createApplication(__DIR__ . '/config.php')->run();
