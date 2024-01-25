<?php

namespace App\EndpointController;

/**
 * Class Post
 * 
 * This class is responsible for handling the requests to the post endpoint.
 * It can handle GET, POST, PUT and DELETE requests.
 * 
 * Parameters: should be passed as JSON in the body of the HTTP request
 * 
 * HTTP Request Methods:
 * GET:    Get a post by postID, userID, both or no parameters for all posts
 * POST:   Create a new post Requires userID, username, and at least one of the optional parameters (textContent, location, photoPath, videoPath, visibility)
 * PUT:    Update a post Requires userID, postID, and at least one of the optional parameters (textContent, photoPath, videoPath, visibility, username)
 * DELETE: Delete a post Requires postID
 * 
 * 
 * @package App\EndpointController
 * @author Hassan <w20017074@northumbria.ac.uk>
 */

use App\{
    ClientError,
    Database,
    Request,
    Requesthandler
};

class Post extends Endpoint
{
    private $db;
    private $postID;
    private $userID;
    private $textContent;
    private $photoPath;
    private $videoPath;
    private $visibility;
    private $requestData;

    private $allowedParams = [
        'textContent',
        'photoPath',
        'videoPath',
        'visibility',
        'location',
        'postID',
        'userID',
        'username'
    ];
    private $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE'
    ];

    public function __construct()
    {
        $this->db = new Database(DB_PATH);
        $this->setProperties();
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $data = [];
        switch (Request::method()) {
            case 'GET':
                $data = $this->get();
                break;
            case 'POST':
                $data = $this->post();
                break;
            case 'PUT':
                $data = $this->updatePost();
                break;
            case 'DELETE':
                $data = $this->delete();
                break;
        }
        parent::__construct($data);
    }

    //=================================================================================================
    //=================================================================================================
    public function get()
    {
        $db = new Database(DB_PATH);
        if(isset($this->requestData['postID']) && isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM post WHERE postID = :postID AND userID = :userID";
            $sqlParams = [':postID' => $this->requestData['postID'], ':userID' => $this->requestData['userID']];
            $data = $db->executeSQL($sql, $sqlParams);
            if(count($data) === 0) {
                $data['message'] = "No post found";
            }
            return $data;
        }

        if (isset($this->requestData['postID'])) {
            $sql = "SELECT * FROM post WHERE postID = :postID";
            $sqlParams = [':postID' => $this->requestData['postID']];
            return $db->executeSQL($sql, $sqlParams);
        }
        if (isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM post WHERE userID = :userID";
            $sqlParams = [':userID' => $this->requestData['userID']];
            return $db->executeSQL($sql, $sqlParams);
        }
        $sql = "SELECT * FROM post";
        $sqlParams = [];
        return $db->executeSQL($sql, $sqlParams);

    }
    //=================================================================================================
    //=================================================================================================
    public function post()
    {
        // Ensure compulsory parameters are provided
        $requiredParams = ['userID', 'username'];
        $optionalParams = ['textContent', 'location', 'photoPath', 'videoPath', 'visibility'];

        // Check if at least one of the specified optional parameters is provided
        $providedOptionalParams = array_intersect($optionalParams, array_keys($this->requestData));
        if (empty($providedOptionalParams)) {
            throw new ClientError(422, "At least one of the optional parameters ('textContent', 'location', 'photoPath', 'videoPath', 'visibility') is required");
        }

        // Merge required and optional parameters
        $allParams = array_merge($requiredParams, $optionalParams);

        // Check and sync SQL placeholders with allowed params
        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        if (empty($providedPropertyKeys)) {
            throw new ClientError(422, "At least one property of the post is required");
        }

        // Extract valid parameters for update
        $postFields = array_intersect($providedPropertyKeys, $allParams);

        if (empty($postFields)) {
            throw new ClientError(400, "No valid parameters provided for update");
        }

        // Set default values for optional parameters
        $defaultValues = [
            'textContent' => null,
            'location' => null,
            'photoPath' => null,
            'videoPath' => null,
            'visibility' => 'friends',
        ];

        // Merge requestData with default values
        $postParams = array_merge($defaultValues, $this->requestData);

        // Assign 'friends' visibility for null values in optional parameters
        $postParams['visibility'] = ($postParams['visibility'] === null) ? 'friends' : $postParams['visibility'];

        // Ensure that only the required and optional parameters are used in the SQL query
        $validParams = array_intersect_key($postParams, array_flip($allParams));

        // Construct dynamic placeholders for the INSERT query
        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, array_keys($validParams)));

        // Construct the INSERT query with dynamic placeholders
        $sql = "INSERT INTO post (" . implode(', ', array_keys($validParams)) . ") VALUES ($placeholders)";

        // Execute the SQL query
        $data = $this->db->executeSQL($sql, $validParams);
        $data['message'] = "success";
        return $data;
    }
    //=================================================================================================
    //=================================================================================================

    public function updatePost()
    {
        // Ensure that 'userID', 'postID', and at least one of the properties are compulsory
        $requiredParams = ['userID', 'postID'];
        $optionalParams = ['textContent', 'photoPath', 'videoPath', 'visibility', 'username', 'location'];

        if (array_intersect($requiredParams, array_keys($this->requestData)) !== $requiredParams) {
            throw new ClientError(422, "At least one of the required parameters ('userID', 'postID') is required");
        }
        // Check if at least one of the specified optional parameters is provided
        $providedOptionalParams = array_intersect($optionalParams, array_keys($this->requestData));
        if (empty($providedOptionalParams)) {
            throw new ClientError(422, "At least one of the optional parameters ('textContent', 'photoPath', 'videoPath', 'visibility', 'username') is required");
        }

        // Merge required and optional parameters
        $allParams = array_merge($requiredParams, $optionalParams);

        // Check and sync SQL placeholders with allowed params
        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        if (empty($providedPropertyKeys)) {
            throw new ClientError(422, "At least one property of the post is required");
        }

        echo json_encode($providedPropertyKeys) . "\n";
        // Extract valid parameters for update
        $updateFields = array_intersect($providedPropertyKeys, $allParams);

        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for update");
        }

        // Construct the UPDATE query
        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            $updateFields
        );

        $sql = "UPDATE post SET " . implode(', ', $setClauses);
        // Add the WHERE condition for UserID and PostID
        $sql .= " WHERE userID = :userID AND postID = :postID";

        // Create an array for SQL parameters
        $sqlParams = [];

        // Iterate through the keys in $updateFields and fetch corresponding values
        foreach ($updateFields as $property) {
            // Check if the property exists in $this->requestData before accessing it
            if (isset($this->requestData[$property])) {
                // Assign the value to the $sqlParams array
                $sqlParams[":$property"] = $this->requestData[$property];
            }
        }
        // Execute the SQL query
        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    //=================================================================================================
    //=================================================================================================
    public function delete()
    {
        if (!isset($this->requestData['postID'])) {
            throw new ClientError(422, "PostID is required");
        }
        $sql = "DELETE FROM post WHERE postID = :postID";
        $sqlParams = [':postID' => $this->postID];
        $data[] = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    protected function setProperties()
    {
        if ((new Requesthandler())->getData() !== null) {
            $this->requestData = (new Requesthandler())->getData();
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