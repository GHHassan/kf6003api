<?php
namespace App;
class Requesthandler
{
    private $requestData;

    public function __construct()
    {
        $this->requestData = $this->parseJsonRequestBody();
    }

    public function getData()
    {
        return $this->requestData;
    }

    private function parseJsonRequestBody()
    {
        if(file_get_contents("php://input") === null){
         
            return "no data";
        }
        // Get the raw request body
        $rawData = file_get_contents("php://input");
        // Decode the JSON data
        $jsonData = json_decode($rawData, true);

        if($jsonData === null){
            return null;
        }
        // Check if JSON decoding was successful
        if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON parsing error
            throw new \Exception('Error parsing JSON request body: ' . json_last_error_msg());
        }
        return $jsonData;
    }

    public static function hasParam($param)
    {
        return isset($_GET[$param]) || isset($_POST[$param]);
    }
    public static function getParam($param)
    {
        return $_GET[$param] ?? $_POST[$param];
    }
}