<?php
Class Topic extends Post
{
    private $_chunk = 10;
    function __construct()
    {
        $dirs = explode('/', $this->path);
        $id = (isset($dirs[1]) === true && is_numeric($dirs[1]) === true) ? $dirs[1] : false;
        $index = (isset($dirs[2]) === true && is_numeric($dirs[2]) === true) ? $dirs[2] - 1 : 0;        
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
            $break = count($comments) / $this->_chunk;
            if ( empty($comments) === false && $index < $break) {
                $this->set('comment_limit', $break, View::NO_CACHE);
                $this->set('chunk', $this->_chunk, View::NO_CACHE);
                $temp = array_chunk($comments, $this->_chunk);
                $this->set('comments', $temp[$index]);
            } else {
                View::$http_status = 404;
                exit;
            }

            $this->set('topic_id', $id, View::NO_CACHE);
            $this->set('index', $index, View::NO_CACHE);
            $this->set('datetime', new DateTime('now', new DateTimeZone( 'ASIA/TOKYO' )), VIEW::NO_CACHE);
        }
    }

}