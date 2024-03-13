<?php

namespace App\Auth;

/**
 * Token endpoint
 * 
 * This endpoint is used to generate a JWT token 
 * if the user provides a valide credentials for the user.
 * The token is valid for 1 hour. and the user can use it
 * to access restricted personalised materials to them.
 * inherits validateToken() and more from the Endpoint class and uses the
 * 
 * @package App\Auth
 * @return JWT token if the user provides a valid credentials
 * 
 * @author John Rooksby <john.rooksby@northumbria.ac.uk>
 * @author Hassan <w20017074>
 * 
 */

use Firebase\JWT\JWT;
use App\Request;

class Token extends \App\EndpointController\Endpoint 
{
    private $allowedMethods = ['GET','POST'];
    private $sqlParams = [];
    private $data = [];

    public function __construct() {
        $this->checkAllowedParams(Request::params(), $this->allowedParams);
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $id = $this->checkCredentials();
        $data['token'] = $this->generateJWT($id);
        $data['message'] = 'success';
        parent::__construct($data);
    }

    private function generateJWT($token) {
        $secretKey = SECRET;
        $id = $token['userID'];
        $username = $token['username'];
        $iat = time();
        $iss = $_SERVER['HTTP_HOST'];
        $email = $_SERVER['PHP_AUTH_USER'];
        $payload = [
            'sub' => $id,
            'email' => $email,
            'username' => $username,
            'iat' => $iat,
            'exp' => $iat + (3600 * 6),
            'iss' => $iss
        ];
        $jwt = JWT::encode($payload, $secretKey, 'HS256');
        return $jwt;
    }
}