<?php

namespace App;

/**
 * Response class
 * 
 * This class is responsible for outputting the data in JSON format.
 * It also sets the headers for the response.
 *  
 * @return response headers and data in JSON format
 * @author Ghulam Hassan Hassani <w20017074@northumbria.ac.uk>
 */
class Response
{
    public function __construct()
    {
        $this->outputHeaders();
        
        if (Request::method() == "OPTIONS") {
            exit();
        }
    }

    private function outputHeaders()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    public function outputJSON($data)
    {
        if (empty($data)) {
            http_response_code(204);
            $data = ['message' => 'No data found'];
        } else {
            echo json_encode($data);
        }
    }
}

