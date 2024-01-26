<?php

namespace App\EndpointController;

/**
 * Class Comment
 *
 * This class is responsible for handling the requests to the comment endpoint.
 * It can handle GET, POST, PUT, and DELETE requests for comments related to posts.
 *
 * Parameters: should be passed as JSON in the body of the HTTP request
 *
 * HTTP Request Methods:
 * GET:    Get comments for a post by postID or for a user by userID
 * POST:   Create a new comment on a post Requires postID, userID, username, and commentContent
 * PUT:    Update a comment Requires commentID, userID, and at least one of the optional parameters (commentContent, username)
 * DELETE: Delete a comment Requires commentID
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

class Comment extends Endpoint
{
    private $commentID;
    private $postID;
    private $userID;
    private $commentContent;
    private $requestData ;

    private $allowedParams = [
        'commentContent',
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
                $data = $this->updateComment();
                break;
            case 'DELETE':
                $data = $this->delete();
                break;
        }
        parent::__construct($data);
    }

    /**
     * Get comments for a post or for a user.
     * If postID is provided, get comments for that post; if userID is provided, get comments for that user.
     * If both postID and userID are provided, return comments for the specific user on that post.
     * If no parameters are provided, return all comments.
     *
     * @return array
     * @throws ClientError
     */
    public function get()
    {
        $db = new Database(DB_PATH);
        if (isset($this->requestData['postID']) && isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM comment WHERE postID = :postID AND userID = :userID";
            $sqlParams = [':postID' => $this->requestData['postID'], ':userID' => $this->requestData['userID']];
            $data = $db->executeSQL($sql, $sqlParams);
            if (count($data) === 0) {
                $data['message'] = "No comments found for the specified post and user.";
            }
            return $data;
        }

        if (isset($this->requestData['commentID'])) {
            $sql = "SELECT * FROM comment WHERE commentID = :commentID";
            $sqlParams = [':commentID' => $this->requestData['commentID']];
            $data = $db->executeSQL($sql, $sqlParams);
            if (count($data) === 0) {
                $data['message'] = "No comments found for the specified commentID.";
            }
            return $data;
        }

        if (isset($this->requestData['postID'])) {
            $sql = "SELECT * FROM comment WHERE postID = :postID";
            $sqlParams = [':postID' => $this->requestData['postID']];
            return $db->executeSQL($sql, $sqlParams);
        }

        if (isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM comment WHERE userID = :userID";
            $sqlParams = [':userID' => $this->requestData['userID']];
            return $db->executeSQL($sql, $sqlParams);
        }

        $sql = "SELECT * FROM comment";
        $sqlParams = [];
        return $db->executeSQL($sql, $sqlParams);
    }

    /**
     * Create a new comment on a post.
     * Requires postID, userID, username, and commentContent.
     *
     * @return array
     * @throws ClientError
     */
    public function post()
    {
        $requiredParams = ['postID', 'userID', 'username', 'commentContent'];
        // Check if all required parameters are provided
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "All required parameters ('postID', 'userID', 'username', 'commentContent') are mandatory.");
            }
        }

        // Check and sync parameters
        $paramKeys = array_intersect($this->allowedParams, array_keys($this->requestData));
        
        // Construct dynamic placeholders for the INSERT query
        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, $paramKeys));
        // Construct the INSERT query with dynamic placeholders
        $sql = "INSERT INTO comment (" . implode(', ', $paramKeys) . ") VALUES ($placeholders)";
        $sqlParams = [];
        foreach ($paramKeys as $param) {
            $sqlParams[":$param"] = $this->requestData[$param];
        }
        $data = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }

    /**
     * Update a comment.
     * Requires commentID, userID, and at least one of the optional parameters (commentContent, username).
     *
     * @return array
     * @throws ClientError
     */
    public function updateComment()
    {
        // Ensure that 'commentID', 'userID', and at least one of the properties are compulsory
        $requiredParams = ['commentID', 'userID', 'commentContent'];

        // Check if all required parameters are provided
        $providedParams = array_intersect($requiredParams, array_keys($this->requestData));
        if (count($providedParams) !== count($requiredParams)) {
            throw new ClientError(422, "All required parameters ('commentID', 'userID') are mandatory.");
        }

        // Check and sync SQL placeholders with allowed params
        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        if (empty($providedPropertyKeys)) {
            throw new ClientError(422, "At least one property of the comment is required");
        }

        // Extract valid parameters for update
        $updateFields = array_intersect($providedPropertyKeys, $providedParams);

        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for updating a comment.");
        }

        // Construct the UPDATE query
        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            $updateFields
        );

        $sql = "UPDATE comment SET " . implode(', ', $setClauses);
        // Add the WHERE condition for CommentID and UserID
        $sql .= " WHERE commentID = :commentID AND userID = :userID";

        // Create an array for SQL parameters
        $sqlParams = [];

        // Iterate through the keys in $updateFields and fetch corresponding values
        foreach ($providedParams as $property) {
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

    /**
     * Delete a comment by commentID.
     *
     * @return array
     * @throws ClientError
     */
    public function delete()
    {
        if (!isset($this->requestData['commentID'])) {
            throw new ClientError(422, "CommentID is required");
        }
        $sql = "DELETE FROM comment WHERE commentID = :commentID";
        $sqlParams = [':commentID' => $this->requestData['commentID']];
        echo $sql .' sql';
        echo json_encode($sqlParams);
        $data[] = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }
     /**
     * Set properties based on request data or query parameters.
     */
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
