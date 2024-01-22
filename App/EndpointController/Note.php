<?php
 
namespace App\EndpointController;
 
/**
 * Note endpoint
 * 
 * enables users to add notes to a specific content
 * these notes are stored in the database and can
 * only be accessed by the user who created them.
 * The user who created the note can also update
 * or delete the note.
 * if a note id is not provided, the endpoint will
 * return all notes for the user, otherwise it will
 * return the note for the specific content.
 * 
 * @package App\EndpointController
 * @return [JSON] data of the note for a specific content
 * 
 * @author Hassan <w20017074>
 */

use App\{
    Request,
    Database,
    ClientError,
};

class Note extends Endpoint 
{
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
    private $allowedParams = ['content_id', 'note', 'note_id'];
    public function __construct() {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $id = $this->validateToken();
        $this->checkUserExists($id);
        switch(Request::method()) 
        {
            case 'GET':
                $data = $this->getNote($id);
                break;
            case 'POST':
            case 'PUT':
                $data = $this->postNote($id);
                break;
            case 'DELETE':
                $data = $this->deleteNote($id);
                break;
            default:
                throw new ClientError(405);
        }
        parent::__construct($data);
    }
 
    /**
     * note 
     * 
     * sanitise the note and check if it is over 255 characters
     * 
     * @return string
     */
    private function note() 
    {
        $note = $this->sanitiseString(REQUEST::params()['note']);

        if (mb_strlen(strlen($note) > 255)) {
            throw new ClientError(431, 'note is too long');
        }

       return $note;
    }
 
    /**
     * Get notes
     * 
     * Get all notes for a user unless a content_id is specified, in which case
     * it returns the note for that specific content.
     * 
     * @param int $id
     * @return array
     * 
     */
    private function getNote($id)
    {
        $this->allowedParams= ['content_id'];
        $this->checkAllowedParams(Request::params(), $this->allowedParams);
        if (isset(REQUEST::params()['content_id']))
        {
            $content_id = REQUEST::params()['content_id'];
            if (!is_numeric($content_id))
            {
                throw new ClientError(422, 'content_id must be numeric');
            }
            $sql = "SELECT * FROM notes WHERE user_id = :id AND content_id = :content_id";
            $sqlParams = [':id' => $id, 'content_id' => $content_id];
        } else {
            $sql = "SELECT * FROM notes WHERE user_id = :id";
            $sqlParams = [':id' => $id];
        }
 
        $dbConn = new Database(DB_USER_PATH);
        $data = $dbConn->executeSQL($sql, $sqlParams);
        
        return $data;
    }
 
    /**
     * Post note
     * 
     * This handles both posting a new note and updating an existing note
     * for a content. There can only be one note per content per user.
     */
    private function postNote($id)
    {
        $this->allowedParams= ['content_id', 'note'];
        $this->checkAllowedParams(Request::params(), $this->allowedParams);
        if (!isset(REQUEST::params()['content_id']))
        {
            throw new ClientError(422);
        }
        $content_id = REQUEST::params()['content_id'];
        
        if (!is_numeric($content_id))
        {
            throw new ClientError(422);
        }
 
        $note = $this->note();
        $dbConn = new Database(DB_USER_PATH);
        $sqlParams = [':id' => $id, 'content_id' => $content_id];
        $sql = "SELECT * FROM notes WHERE user_id = :id AND content_id = :content_id";
        $data = $dbConn->executeSQL($sql, $sqlParams);
 
        if (count($data) === 0) {
            $sql = "INSERT INTO notes (user_id, content_id, note) VALUES (:id, :content_id, :note)";
        } else {
            $sql = "UPDATE notes SET note = :note WHERE user_id = :id AND content_id = :content_id";
        }
 
        $sqlParams = [':id' => $id, 'content_id' => $content_id, 'note' => $note];
        $data = $dbConn->executeSQL($sql, $sqlParams);
        $data = ['message' => 'success'];
        return $data;
    }
 
 
    /**
     * Delete a note for a content. This method is not strictly necessary as
     * the postNote method can be used to 'delete' a note by setting the note
     * to an empty string.
     * 
     * @param int $id
     * @return array
     */
    private function deleteNote($id)
    {
        $this->allowedParams= ['content_id'];
        $this->checkAllowedParams(Request::params(), $this->allowedParams);
        if (!isset(REQUEST::params()['content_id']))
        {
            throw new ClientError(422);
        }
 
        $content_id = REQUEST::params()['content_id'];
        
        if (!is_numeric($content_id))
        {
            throw new ClientError(422);
        }
 
        $dbConn = new Database(DB_USER_PATH);
        $sql = "DELETE FROM notes WHERE user_id = :id AND content_id = :content_id";
        $sqlParams = [':id' => $id, 'content_id' => $content_id];
        $data = $dbConn->executeSQL($sql, $sqlParams);
        $data = ['message' => 'success'];
        return $data;
    }
 
    private function checkUserExists($id)
    {
        $dbConn = new Database(DB_USER_PATH);
        $sql = "SELECT id FROM account WHERE id = :id";
        $sqlParams = [':id' => $id];
        $data = $dbConn->executeSQL($sql, $sqlParams);
        if (count($data) != 1) {
            throw new ClientError(401, 'user does not exist');
        }
    }
}