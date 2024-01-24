<?php
namespace App\EndpointController;

/**
 * Profile
 * 
 * This class is responsible for handling requests to the /profile endpoint.
 * 
 * Get: accepts a userID in the url and returns the profile data for that user
 * 
 * POST and PUT: accepts json Object with the following properties:
 * - properites in the allowedParams array.
 * post creates a new profile for the user
 * put updates the profile for the user
 * DELETE: accepts a userID in the url and deletes the profile for that user
 * 
 */
use App\{
    ClientError,
    Database,
    Request,
    Requesthandler
};

class Profile extends Endpoint
{
    private $db;
    private $userID;
    private $data;
    private $requestData;
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    private $allowedParams = [
        'firstname',
        'lastname',
        'dateofbirth',
        'gender',
        'email',
        'phonenumber',
        'bio',
        'address',
        'profilepicturepath',
        'coverpicturepath',
        'relationshipstatus',
        'website',
        'joineddate',
        'profilevisibility',
        'emailvisibility',
        'phonenumbervisibility',
        'addressvisibility',
        'relationshipstatusvisibility',
        'gendervisibility',
        'dateofbirthvisibility'
    ];

    public function __construct()
    {
        $this->requestData = (new RequestHandler())->getData();
        $this->setProperties();
        // $this->userID = $_GET['userID'];
        $data = [];
        if (Request::method() === 'GET') {
            $data = $this->getUserProfile($this->userID);
        }
        if (Request::method() === 'POST') {
            $data = $this->createProfile($this->requestData, $this->userID);
        }
        if (Request::method() === 'PUT') {
            // $this->requestData = (new RequestHandler())->getData();
            $data = $this->updateProfile($this->requestData, $this->userID);
        }
        if (Request::method() === 'DELETE') {
            $data = $this->deleteProfile($this->userID);
        }
        $data['message'] = "success";
        parent::__construct($data);
    }

    private function getUserProfile($userID)
    {
        $db = new Database(DB_PATH);
        $sql = "SELECT * FROM Profile WHERE userID = :userID";
        $sqlParams = [':userID' => $userID];
        $result = $db->executeSql($sql, $sqlParams);
        if (count($result) === 0) {
            throw new ClientError(404, "Profile not found");
        }
        return $result;
    }


    private function createProfile($requestData, $userID = null)
    {
        $db = new Database(DB_PATH);
        $updateFields = [];
        foreach ($this->allowedParams as $param) {
            if (isset($requestData[$param])) {
                $updateFields[$param] = $param;
            }
        }

        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for update");
        }

        $sql = "INSERT INTO Profile (";
        $sql .= implode(', ', array_keys($requestData));
        $sql .= ", userID) VALUES (";

        // Construct the parameter placeholders
        $placeholders = array_map(
            function ($column) {
                return ":$column";
            },
            array_keys($requestData)
        );

        $sql .= implode(', ', $placeholders);
        $sql .= ", :userID)";

        // Prepare the SQL parameters
        $sqlParams = array_merge($requestData, [':userID' => $userID]);

        $result = $db->executeSql($sql, $sqlParams);
        if ($result['message'] = 'succes')
            return $result;
        else
            throw new ClientError(500);
    }

    private function updateProfile($requestData, $userID = null)
    {
        $db = new Database(DB_PATH);

        // Extract columns and values to update from $requestData
        $updateFields = [];
        foreach ($this->allowedParams as $param) {
            if (isset($requestData[$param])) {
                $updateFields[$param] = $param;
            }
        }

        // Check if there are valid parameters for update
        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for update");
        }

        // Construct the UPDATE query
        $sql = "UPDATE Profile SET ";

        // Build the SET part of the query with column = :column placeholders
        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            array_keys($updateFields)
        );

        $sql .= implode(', ', $setClauses);

        // Add the WHERE condition for the userID
        $sql .= " WHERE userID = :userID";

        // Prepare the SQL parameters
        $sqlParams = [];
        foreach ($updateFields as $param) {
            $sqlParams[":$param"] = $requestData[$param];
        }
        $sqlParams[':userID'] = $userID;
        echo $sql . "\n";
        echo json_encode($sqlParams) . "\n";
        // Execute the SQL query
        $result = $db->executeSql($sql, $sqlParams);
        $result['message'] = 'success';
        if ($result['message'] === 'success') {
            return $result;
        } else {
            throw new ClientError(500);
        }
    }

    private function getUserID()
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

    private function deleteProfile($userID)
    {
        $db = new Database(DB_PATH);
        $sql = "DELETE FROM Profile WHERE userID = :userID";
        $sqlParams = [':userID' => $userID];
        $result = $db->executeSql($sql, $sqlParams);
        if ($result['message'] === 'success') {
            return $result;
        } else {
            throw new ClientError(500);
        }
    }
    private function setProperties()
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
}