<?php

namespace App\EndpointController;

/**
 * Friendship
 * 
 * This class is responsible for handling requests to the /friendship endpoint.
 * All parameters should be passed via the http request body in JSON format.
 * 
 * allowed properites are given in allowedParams array see class properties.
 * User ID is required for all methods except POST.
 * 
 * HTTP Request Methods:
 * GET: with userID: returns list of friends for a given user
 * with UserID and status: returns list of friends with a given status for a given user
 * status can be either 'accepted' or 'pending' or 'rejected'
 * 
 * POST: requires userID1 and userID2: sends a friend request from one user to another
 * 
 * PUT: requires connectionID and status: responds to a friend request
 * status can be either 'accepted' or 'rejected'
 * 
 * DELETE: requires connectionID: removes a friend
 * 
 * @author Hassan Hassani <w20017074"northumbria.ac.uk>
 * 
 */

use App\{
    ClientError,
    Database,
    Request,
    Requesthandler
};

class Friendship extends Endpoint
{
    private $db;
    private $allowedMethods = ['POST', 'PUT', 'DELETE'];
    private $allowedParams = ['userID1', 'userID2', 'status', 'connectionID'];
    public function __construct()
    {
        $this->db = new Database(DB_PATH);
        $this->requestData = (new Requesthandler())->getData();
        $this->handleRequest();
        parent::__construct($this->getData());
    }

    protected function performAction()
    {
        $data = [];
        switch (Request::method()) {
            case 'GET':
                $data = $this->getFriends();
                break;
            case 'POST':
                $data = $this->sendFriendRequest();
                break;
            case 'PUT':
                $data = $this->respondToFriendRequest();
                break;
            case 'DELETE':
                $data = $this->removeFriend();
                break;
        }
        $this->setData($data);
    }

    /**
     * getFriends
     * 
     * This method is responsible for returning a list of friends for a given user.
     * if status is provided, only friends with that status will be returned.
     * else only friends with status 'accepted' will be returned.
     * 
     * userID to be provided in the request body.
     * status parameter is optional.
     * 
     * @return array
     */
    private function getFriends()
    {
        $requiredParams = ['userID'];
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "One or more required parameters are missing.");
            }
        }

        if (isset($this->requestData['status'])) {
            $sql = "SELECT * FROM Friends 
                    WHERE (userID1 = :userID OR userID2 = :userID) AND status = :status";
            $sqlParams = [
                ':userID' => $this->requestData['userID'],
                ':status' => $this->requestData['status']
            ];
        } else {
            $sql = "SELECT * FROM Friends 
                WHERE (userID1 = :userID OR userID2 = :userID) AND status = 'accepted'";
            $sqlParams = [':userID' => $this->requestData['userID']];
        }
        $result = $this->db->executeSql($sql, $sqlParams);
        if(count($result) > 0){
            $result['message'] = 'success';
        }
        return $result;
    }

    /**
     * sendFriendRequest
     * 
     * This method is responsible for sending a friend request from one user to another.
     * requires userID1 and userID2 to be provided in the request body.
     * @return array
     */
    private function sendFriendRequest()
    {
        $requiredParams = ['userID1', 'userID2'];
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "One or more required parameters are missing.");
            }
        }

        $sql = "INSERT INTO Friends (userID1, userID2, status, connectionDateTime) 
                VALUES (:userID1, :userID2, 'pending', CURRENT_TIMESTAMP)";
        $sqlParams = [
            ':userID1' => $this->requestData['userID1'],
            ':userID2' => $this->requestData['userID2']
        ];

        $result = $this->db->executeSql($sql, $sqlParams);
        count($result) > 0 ? $result['message'] = 'success' : $result['message'] = 'failed';
        return $result;
    }

    /**
     * respondToFriendRequest
     * 
     * This method is responsible for responding to a friend request.
     * requires connectionID and status to be provided in the request body.
     * status can be either 'accepted' or 'rejected'.
     * status will be converted to lowercase for consistency.
     * 
     * @return array
     */
    private function respondToFriendRequest()
    {
        $requiredParams = ['connectionID', 'status'];

        foreach ($requiredParams as $param) {
            if ($param === 'status') {
                $this->requestData[$param] = strtolower($this->requestData[$param]);
            }
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "One or more required parameters are missing.");
            }
        }

        $sql = "UPDATE Friends SET status = :status WHERE connectionID = :connectionID";
        $sqlParams = [
            ':status' => $this->requestData['status'],
            ':connectionID' => $this->requestData['connectionID']
        ];

        $result = $this->db->executeSql($sql, $sqlParams);
        count($result) > 0 ? $result['message'] = 'success' : $result['message'] = 'failed';
        return $result;
    }

    private function removeFriend()
    {
        $requiredParams = ['connectionID'];
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "One or more required parameters are missing.");
            }
        }

        $sql = "DELETE FROM Friends WHERE connectionID = :connectionID";
        $sqlParams = [':connectionID' => $this->requestData['connectionID']];

        $result = $this->db->executeSql($sql, $sqlParams);
        count($result) > 0 ? $result['message'] = 'success' : $result['message'] = 'failed';
        return $result;
    }
}
