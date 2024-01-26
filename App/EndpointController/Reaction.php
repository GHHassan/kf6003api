<?php

namespace App\EndpointController;

/**
 * Class Reactions
 *
 * This class is responsible for handling reactions (e.g., likes) on posts.
 * It can handle GET, POST, PUT, and DELETE requests for reactions.
 *
 * Parameters: should be passed as JSON in the body of the HTTP request
 *
 * HTTP Request Methods:
 * GET:    Get reactions for a post by postID or for a user by userID
 * POST:   Create a new reaction on a post Requires postID, userID, and reactionType
 * PUT:    Update a reaction Requires reactionID, userID, and newReactionType
 * DELETE: Delete a reaction Requires reactionID
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

class Reaction extends Endpoint
{
    private $reactionID;
    private $postID;
    private $userID;
    private $reactionType;
    protected $requestData;

    private $allowedParams = [
        'postID',
        'userID',
        'reactionType',
        'reactionID'
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
        $this->handleRequest();
        parent::__construct($this->getData());
    }

    protected function performAction()
    {
        $data = [];
        switch (Request::method()) {
            case 'GET':
                $data = $this->get();
                break;
            case 'POST':
                $data = $this->post();
                break;
            case 'PUT':
                $data = $this->updateReaction();
                break;
            case 'DELETE':
                $data = $this->delete();
                break;
        }
        $this->setData($data);
    }

    /**
     * Get reactions for a post or for a user.
     * If postID is provided, get reactions for that post; if userID is provided, get reactions for that user.
     * If both postID and userID are provided, return reactions for the specific user on that post.
     * If no parameters are provided, return all reactions.
     *
     * @return array
     * @throws ClientError
     */
    public function get()
    {
        $db = new Database(DB_PATH);
        if (isset($this->requestData['postID']) && isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM reaction WHERE postID = :postID AND userID = :userID";
            $sqlParams = [':postID' => $this->requestData['postID'], ':userID' => $this->requestData['userID']];
            $data = $db->executeSQL($sql, $sqlParams);
            if (count($data) === 0) {
                $data['message'] = "No reactions found for the specified post and user.";
            }
            return $data;
        }

        if (isset($this->requestData['reactionID'])) {
            $sql = "SELECT * FROM reaction WHERE reactionID = :reactionID";
            $sqlParams = [':reactionID' => $this->requestData['reactionID']];
            $data = $db->executeSQL($sql, $sqlParams);
            if (count($data) === 0) {
                $data['message'] = "No reactions found for the specified reactionID.";
            }
            return $data;
        }

        if (isset($this->requestData['postID'])) {
            $sql = "SELECT * FROM reaction WHERE postID = :postID";
            $sqlParams = [':postID' => $this->requestData['postID']];
            return $db->executeSQL($sql, $sqlParams);
        }

        if (isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM reaction WHERE userID = :userID";
            $sqlParams = [':userID' => $this->requestData['userID']];
            return $db->executeSQL($sql, $sqlParams);
        }

        $sql = "SELECT * FROM reaction";
        $sqlParams = [];
        return $db->executeSQL($sql, $sqlParams);
    }

    /**
     * Create a new reaction on a post.
     * Requires postID, userID, and reactionType.
     *
     * @return array
     * @throws ClientError
     */
    public function post()
    {
        $requiredParams = ['postID', 'userID', 'reactionType'];
        // Check if all required parameters are provided
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "All required parameters ('postID', 'userID', 'reactionType') are mandatory.");
            }
        }

        // Check and sync parameters
        $paramKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        // Construct dynamic placeholders for the INSERT query
        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, $paramKeys));
        // Construct the INSERT query with dynamic placeholders
        $sql = "INSERT INTO reaction (" . implode(', ', $paramKeys) . ") VALUES ($placeholders)";
        $sqlParams = [];
        foreach ($paramKeys as $param) {
            $sqlParams[":$param"] = $this->requestData[$param];
        }
        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    /**
     * Update a reaction.
     * Requires reactionID, userID, and newReactionType.
     *
     * @return array
     * @throws ClientError
     */
    public function updateReaction()
    {
        // Ensure that 'reactionID', 'userID', and at least one of the properties are compulsory
        $requiredParams = ['reactionID', 'userID', 'reactionType'];

        // Check if all required parameters are provided
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "All required parameters ('reactionID', 'userID', 'reactionType') are mandatory.");
            }
        }
        // Check and sync SQL placeholders with allowed params
        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));
        // Extract valid parameters for update
        $updateFields = array_intersect($providedPropertyKeys, array_keys($this->requestData));
        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for updating a reaction.");
        }
        // Construct the UPDATE query
        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            $updateFields
        );

        $sql = "UPDATE reaction SET " . implode(', ', $setClauses);
        // Add the WHERE condition for ReactionID and UserID
        $sql .= " WHERE reactionID = :reactionID";

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
        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    /**
     * Delete a reaction by reactionID.
     *
     * @return array
     * @throws ClientError
     */
    public function delete()
    {
        if (!isset($this->requestData['reactionID'])) {
            throw new ClientError(422, "ReactionID is required");
        }
        $sql = "DELETE FROM reaction WHERE reactionID = :reactionID";
        $sqlParams = [':reactionID' => $this->requestData['reactionID']];
        $data[] = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }
}
