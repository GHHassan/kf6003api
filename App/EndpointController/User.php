<?php

namespace App\EndpointController;

/**
 * Register class
 * 
 * This class is used to fetch user data from the users database
 * 
 * @author 
 */

use App\ {
    ClientError,
    Database,
    Request
};
class User extends Endpoint
{
    private $sql;
    private $sqlParams = ['username', 'email', 'password'];
    private $password;
    private $email;
    private $username;
    private $db;
    private $allowedMethods = ['OPTION', 'POST'];

    public function __construct($data = ["message" => []])
    {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $this->checkAllowedParams(Request::params(), $this->sqlParams);
        $this->db = new Database(DB_USER_PATH);     
        $data = $this->getUser();
        $data['message'] = "success";
        $this->setData($data);
        Parent::__construct($data);
    }

    private function getUser()
    {
        if (isset($_GET['email'])) {
            $this->email = $this->sanitiseEmail($_GET['email']);
        }
        $sql = "SELECT * FROM users WHERE email = :email";
        $sqlParams = [
            ':email' => $this->email
        ];
        $data = $this->db->executeSql($sql, $sqlParams);
        if (count($data) === 0) {
            throw new ClientError(404, "not found");
        }
        if (count($data) > 1) {
            throw new ClientError(500);
        }
        return $data;
    }
}