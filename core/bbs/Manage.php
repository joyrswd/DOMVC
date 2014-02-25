<?php
Class Manage extends Post
{
    function __construct()
    {
        if (empty($_SESSION['level']) === true || $_SESSION['level'] !== 'admin') {
            Controller::jumpto('');
        }
        $this->_db = DB::connect();
        
        if (empty($this->requests['update']) === false) {
            $sth = $this->_db->prepare('update user set level = :level, status = :status where username = :username');
            $binder = array(
                'level'=> (empty($this->requests['admin'])) ? 'user': 'admin',
                'status'=> (empty($this->requests['disable'])) ? 0: 1,
                'username'=>$this->requests['update']
                
            );
            $sth->execute($binder);
        }
        if (isset($this->requests['logout']) === true && isset($_SESSION['username'])) {
            $this->logout();
            Controller::jumpto('');
        }
        
        
        $sth = $this->_db->prepare('select * from user where username != ?');
        $sth->execute(array($_SESSION['username']));
        $users = $sth->fetchAll();
        $this->set('users', $users, View::NO_CACHE);
    }

}