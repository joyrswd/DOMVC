<?php
Class Admin extends Post
{
    function __construct()
    {
        $this->_db = DB::connect();
        $result = $this->_db->query('select * from user')->fetch();
        if (empty($result) === false) {
            Controller::jumpto('');
        }
        if (isset($this->requests['username']) === true) {
            $this->login($this->requests['username'], $this->requests['password']);
            $this->set('errors', $this->_errors, View::NO_CACHE);        
        }
    }

}