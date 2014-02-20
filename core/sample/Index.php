<?php
Class Index extends Post
{   
    private $_chunk = 5;
    function __construct()
    {
        $this->set('post', $_POST, View::NO_CACHE);
        $index = (is_numeric($this->path) === true) ? $this->path - 1 : 0;
        $this->_db = DB::connect();
        $result = $this->_db->query('select * from user')->fetch();
        if (empty($result) === true) {
            Controller::jumpto($this->root.'admin');
        }
        if (isset($this->requests['logout']) === true && isset($_SESSION['username'])) {
            $this->logout();
        }
        if (isset($this->requests['username']) === true) {
            $this->login($this->requests['username'], $this->requests['password']);
        }
        if (isset($this->requests['newtopic']) === true && isset($_SESSION['username']) === true) {
            $this->createTopic($this->requests['topic'], $this->requests['comment'], $_SESSION['username']);
        }
        $this->set('errors', $this->_errors, View::NO_CACHE);

        $topics = $this->_db->query('select *, strftime("%s", since) as since_ts, strftime("%s", lastupdate) as lastupdate_ts '
                . 'from topic left join (select count(id) as total, topic_id from comment where status = 1 group by topic_id) on topic_id = id '
                . 'where status = 1 order by since desc')->fetchAll();
        $break = count($topics) / $this->_chunk;
        if ( empty($topics) === false && $index < $break) {
            $this->set('topic_limit', $break, View::NO_CACHE);
            $temp = array_chunk($topics, $this->_chunk);
            $this->set('topics', $temp[$index], View::NO_CACHE);
        } else {
            View::$http_status = 404;
            exit;
        }
        $this->set('index', $index, View::NO_CACHE);
        $this->set('datetime', new DateTime('now', new DateTimeZone( 'ASIA/TOKYO' )), VIEW::NO_CACHE);

    }

}