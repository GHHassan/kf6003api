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
                case 'token':
                case '/token':
                    $endpoint = new Auth\Token();
                    break;
                case 'reactions':
                case '/reactions':
                    $endpoint = new EndpointController\Reaction();
                    break;
                case 'register':
                case '/register':
                    $endpoint = new EndpointController\Register();
                    break;
                case 'ssouser':
                case '/ssouser':
                    $endpoint = new EndpointController\Ssouser();
                    break;
                case 'profile':
                case '/profile':
                    $endpoint = new EndpointController\Profile();
                    break;
                case 'friendship':
                case '/friendship':
                    $endpoint = new EndpointController\Friendship();
                    break;
                case 'comment':
                case '/comment':
                    $endpoint = new EndpointController\Comment();
                    break;
                case 'upload':
                case '/upload':
                    $endpoint = new EndpointController\Upload();
                    break;
                case 'server':
                case '/server':
                    return new WebSocket\WebSocketServer();
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
