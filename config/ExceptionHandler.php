<?php

 /**
  * Exception handler
  * 
  * This function is responsible for handling all the exceptions thrown by the application.
  * It outputs the exception details in JSON format.
  * 
  * @param \Exception $e The exception object
  * @return void
  * 
  * @package App
  */
  
function exceptionHandler($e) {
   $output['message'] = "Internal Server Error";
   $output['details']['exception'] = $e->getMessage();
   $output['details']['file'] = $e->getFile();
   $output['details']['line'] = $e->getLine();
   echo json_encode($output);
   exit();
}