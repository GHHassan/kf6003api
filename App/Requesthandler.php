<?php
namespace App;

/**
 * Requesthandler
 * 
 * This class is responsible for handling http requests.
 * It parses the request body and returns the data in JSON format 
 * this enables the data parameters to be used in the endpoint classes
 * 
 * @package App
 * @author Hassan Hassani <w20017074@northumbria.ac.uk>
 * @generated
 */
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

    /**
     * Parses the request body and returns the data in JSON format
     * 
     * @return array
     * @throws \Exception
     */
    private function parseJsonRequestBody()
    {
        $rawData = file_get_contents("php://input");

        if ($rawData === false || empty($rawData)) {
            throw new \Exception('Request body is empty.');
        }

        $jsonData = json_decode($rawData, true);

        if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error parsing JSON request body: ' . json_last_error_msg());
        }

        $jsonData = $this->htmlEncodeArray($jsonData);

        return $jsonData;
    }

    /**
     * Encodes all string values in an array to HTML entities.
     * prevents XSS attacks
     * 
     * @param array $array
     * @return array
     */
    private function htmlEncodeArray($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->htmlEncodeArray($value);
            } else {
                $array[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }
        }

        return $array;
    }

    public static function hasParam($param)
    {
        return isset($_GET[$param]) || isset($_POST[$param]);
    }

    public static function getParam($param)
    {
        $value = $_GET[$param] ?? $_POST[$param] ?? null;
        return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
    }
}