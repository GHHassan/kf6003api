<?php

namespace App\EndpointController;

/**
 * Developer
 * 
 * This class is responsible for returning the developer's information.
 * 
 * @package App\EndpointController
 * @author Ghulam Hassan Hassani <w20017074@northumbria.ac.uk>
 */

use App\Request;

class Developer extends Endpoint
{
    private $fullname = "Ghulam Hassan Hassani";
    private $studentID = "W20017074";
    private $data;
    private $allowedMethods = ['GET'];
    private $allowedParams = [];
    public function __construct()
    {
        $this->checkAllowedParams(Request::params(), $this->allowedParams);
        $this->data['fullname'] = $this->fullname;
        $this->data['Student ID'] = $this->studentID;

        parent::__construct($this->data);
    }

    private function getFullname()
    {
        return $this->fullname;
    }

    private function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    private function getStudentID()
    {
        return $this->studentID;
    }

    private function setStudentID($studentID)
    {
        $this->studentID = $studentID;
    }
}
