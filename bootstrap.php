<?php

    /*
        --------------------------------
        Glowie bootstrapper
        --------------------------------
        This file is responsible for loading Glowie application files.
        It also starts the application router.

    */

    // Store application starting time
    $glowieTimer = microtime(true);

    // Setup application include paths
    set_include_path('../');

    // Check minimum PHP version
    if (version_compare(phpversion(), '7.4.9', '<')) {
        die('<strong>Unsupported PHP version!</strong><br>
            Glowie requires PHP version 7.4.9 or higher, you are using ' . phpversion() . '.');
    }

    // Check configuration file
    if(!file_exists('../config/Config.php')){
        die('<strong>Configuration file not found!</strong><br>
            Please rename "app/config/Config.example.php" to "app/config/Config.php".');
    }

    // Include configuration file
    require_once('config/Config.php');
    
    // Check configuration environment
    if(empty($glowieConfig[getenv('GLOWIE_ENV')])){
        die('<strong>Invalid configuration environment!</strong><br>
            Please check your application settings.');
    }
    
    // Include application routes
    require_once('config/Routes.php');
  
    // Include languages
    foreach (glob('../languages/*.php') as $filename) require_once($filename);
    
    // Inlude models
    foreach (glob('../models/*.php') as $filename) require_once($filename);
    
    // Include controllers
    foreach(glob('../controllers/*.php') as $filename) require_once($filename);

    // Setup configuration environment
    $glowieConfig = $glowieConfig[getenv('GLOWIE_ENV')];

    // Initialize router
    $app = new Rails();
    $app->init();
    
?>