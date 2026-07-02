<?php

//Hospital Referral System Information & Configuration
$conf = [];

$conf['site_name'] = 'ReferNet';
$conf['site_url']  = 'http://localhost/REFERNET';
$conf['admin_email'] = 'admin@refernet.co.ke';


$conf['smtp_host'] = 'smtp.gmail.com';
$conf['smtp_user'] = '';
$conf['smtp_pass'] = ''; // Gmail App Password
$conf['smtp_port'] = 465;
$conf['smtp_secure'] = 'ssl';
//mysql://root:agdiXaBUGIHqerfXXkjUVgAsbgjTYOrF@metro.proxy.rlwy.net:10661/railway

$conf['db_type'] = 'PDO';
$conf['db_host'] = 'metro.proxy.rlwy.net';
$conf['db_port'] = 10661;
$conf['db_user'] = 'root';
$conf['db_pass'] = 'agdiXaBUGIHqerfXXkjUVgAsbgjTYOrF';
$conf['db_name'] = 'refernet';

//database connection
try {
    $dsn = "mysql:host={$conf['db_host']};port={$conf['db_port']};dbname={$conf['db_name']};charset=utf8mb4";

    $conn = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("ReferNet DB connection failed: " . $e->getMessage());
}


$conf['site_lang'] = 'en';

?>