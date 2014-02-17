<?php
Class Topic extends Post
{
    function __construct()
    {
        $dirs = explode('/', $this->path);
        $id = (isset($dirs[1]) === true && is_numeric($dirs[1]) === true) ? $dirs[1] : false;
        if ( empty($id) === true ) {
            View::$http_status = 404;
            exit;
        }

        $this->_db = DB::connect();
        $topics = $this->_db->query('select * from topic where id = '.$id.' and status = 1')->fetch();
        if (isset($this->requests['logout']) === true && isset($_SESSION['username'])) {
            $this->logout();
        }
        if (empty($topics) === true) {
            View::$http_status = 404;
            exit;
        } else {
            if (isset($this->requests['username']) === true) {
                $this->login($this->requests['username'], $this->requests['password']);
            }
            if (isset($this->requests['newcomment']) === true && isset($_SESSION['username']) === true) {
                $this->addComment($this->requests['comment'], $id, $_SESSION['username']);
            }
            $this->set('errors', $this->_errors, View::NO_CACHE);
            $this->set('topics', $topics);
            $comments = $this->_db->query('select *, strftime("%s", since) as timestamp from comment where topic_id = '.$id.' and status = 1')->fetchAll();
            $this->set('comments', $comments);
            
            $this->set('datetime', new DateTime('now', new DateTimeZone( 'ASIA/TOKYO' )), VIEW::NO_CACHE);
        }
    }

}