<?php
/**
 * Application configuration.
 *
 * Only the keys that differ from the framework defaults need to be listed;
 * see the "Configuration reference" table in the SNEM README for the rest.
 */

return [
    'name' => 'SNEM Starter',
    'basePath' => __DIR__,

    'db' => [
        'driver' => 'mysqli',
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'databaseName' => 'snem_starter',
        'tablePrefix' => 'st_',
        'debugMode' => false,
    ],

    'defaultPage' => 'home',
    'templateName' => 'default',
    'debugMode' => true,

    // Absolute paths keep class loading independent of the web server's
    // working directory. These are the defaults; shown here to make the
    // convention explicit.
    'autoloadPaths' => [
        __DIR__ . '/app/controllers',
        __DIR__ . '/app/models',
        __DIR__ . '/app/helpers',
    ],

    'user_session_name' => 'starter_user',
    'notFoundMessage' => 'We could not find that page.',
    'forbiddenMessage' => 'Please sign in to view this page.',

    'maintenanceMode' => [
        'enabledMode' => false,
        'message' => 'The starter app is down for maintenance. Back shortly.',
    ],

    'params' => [
        'postsPerPage' => 10,
        'tagline' => 'A minimal app built on the SNEM micro-framework.',
    ],
];
