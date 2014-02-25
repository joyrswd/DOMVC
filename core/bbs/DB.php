<?php
Class DB
{
    static function connect()
    {
        try {
            $db = new PDO('sqlite:'.CORE_SITE_ROOT.DIRECTORY_SEPARATOR.'bbs.sql');
        } catch (PDOException $e) {
            die ('Connection failed : '.$e->getMessage());
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
        $result = $db->query('select * from sqlite_master where type="table" and name = "user"')->fetch();
        if (empty($result) === true) {
            self::createUserTable($db);
        }

        $result = $db->query('select * from sqlite_master where type="table" and name = "topic"')->fetch();
        if (empty($result) === true) {
            self::createTopicTable($db);
        }

        $result = $db->query('select * from sqlite_master where type="table" and name = "comment"')->fetch();
        if (empty($result) === true) {
            self::createCommentTable($db);
        }
        return $db;
    }

    static private function createUserTable($db)
    {
        $sql = 'create table user('
                . 'username text primary key, '
                . 'password text, '
                . 'level text default "user", '
                . 'status integer default 1, '
                . 'since timestamp default current_timestamp, '
                . 'lastupdate timestamp default current_timestamp)';
        $db->query($sql);
        $sql = 'create trigger user_lastupdate after update on user '
                . 'begin '
                . 'update user set lastupdate = datetime("now", "localtime") '
                . 'where username = old.username; end;';
        $db->query($sql);
        $sql = 'create index user_status on user(status)';
        $db->query($sql);
        $sql = 'create index user_since on user(since)';
        $db->query($sql);
    }

    static private function createTopicTable($db)
    {
        $sql = 'create table topic('
                . 'id integer primary key autoincrement, '
                . 'username text, '
                . 'title text, '
                . 'status integer default 1, '
                . 'since timestamp default current_timestamp, '
                . 'lastupdate timestamp default current_timestamp)';
        $db->query($sql);
        $sql = 'create trigger topic_lastupdate after update on topic '
                . 'begin '
                . 'update topic set lastupdate = datetime("now", "localtime") '
                . 'where id = old.id; end;';
        $db->query($sql);
        $sql = 'create index topic_user_id on topic(username)';
        $db->query($sql);
        $sql = 'create index topic_status on topic(status)';
        $db->query($sql);
        $sql = 'create index topic_since on topic(since)';
        $db->query($sql);
    }

    static private function createCommentTable($db)
    {
        $sql = 'create table comment('
                . 'id integer primary key, '
                . 'content text, '
                . 'topic_id integer, '
                . 'username text, '
                . 'status integer default 1, '
                . 'since timestamp default current_timestamp, '
                . 'lastupdate timestamp default current_timestamp )';
        $db->query($sql);
        $sql = 'create trigger comment_lastupdate after update on comment '
                . 'begin '
                . 'update comment set lastupdate = datetime("now", "localtime") '
                . 'where topic_id= old.topic_id and id = old.id; end;';
        $db->query($sql);
        $sql = 'create trigger comment_topic_lastupdate after insert on comment '
                . 'begin '
                . 'update topic set lastupdate = datetime("now", "localtime") '
                . 'where id = new.topic_id; end;';
        $db->query($sql);
        $sql = 'create index comment_topic_id on comment(topic_id)';
        $db->query($sql);
        $sql = 'create index comment_status on comment(status)';
        $db->query($sql);
        $sql = 'create index comment_since on comment(since)';
        $db->query($sql);
        $sql = 'create index comment_username on comment(username)';
        $db->query($sql);
    }
}