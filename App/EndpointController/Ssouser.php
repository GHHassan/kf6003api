<?php

namespace App\EndpointController;

/**
 * Ssouser
 * 
 * This class is listening to the SSO events, it 
 * listens to the user.created, user.updated and user.deleted
 * events and updates the users table in the database accordingly.
 * 
 * The url of this endpoint is passed to the Clerk API as a webhook.
 * and any event that happens in the SSO is sent to this endpoint.
 * and the data is updated in the users table in the database accordingly.
 * 
 * @author Hassan Hassani <w20017074@northumbria.ac.uk>
 * @package App\EndpointController
 * 
 */

use App\{
    Requesthandler,
    ClientError
};
class Ssouser extends Endpoint
{
    private $allowedParams = ['GET','POST'];
    private $sqlParams = [];
    private $data = [];
    private $db;

    public function __construct() {
        $this->db = new \App\Database(DB_USER_PATH);
        $this->recordData();
    }

    /** retrieves data from body params of httpRequest */
    private function getData() {
        $data = (new Requesthandler())->getData();
        return $data;
    }

    private function recordData() {
        $data = $this->getData();
        switch($data['type']){
            case "user.created":
                $sql = "INSERT INTO users (userID, email, password_hash) VALUES (:userID, :email, :password_hash)";
                $username = $data['username'] ?? $data['data']['first_name'] . $data['data']['last_name'];  
                $this->sqlParams = [
                    ':userID' => $data['data']['id'],
                    ':email' => $data['data']['email_addresses'][0]['email_address'],
                    ':password_hash' => 'SSO',
                ];
                $data = $this->db->executeSql($sql, $this->sqlParams);
                count($data) > 0 ? $data = ['message' => 'success'] : $data = ['message' => 'failed'];
                $this->setData($data);
                break;
            case "user.updated":
                $sql = "UPDATE users SET email = :email, password_hash = :password_hash WHERE userID = :userID";
                $this->sqlParams = [
                    ':userID' => $data['data']['primary_email_address_id'],
                    ':email' => $data['data']['email_addresses']['email_address'],
                    ':username' => $data['data']['first_name'] . $data['data']['last_name'],
                    ':password_hash' => 'SSO',
                ];
                $data = $this->db->executeSql($sql, $this->sqlParams);
                count($data) > 0 ? $data = ['message' => 'success'] : $data = ['message' => 'failed'];
                $this->setData($data);
                break;
            case "user.deleted":
                $sql = "DELETE FROM users WHERE userID = :userID";
                $this->sqlParams = [
                    ':userID' => $data['data']['id'],
                ];
                $data = $this->db->executeSql($sql, $this->sqlParams);
                count($data) > 0 ? $data = ['message' => 'success'] : $data = ['message' => 'failed'];
                $this->setData($data);
                break;
            default:
                throw new ClientError(422, 'Invalid SSO event type');
        }
        if(count($this->getData()) > 0) {
            $this->data['message'] = 'success';
            return $this->getData();
        } else {
            throw new ClientError(422, 'Invalid SSO event type');
        }
    }
}
