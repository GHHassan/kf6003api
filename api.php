<?php

/**
 * API entry point (api.php)
 * 
 * this file is the main entry point for this API. 
 * It is sets up the error reporting and autoloader,
 * It is also responsible for normalising the endpoint names
 * on on the url and routing the requests to 
 * the correct endpoint and returning the response
 * to the client in JSON format.
 * 
 * 
 * @author Ghulam Hassan Hassani <w20017074@nornthumbria.ac.uk>
 * 
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("config/ExceptionHandler.php");
set_exception_handler("ExceptionHandler");

include 'config/Autoloader.php';
include 'config/Setting.php';

Autoloader::register();
$response = new \App\Response();
$response = new \App\Response();
$endpointName = \App\Router::routeRequest();
$data = $endpointName->getData();
$response->outputJSON($data);