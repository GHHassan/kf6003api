<?php

namespace App\EndpointController;

/**
 * Profile profile update test
 * 
 * This class is responsible for handling requests to the /profile endpoint.
 * All parameters should be passed via the http request body in JSON format.
 * 
 * allowed properites are given in allowedParams array see class properties.
 * User ID is required for all methods except POST.
 * 
 * HTTP Request Methods:
 * 
 * GET returns the profile for the user based on the userID
 * POST creates a new profile for the user based on the parameters passed
 * - Required parameters are userID, username, firstName, lastName, dateOfBirth, email
 * PUT updates the profile for the user based on the userID and the parameters passed
 * DELETE: Deletes a profile based on the userID
 * 
 * @package App\EndpointController
 * @author Hassan <w20017074@northumbria.ac.uk>
 */
use App\{
    ClientError,
    Database,
    Request
};

class Profile extends Endpoint
{
    private $db;
    private $userID;
    private $data;
    protected $requestData;
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    private $allowedParams = [
        'userID',
        'username',
        'firstName',
        'lastName',
        'dateOfBirth',
        'gender',
        'email',
        'phoneNumber',
        'bio',
        'address',
        'profilePicturePath',
        'coverPicturePath',
        'relationshipStatus',
        'website',
        'joinedDate',
        'profileVisibility',
        'emailVisibility',
        'phoneNumberVisibility',
        'addressVisibility',
        'relationshipStatusVisibility',
        'genderVisibility',
        'dateOfBirthVisibility'
    ];

    public function __construct()
    {
        $this->db = new Database(DB_PATH);
        $this->handleRequest();        
        parent::__construct($this->getData());
    }

    protected function performAction()
    {
        $data = [];
        switch(Request::method()) {
            case 'GET':
                $data = $this->getUserProfile();
                break;
            case 'POST':
                $data = $this->createProfile();
                break;
            case 'PUT':
                $data = $this->updateProfile();
                break;
            case 'DELETE':
                $data = $this->deleteProfile();
                break;
        }
        $this->setData($data);
    }

    private function getUserProfile()
    {
        if(!isset($this->requestData['userID'])) {
            throw new ClientError(422, "userID is required");
        }
        $sql = "SELECT * FROM Profile WHERE userID = :userID";
        $sqlParams = [':userID' => $this->requestData['userID']];
        $result = $this->db->executeSql($sql, $sqlParams);

        count($result) > 0 ? $result['message'] = 'success' : $result['message'] = 'not found'; 
        return $result;
    }

    private function createProfile()
    {
        $db = new Database(DB_PATH);
        $requiredParams = [
            'userID',
            'username',
            'firstName',
            'lastName',
            'dateOfBirth',
            'email'
        ];

        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "One or more required parameters are missing.");
            }
        }

        // Construct dynamic INSERT query
        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, array_keys($this->requestData)));

        $sql = "INSERT INTO Profile (" . implode(', ', array_keys($this->requestData)) .
            ") VALUES ( $placeholders)";
        $sqlParams = array_merge(array_values($this->requestData));

        if (isset($this->requestData['userID']) && $this->requestData['userID'] !== null) {
            if (!$this->profileExists($this->requestData['userID'])) {
                $result = $db->executeSql($sql, $sqlParams);
                $result['message'] = 'success';
                if ($result['message'] === 'success') {
                    return $result;
                }
            }
            throw new ClientError(403, "user already exists");
        }
    }

    private function updateProfile()
    {
        $db = new Database(DB_PATH);

        // Extract columns and values from $requestData
        $updateFields = [];
        foreach ($this->allowedParams as $param) {
            if (isset($this->requestData[$param])) {
                $updateFields[$param] = $param;
            }
        }
        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for update");
        }

        // Construct the UPDATE query
        $sql = "UPDATE Profile SET ";
        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            array_keys($updateFields)
        );

        $sql .= implode(', ', $setClauses);
        $sql .= " WHERE userID = :userID";

        // Prepare the SQL parameters
        $sqlParams = [];
        foreach ($updateFields as $param) {
            $sqlParams[":$param"] = $this->requestData[$param];
        }
        $sqlParams[':userID'] = $this->requestData['userID'];
       
        $data = $db->executeSql($sql, $sqlParams);
        count($data) > 0 ? $data['message'] = 'success' : $data['message'] = 'failed';
        return $data;
    }

    private function deleteProfile()
    {
        $db = new Database(DB_PATH);
        $sql = "DELETE FROM Profile WHERE userID = :userID";
        $sqlParams = [':userID' => $this->requestData['userID']];
        $result = $db->executeSql($sql, $sqlParams);
        $result['message'] = 'success';
        if ($result['message'] === 'success') {
            return $result;
        } else {
            throw new ClientError(500);
        }
    }

    private function profileExists($userID)
    {
        $db = new Database(DB_PATH);
        $sql = "SELECT userID FROM Profile WHERE userID = :userID";
        $sqlParams = [':userID' => $userID];
        $data = $db->executeSql($sql, $sqlParams);
        if (count($data) > 1) {
            throw new ClientError(500);
        }
        if (count($data) === 0) {
            return false;
        }
        return true;
    }
}