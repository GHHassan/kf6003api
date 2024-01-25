<?php

namespace App\EndpointController;

/**
 * Endpoint class
 * 
 * A blueprint for the other endpoints in this project
 * that inherit it. It is responsible for connecting to
 * the database and returning the data in JSON format.
 * It does not support any parameters.
 * Limits access to only GET requests. This only 
 * applies to the endpoints that inherit this class and
 * call the parent constructor. In its constructor,
 * this also includes the `initialise()` method that
 * is used by some of the child classes.
 * provides token validation and sanitisation methods
 * 
 * @package App\EndpointController
 * @author Hassan
 */

use App\{
    ClientError,
    Database,
    Requesthandler,
};
use Firebase\JWT\JWT;

class Endpoint
{
    private $sql;
    private $sqlParams;
    private $data;
    private $db;
    private $allowedMethods = [];
    private $userID;
    private $requestData;
    private $allowedParams = ['GET', 'OPTIONS', 'POST', 'PUT', 'DELETE'];

    public function __construct($data = ["message" => []])
    {
        $this->setData($data);
    }
    protected function checkAllowedMethod($methods, $allowedMethods = [])
    {
        if (!is_array($methods)) {
            // Handle the case when a single method is passed
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            if (!in_array($method, $allowedMethods)) {
                throw new ClientError(405, "Method Not Allowed");
            }
        }
    }


    protected function sanitiseNum($input)
    {
        if (isset($input) && is_numeric($input)) {
            $result = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            return $result;
        }
        return $input;
    }

    protected function sanitiseString($input)
    {
        if (isset($input) && !empty($input) && is_string($input)) {
            $result = htmlspecialchars($input);
            return $result;
        }
        return $input;
    }

    protected function sanitiseEmail($input)
    {
        if (isset($input) && !empty($input) && is_string($input)) {
            $result = filter_var($input, FILTER_SANITIZE_EMAIL);
            return $result;
        }
        return $input;
    }
    protected function normaliseString($input)
    {
        if (isset($input) && !is_numeric($input)) {
            $result = strtolower($input);
            $result = ucwords($result);
            return $result;
        }
        return $input;
    }

    protected function checkAllowedParams($params, $allowedParams = [])
    {
        if (!is_array($params)) {
            // Handle the case when a single parameter is passed
            $params = [$params];
        }

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedParams)) {
                throw new ClientError(422, "Invalid parameter: $key");
            }
        }
    }
    protected function validateNumParam($num)
    {
        $sanitisedNum = $this->sanitiseNum($num);
        if (!is_numeric($sanitisedNum) || $sanitisedNum <= 0) {
            throw new ClientError(422);
        }
        return $sanitisedNum;
    }

    protected function validateToken()
    {
        $key = SECRET;
        $jwt = $this->getBearerToken();

        try {
            $decodedJWT = JWT::decode($jwt, new \Firebase\JWT\Key($key, 'HS256'));
            if ($decodedJWT->exp < time()) {
                throw new ClientError(402, 'Token expired');
            }
            if ($decodedJWT->iss !== $_SERVER['HTTP_HOST']) {
                throw new \UnexpectedValueException('Token not valid for this server');
            }
            return $decodedJWT->sub;
        } catch (\UnexpectedValueException $e) {
            throw new ClientError(401, $e->getMessage());
        }
    }

    protected function checkCredentials()
    {
        $sql = "SELECT id , password FROM account WHERE email = :email";
        $dbConn = new Database(DB_USER_PATH);
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            throw new ClientError(401, "Username or password is missing");
        }
        if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
            throw new ClientError(401, "Username or password is empty");
        }

        $sqlParams[":email"] = $_SERVER['PHP_AUTH_USER'];
        $data = $dbConn->executeSQL($sql, $sqlParams);
        if (count($data) < 1) {
            throw new ClientError(401, "Username or password is incorrect");
        }
        if (count($data) > 1) {
            throw new ClientError(500, "Please contact your admin");
        }
        if (!password_verify($_SERVER['PHP_AUTH_PW'], $data[0]['password'])) {
            throw new ClientError(401, "Username or password is incorrect");
        }
        return $data[0]['id'];
    }
    protected function getBearerToken()
    {
        $allHeaders = getallheaders();
        $authorizationHeader = "";

        if (array_key_exists('Authorization', $allHeaders)) {
            $authorizationHeader = $allHeaders['Authorization'];
        } elseif (array_key_exists('authorization', $allHeaders)) {
            $authorizationHeader = $allHeaders['authorization'];
        }

        if (substr($authorizationHeader, 0, 7) != 'Bearer ') {
            throw new ClientError(401);
        }

        return trim(substr($authorizationHeader, 7));
    }

    protected function setProperties()
    {
        if ((new RequestHandler())->getData() !== null) {
            $this->requestData = (new RequestHandler())->getData();
            foreach ($this->allowedParams as $param) {
                if (Requesthandler::hasParam($param)) {
                    $this->{$param} = Requesthandler::getParam($param);
                }
            }
        }
        if ($this->requestData !== null && isset($this->requestData['userID'])) {
            $this->userID = $this->requestData['userID'];
        } else if (isset($_GET['userID'])) {
            $this->userID = $_GET['userID'];
        }
    }

    protected function getUserID()
    {
        $db = new Database(DB_USER_PATH);
        if ($this->requestData !== null) {
            $sql = "SELECT userID FROM users WHERE email = :email";
            $sqlParams = [
                ':email' => $this->requestData['email']
            ];
            $data = $db->executeSql($sql, $sqlParams);
            if (count($data) === 0) {
                throw new ClientError(404, "User not found");
            }
            if (count($data) > 1) {
                throw new ClientError(500);
            }
            return $data[0]['userID'];
        } else {
            throw new ClientError(400, "No valid parameters provided for update");
        }
    }
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    public function getSQL()
    {
        return $this->sql;
    }

    public function getSQLParams()
    {
        return $this->sqlParams;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setSQLParams($sqlParams)
    {
        $this->sqlParams = $sqlParams;
    }

    protected function initialiseSQL()
    {
        $sql = "";
        $this->setSql($sql);
        $this->setSQLParams([]);
    }

    public function setAllowedParams($allowedParams)
    {
        $this->allowedParams = $allowedParams;
    }
}
