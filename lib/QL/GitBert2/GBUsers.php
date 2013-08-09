<?php
/*
© Quicken Loans Inc. - Trade Secret, Confidential and Proprietary,
All rights reserved, 2002 - 2011.
Unauthorized dissemination is strictly prohibited.
*/

require_once 'QGMySQL.php';

class GBUsers {
    private $db;

    public function __construct() {
        $db_link = mysql_connect('localhost', 'root', '');
        if (!$db_link) {
            die(sprintf('Could not connect to database (%d): %s', mysql_errno(), mysql_error()));
        }
        $db_selected = mysql_select_db('gitbertSlim');
        if (!$db_selected) {
            die(sprintf('Could not select database (%d): %s', mysql_errno(), mysql_error()));
        }
        $this->db = new QGMySQL($db_link);
    }

    public function addUser($commonId, $userId, $email, $displayName) {
        $query = "insert into gitUsers (CommonId, UserId, Email, DisplayName) values (:commonId, :userId, :email, :displayName)";
        $result = $this->db->query($query, array(':commonId' => $commonId, ':userId' => $userId, ':email' => $email, ':displayName' => $displayName));
    }

    public function listUsers() {
        $query = "SELECT * FROM gitUsers";
        $result = $this->db->query($query);
        return $result;
    }
}
