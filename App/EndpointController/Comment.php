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
    protected $requestData;
    private $data;

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
        $this->handleRequest();        
        parent::__construct($this->data);
    }

    protected function performAction()
    {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
    
        switch (Request::method()) {
            case 'GET':
                $this->data = $this->get();
                break;
            case 'POST':
                $this->data = $this->post();
                break;
            case 'PUT':
                $this->data = $this->updateComment();
                break;
            case 'DELETE':
                $this->data = $this->delete();
                break;
        }
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
    private function get()
    {
        $db = new Database(DB_PATH);

        $message = "No comments found for the specified criteria.";

        if (isset($this->requestData['commentID'])) {
            $sql = "SELECT * FROM comment WHERE commentID = :commentID";
            $sqlParams = [':commentID' => $this->requestData['commentID']];
        } elseif (isset($this->requestData['postID'], $this->requestData['userID'])) {
            $sql = "SELECT * FROM comment WHERE postID = :postID AND userID = :userID";
            $sqlParams = [
                ':postID' => $this->requestData['postID'],
                ':userID' => $this->requestData['userID'],
            ];
        } elseif (isset($this->requestData['postID'])) {
            $sql = "SELECT * FROM comment WHERE postID = :postID";
            $sqlParams = [':postID' => $this->requestData['postID']];
        } elseif (isset($this->requestData['userID'])) {
            $sql = "SELECT * FROM comment WHERE userID = :userID";
            $sqlParams = [':userID' => $this->requestData['userID']];
        } else {
            $sql = "SELECT * FROM comment";
            $sqlParams = [];
        }

        $data = $db->executeSQL($sql, $sqlParams);

        if (count($data) <= 0) {
            $data['message'] = $message;
        } else {
            $data['message'] = "success";
        }

        return $data;
    }

    /**
     * Create a new comment on a post.
     * Requires postID, userID, username, and commentContent.
     *
     * @return array
     * @throws ClientError
     */
    private function post()
    {
        echo json_encode($this->requestData);
        $requiredParams = ['postID', 'userID', 'username', 'commentContent'];
        foreach ($requiredParams as $param) {
            if (!isset($this->requestData[$param])) {
                throw new ClientError(422, "All required parameters ('postID', 'userID', 'username', 'commentContent') are mandatory.");
            }
        }

        $paramKeys = array_intersect($this->allowedParams, array_keys($this->requestData));
        $placeholders = implode(', ', array_map(function ($param) {
            return ":$param";
        }, $paramKeys));

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
     * Requires commentID, userID, and at least one of the optional parameters (commentContent).
     *
     * @return array
     * @throws ClientError
     */
    private function updateComment()
    {
        $requiredParams = ['commentID', 'userID', 'commentContent'];

        $providedParams = array_intersect($requiredParams, array_keys($this->requestData));
        if (count($providedParams) !== count($requiredParams)) {
            throw new ClientError(422, "All required parameters ('commentID', 'userID') are mandatory.");
        }

        $providedPropertyKeys = array_intersect($this->allowedParams, array_keys($this->requestData));

        if (empty($providedPropertyKeys)) {
            throw new ClientError(422, "At least one property of the comment is required");
        }

        $updateFields = array_intersect($providedPropertyKeys, $providedParams);

        if (empty($updateFields)) {
            throw new ClientError(400, "No valid parameters provided for updating a comment.");
        }

        $setClauses = array_map(
            function ($column) {
                return "$column = :$column";
            },
            $updateFields
        );

        $sql = "UPDATE comment SET " . implode(', ', $setClauses);
        $sql .= " WHERE commentID = :commentID AND userID = :userID";
        $sqlParams = [];

        foreach ($providedParams as $property) {
            if (isset($this->requestData[$property])) {
                $sqlParams[":$property"] = $this->requestData[$property];
            }
        }
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
    private function delete()
    {
        if (!isset($this->requestData['commentID'])) {
            throw new ClientError(422, "CommentID is required");
        }
        $sql = "DELETE FROM comment WHERE commentID = :commentID";
        $sqlParams = [':commentID' => $this->requestData['commentID']];
        echo $sql . ' sql';
        echo json_encode($sqlParams);
        $data[] = $this->db->executeSQL($sql, $sqlParams);
        $data['message'] = "success";
        return $data;
    }
}
