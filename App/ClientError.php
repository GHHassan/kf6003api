<?php

namespace App;

/**
 * ClientError class to handle any errors by the client.
 * 
 * This is a subclass of the Exception class and used to 
 * handle client request errors.
 * 
 * @return Error message based on the error code.
 * @author H Hassani <w20017074@northumbria.ac.uk>
 */
class ClientError extends \Exception
{
    public function __construct($code, $message = '')
    {
        parent::__construct($this->errorResponses($code, $message ?? ""));
    }

    private function errorResponses($code, $message)
    {
        http_response_code($code);
        switch ($code) {
            case 204:
                $message = "No Content";
                break;
            case 400:
                $message .= " (Bad Request)";
                break;
            case 401:
                $message .= " (Unauthorized)";
                break;
            case 403:
                $message .= " (Forbidden)";
                break;
            case 404:
                $message .= " (Not Found)";
                break;
            case 405:
                $message .= " (Method Not Allowed)";
                break;
            case 409:
                $message .= " (Conflict)";
                break;
            case 422:
                $message .= " (Unprocessable Entity)";
                break;
            case 431;
                $message .= " (Request Header Fields Too Large)";
                break;
            case 500:
                $message .= " (Internal Server Error)";
                break;
            default:
                $message .= "Unknown Client Error";
        }
        return $message;
    }
}
