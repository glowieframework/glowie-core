<?php

/*
    --------------------------------
    Application CLI server
    --------------------------------
    This file is responsible for running the local development server.
    Use `php firefly shine` to start.
*/

// Changes the working folder
chdir('app/public');

// Gets the request URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Checks if file exists
$file = getcwd() . '/' . $uri;
if ($uri != '/' && is_file($file)) {
    // Continues to the file
    return false;
} else {
    // Continues to the application
    require_once(getcwd() . '/index.php');
}
