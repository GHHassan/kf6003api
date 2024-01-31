<?php

namespace App;

/** 
 * The Database class
 * 
 * This class is responsible for connecting and
 * running CRUD operations and counting rows on the database
 * 
 * @author G H Hassani w20017074
 * 
 * @return Database connection
 */

class Database {
    private $pdo;

    public function __construct($databaseName) {
        $this->setDatabaseConnection($databaseName);
    }

    public function setDatabaseConnection($databaseName) {
        $this->pdo = new \PDO('sqlite:' . $databaseName);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function executeSql($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
    
            $queryType = strtoupper(explode(' ', $sql, 2)[0]);
    
            if ($queryType === 'SELECT') {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($queryType === 'INSERT') {
                $lastInsertId = $this->pdo->lastInsertId();
                return [
                    'lastInsertId' => $lastInsertId,
                ];
            } else {
                $rowCount = $stmt->rowCount();
                return [
                    'rowCount' => $rowCount,
                ];
            }
        } catch (\PDOException $e) {
            throw new \Exception("Database error: " . $e->getMessage());
        }
    }
    

    public function countRows($sql, $params = []) {
        try {
            $stmt = $this->executeSql($sql, $params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Database error: " . $e->getMessage());
        }
    }
}
