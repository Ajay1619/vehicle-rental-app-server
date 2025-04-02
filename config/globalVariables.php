<?php

define('BASEPATH', 'http://192.168.0.108/vehicle_rental_app');
define('ROOT', $_SERVER['DOCUMENT_ROOT'] . '/vehicle_rental_app');
define('GLOBAL_PATH', BASEPATH . '/global');
define('UPLOADS', BASEPATH . '/uploads');
define('TIMEZONE', 'Asia/Kolkata');
define('COUNTRY', 'India');
define('COUNTRY_CODE', 'IN');
define('LANG', 'EN');
define('CURRENCY', 'INR');
define('CURRENCY_SYMBOL', '&#8377;');
define("COOKIE_TIME_OUT", 10); //specify cookie timeout in days (default is 10 days)

date_default_timezone_set(TIMEZONE);

//application hosted date
define('HOSTED_DATE', '2021-01-01');
//date format
define('DATE_FORMAT', 'd-m-Y');
//DB date format
define('DB_DATE_FORMAT', 'Y-m-d');
//time format
define('TIME_FORMAT', 'h:i:s A');
//date time format
define('DATETIME_FORMAT', 'd-m-Y h:i:s A');
//file timestamp
define('FILE_DATETIME_FORMAT', 'd-m-Y h i s A');
