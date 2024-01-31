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
 * GET:    Get a post by postID, userID and visibility both or no parameters for all posts
 * POST:   Create a new post Requires userID, username, and at least one of the optional parameters
 *  (textContent, location, photoPath, videoPath, visibility)
 * PUT:    Update a post Requires userID, postID, and at least one of the optional parameters 
 * (textContent, photoPath, videoPath, visibility, username)
 * DELETE: Delete a post Requires postID
 * 
 * 
 * @package App\EndpointController
 * @author Hassan <w20017074@northumbria.ac.uk>
 */

use App\{
    ClientError,
    Database,
    Request
};

class Post extends Endpoint
{
    private $db;
    private $allowedParams = ['textContent', 'photoPath', 'videoPath', 'visibility', 'location', 'postID', 'userID', 'username'];
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];

    public function __construct()
    {
        $this->db = new Database(DB_PATH);
        $this->handleRequest();
    }

    protected function performAction()
    {
        $data = $this->executeAction(Request::method());
        parent::__construct($data);
    }

    private function executeAction($method)
    {
        switch ($method) {
            case 'GET':
                return $this->get();
            case 'POST':
                return $this->post();
            case 'PUT':
                return $this->updatePost();
            case 'DELETE':
                return $this->delete();
            default:
                throw new ClientError(405, "Method Not Allowed");
        }
    }

    /**
     * This method is responsible for handling GET requests to the post endpoint.
     * It can handle GET requests with optional postID, userID and visibility, both,
     * or no parameters for all posts with public visibility.
     *
     * @return array
     * @throws ClientError
     */
    public function get()
    {
        $db = new Database(DB_PATH);

        !empty($this->requesData) ?? $postFields = $this->validateParams([],['postID', 'userID', 'visibility']);

        $sql = "SELECT * FROM post WHERE visibility = 'public'";
        $sqlParams = [];

        if (!empty($postFields)) {
            $sql .= " WHERE " . implode(' AND ', array_map(function ($field) {
                return "$field = :$field";
            }, $postFields));

            $sqlParams = array_intersect_key($this->requestData, array_flip($postFields));
        }

        $data = $db->executeSQL($sql, $sqlParams);

        $data['message'] = (count($data) === 0 ? "No post found" : "success");

        return $data;
    }

    /**
     * This method is responsible for handling POST requests to the post endpoint.
     *
     * It can handle POST requests. Requires: userID, username, and at least one of
     * the optional parameters (textContent, location, photoPath, videoPath, visibility)
     * sets default values for optional parameters if not provided
     * visibility defaults to 'friends' only
     *
     * @return array
     * @throws ClientError
     */
    public function post()
    {
        $postFields = $this->validateParams(['userID', 'username'], ['textContent', 'location', 'photoPath', 'videoPath', 'visibility']);

        $defaultValues = [
            'textContent' => null,
            'location' => null,
            'photoPath' => null,
            'videoPath' => null,
            'visibility' => 'friends',
        ];

        $postParams = array_merge($defaultValues, $this->requestData);
        $postParams['visibility'] = $postParams['visibility'] ?? 'friends';

        $validParams = array_intersect_key($postParams, array_flip($postFields));

        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, array_keys($validParams)));

        $sql = "INSERT INTO post (" . implode(', ', array_keys($validParams)) . ") VALUES ($placeholders)";

        $data = $this->db->executeSQL($sql, $validParams);
        count($data) > 0 ? $data['message'] = "success" : $data['message'] = "failed";
        return $data;
    }

    /**
     * This method is responsible for handling PUT requests to the post endpoint.
     *
     * It can handle PUT requests. Requires: userID, postID, and at least one of
     * the optional parameters (textContent, photoPath, videoPath, visibility, username, location)
     *
     * @return array
     * @throws ClientError
     */
    public function updatePost()
    {
        $postFields = $this->validateParams(['userID', 'postID'], ['textContent', 'photoPath', 'videoPath', 'visibility', 'username', 'location']);

        $setClauses = array_map(function ($column) {
            return "$column = :$column";
        }, $postFields);

        $sql = "UPDATE post SET " . implode(', ', $setClauses);
        $sql .= " WHERE userID = :userID AND postID = :postID";

        $sqlParams = $this->buildSqlParams($postFields);

        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    /**
     * This method is responsible for handling DELETE requests to the post endpoint.
     * It requires the postID parameter.
     *
     * @return array
     * @throws ClientError
     */
    public function delete()
    {
        $this->validateRequiredParams(['postID']);

        $sql = "DELETE FROM post WHERE postID = :postID";
        $sqlParams = [':postID' => $this->requestData['postID']];

        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    // Other methods...

    private function validateParams(array $requiredParams, array $optionalParams = [])
    {
        $providedOptionalParams = array_intersect($optionalParams, array_keys($this->requestData));

        if (empty($providedOptionalParams)) {
            throw new ClientError(422, "At least one of the optional parameters ("
                . json_encode($optionalParams) . ") is required");
        }

        $allParams = array_merge($requiredParams, $optionalParams);
        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        if (empty($providedPropertyKeys)) {
            throw new ClientError(422, "At least one property of the post is required");
        }

        $postFields = array_intersect($providedPropertyKeys, $allParams);

        if (empty($postFields)) {
            throw new ClientError(400, "No valid parameters provided");
        }

        return $postFields;
    }

    private function buildSqlParams(array $updateFields)
    {
        $sqlParams = [];

        foreach ($updateFields as $property) {
            if (isset($this->requestData[$property])) {
                $sqlParams[":$property"] = $this->requestData[$property];
            }
        }

        return $sqlParams;
    }

    private function validateRequiredParams(array $requiredParams)
    {
        if (array_intersect($requiredParams, array_keys($this->requestData)) !== $requiredParams) {
            throw new ClientError(422, "At least one of the required parameters (" .
                (json_encode($requiredParams)) . ") is required");
        }
    }
}