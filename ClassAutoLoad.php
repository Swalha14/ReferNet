<?php

 require 'Plugins/PHPMailer/vendor/autoload.php';

require_once 'conf.php';
require_once 'Includes/dbconnection.php';


$directories = ['Forms', 'Layout', 'Global'];


spl_autoload_register(function ($className) use ($directories) {
    foreach ($directories as $directory) {
        $filePath = __DIR__ . '/' . $directory . '/' . $className . '.php';
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});

//Create a database connection
$SQL = new dbConnection($conf['db_type'], $conf['db_host'], $conf['db_name'], $conf['db_user'], $conf['db_pass'], $conf['db_port']);

// Create instances
$Objform   = new Forms();
$Objlayout = new layout();
//$ObjSendMail= new SendMail();
