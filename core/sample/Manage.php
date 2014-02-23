<?php
Class Manage extends Post
{
    function __construct()
    {
        $this->_db = DB::connect();
        $sth = $this->_db->prepare('select * from user where username != ?');
        $sth->execute(array($_SESSION['username']));
        $users = $sth->fetchAll();
        $this->set('users', $users, View::NO_CACHE);
    }

}