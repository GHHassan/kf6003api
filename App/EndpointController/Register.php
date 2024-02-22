<?php

namespace App\EndpointController;

/**
 * Register class
 * 
 * This class is used to validate user input and inserts a
 * new user record on account table of the users database
 * 
 * @author Ghulam Hassan hassani
 */
use App\{
    ClientError,
    Database,
    Request
};

class Register extends Endpoint
{
    private $sql;
    private $sqlParams;
    private $password;
    private $email;
    private $name;
    private $db;
    private $userID;
    private $allowedMethods = ['OPTION', 'POST'];


    public function __construct($data = ["message" => []])
    {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $this->checkAllowedParams(Request::params(), $this->sqlParams);
        $this->db = new Database(DB_USER_PATH);
        $data ;
        if (Request::method() === 'POST') {
           $data = $this->registerUser();
        }
        parent::__construct($data);
    }

    private function registerUser()
    {
        if (isset($_POST['email'])) {
            $this->email = $this->sanitiseString($_POST['email']);
        }
        $sql = "SELECT email FROM users WHERE email = :email";
        $sqlParams = [
            ':email' => $this->email
        ];

        if (count($this->db->countRows($sql, $sqlParams)) > 0) {
            throw new ClientError(409, "duplicate");
        }

        $this->initialiseSQL();
        return $this->db->executeSql($this->sql, $this->sqlParams);
    }

    protected function initialiseSQL()
    {
        // Get the raw JSON data from the request body
        $jsonData = file_get_contents("php://input");

        $data = json_decode($jsonData, true); 

        // Check for the presence of required keys
        if (isset($data['username']) && isset($data['email']) && isset($data['password_hash'])) {
            if ($this->isValidEmail($data['email'])) {
                isset($data['userID']) ? $this->userID = $data['userID'] : $this->userID = $this->generateUserID();
                $this->name = ucwords($this->sanitiseString($data['username']));
                $this->email = strtolower($this->sanitiseEmail($data['email']));
                $this->password = password_hash($data['password_hash'], PASSWORD_DEFAULT);
            } else {
                // Invalid email format
                throw new ClientError(422, 'Invalid email format');
            }
        } else {
            // Missing required data
            throw new ClientError(422, 'Missing required data');
        }

        $this->sql = "INSERT INTO users (userID, username, email, password_hash) VALUES (:userID:, username, :email, :password_hash)";
        $this->sqlParams = [
            ":userID"=> $this->userID,
            ':username' => $this->name,
            ':email' => $this->email,
            ':password_hash' => $this->password
        ];
    }
    function isValidEmail($email)
    {
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        if (preg_match($pattern, $email)) {
            return true;
        } else {
            throw new ClientError(400, "Invalid email");
        }
    }

    private function generateUserID()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $userID = '';
        for ($i = 0; $i < 10; $i++) {
            $userID .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $userID;
    }
}