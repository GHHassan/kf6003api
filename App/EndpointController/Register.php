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
use App\ {
    ClientError,
    Database,
    Request
};
class Register extends Endpoint{
    private $sql;
    private $sqlParams = ['name', 'email', 'password'];
    private $password;
    private $email;
    private $name;
    private $db;
    private $allowedMethods = ['OPTION','POST'];


    public function __construct($data = ["message" => []])
    {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $this->checkAllowedParams(Request::params(), $this->sqlParams);
        $this->db = new Database(DB_USER_PATH);
        $this->initialiseSQL();
        if(Request::method() === 'POST'){
            $this->registerUser();
        }
        $data = $this->db->executeSql($this->sql, $this->sqlParams);
        $data['message'] = "success";
        $this->setData($data);
        Parent::__construct($data);
    }

    private function registerUser(){
        if(isset($_POST['email'])){
            $this->email = $this->sanitiseString($_POST['email']);
        }
        $sql = "SELECT email FROM account WHERE email = :email";
        $sqlParams = [
            ':email' => $this->email
        ];
        if(count($this->db->countRows($sql, $sqlParams)) > 0){
            throw new ClientError(409, "duplicate");
        }
    }
    protected function checkAllowedMethod($method, $allowedMethods = [])
    {
        if (!in_array($method, $allowedMethods)) {
            throw new ClientError(405);
        }
    }

    protected function sanitiseString($input)
    {
        if (isset($input) && !empty($input) && is_string($input)) {
            $result = htmlspecialchars($input);
            return $result;
        }
        return $input;
    }

    protected function initialiseSQL ()
    {
        if(isset($_GET['name']) && isset($_GET['email']) && isset($_GET['password'])){
            if($this->isValidEmail($_GET['email'])){
                $this->name = $this->sanitiseString($_GET['name']);
                $this->name = ucwords($this->name);
                $this->email = $this->sanitiseString($_GET['email']);
                $this->email = strtolower($this->email);
                $this->password = password_hash($_GET['password'], PASSWORD_DEFAULT);
            }
        }else{
            throw new ClientError(400);
        }
        $this->sql = "INSERT INTO account (name, email, password) VALUES (:name, :email, :password)";
        $this->sqlParams = [
            ':name' => $this->name,
            ':email' => $this->email,
            ':password' => $this->password
        ];

    }

    function isValidEmail($email) {
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    
        if (preg_match($pattern, $email)) {
            return true;
        } else {
            throw new ClientError(400, "Invalid email");
        }
    }
    
}