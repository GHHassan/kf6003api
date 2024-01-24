<?php

namespace App;

/**
 * Router class
 * 
 * This abstract class is responsible for routing incoming requests to the appropriate endpoint.
 * It uses the endpoint name from the request to determine which endpoint to instantiate.
 * If the endpoint is not found, it catches a `ClientError` exception.
 * 
 * @package App
 */
abstract class Router
{
    public static function routeRequest()
    {
        try {
            switch (strtolower(Request::endpointName())) {
                case '/':
                case 'developer':
                case '/developer':
                    $endpoint = new EndpointController\Developer();
                    break;
                case 'post':
                case '/post':
                    $endpoint = new EndpointController\Post();
                    break;
                case 'user':
                case '/user':
                    $endpoint = new EndpointController\User();
                    break;
                case 'token':
                case '/token':
                    $endpoint = new Auth\Token();
                    break;
                case 'notes':
                case '/notes':
                    $endpoint = new EndpointController\Note();
                    break;
                case 'register':
                case '/register':
                    $endpoint = new EndpointController\Register();
                    break;
                case 'profile':
                case '/profile':
                    $endpoint = new EndpointController\Profile();
                    break;
                case 'requesthandler':
                case '/requesthandler':
                    $endpoint = new EndpointController\Requesthandler();
                    break;
                default:
                    throw new ClientError(404, 'check your url and try again');
            }
        } catch (ClientError $e) {
            $data = ['message' => $e->getMessage()];
            $endpoint = new EndpointController\Endpoint($data);
        }
        return $endpoint;
    }
}
