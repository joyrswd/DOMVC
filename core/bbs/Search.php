<?php
Class Search extends Post
{   
    private $_chunk = LIST_ITEM_LIMIT;
    function __construct()
    {
        if (empty($this->requests['keyword']) === false) {
            Controller::jumpto($this->root.'search/'.$this->requests['keyword']);
        }
        
        $keywords = explode('/', rawurldecode($this->path));
        $keyword = $keywords[1];
        
        $index = (isset($keywords[2]) === true && is_numeric($keywords[2]) === true) ? $keywords[2] - 1 : 0;
        $this->_db = DB::connect();
        
        if (isset($this->requests['logout']) === true && isset($_SESSION['username'])) {
            $this->logout();
        }

        if (strlen($keyword) > 2) {
            $sql = 'select *, group_concat(comment.id||" "||comment.content, "<>") as comments from topic left join comment on topic_id = topic.id '
                    . 'where (topic.status = 1 and  title like ?) or (comment.status = 1 and comment.content like ?) '
                    . 'group by topic_id order by topic.since desc';
            $sth = $this->_db->prepare($sql);
            $sth->execute(array('%'.$keyword.'%', '%'.$keyword.'%'));
            $results = $sth->fetchAll();
            $break = count($results) / $this->_chunk;
            if ( empty($results) === false && $index < $break) {
                $this->set('result_limit', $break, View::NO_CACHE);
                $temp = array_chunk($results, $this->_chunk);
                $this->set('results', $temp[$index], View::NO_CACHE);
            } elseif(isset($_SESSION['username']) === false) {
                View::$http_status = 404;
                exit;
            }
        }
        $this->set('keyword', $keyword, View::NO_CACHE);
        $this->set('index', $index, View::NO_CACHE);
        $this->set('datetime', new DateTime('now', new DateTimeZone( 'ASIA/TOKYO' )), VIEW::NO_CACHE);
    }

}
