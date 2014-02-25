<?php
Class Post extends Model
{
    protected $_db;
    protected $_errors = array();

    protected function login($username, $password)
    {
        if ($this->validateUsername($username) === true && $this->validatePassword($password) === true) {
            $sql = 'select * from user where username = ?';
            $sth = $this->_db->prepare($sql);
            $sth->execute(array($username));
            $user = $sth->fetch();

            if (empty($user) === true) {
               $this->createAccount($username, $password);
            } elseif ($user['password'] === sha1($password)) {
                $_SESSION['username']   = $user['username'];
                $_SESSION['level']      = $user['level'];
            } else {
               $this->_errors[] = 'Existed username and wrong password.';
            }
        }
    }

    protected function logout()
    {
       unset($_SESSION['username']);
       unset($_SESSION['level']);
       Controller::jumpto($this->path);
    }

    private function createAccount($username, $password)
    {
        $this->_db->beginTransaction();
        if ($this->path === 'admin') {
            $url = '';
            $level = 'admin';
        } else {
            $url = $this->path;
            $level = 'user';
        }
        $sql = 'insert into user(username, password, level) values(?, ?, ?);';
        $sth = $this->_db->prepare($sql);
        $sth->execute(array($username, sha1($password), $level));
        $this->_db->commit();
        $_SESSION['username']   = $username;
        $_SESSION['level']      = $level;
        Controller::jumpto($url);
    }

    protected function createTopic($topic, $comment, $username)
    {
        if ($this->validateTopic($topic, $comment) === true) {
            $this->_db->beginTransaction();
            $sql = 'insert into topic(title, username) values(?, ?);';
            $sth = $this->_db->prepare($sql);
            $sth->execute(array($topic, $username));
            $this->_db->commit();
            $id = $this->_db->lastInsertId();
            $this->addComment($comment, $id, $username);
        }
    }

    protected function addComment($comment, $id, $username)
    {
        if ($this->validateComment($comment) === true) {
            $this->_db->beginTransaction();
            $sql = 'insert into comment(content, topic_id, username) values(?, ?, ?);';
            $sth = $this->_db->prepare($sql);
            $sth->execute(array($comment, $id, $username));
            $this->_db->commit();
            Controller::jumpto($this->root.$this->path);
        }
    }

    private function validateUsername($username)
    {
        $valid = false;
        if (empty($username) === true) {
         $this->_errors[] = 'Username is mandatory.';
        } elseif (ctype_alnum($username) === false) {
         $this->_errors[]  = 'Username must be alphanumeric.';         
        } elseif (strlen($username) > 15) {
         $this->_errors[]  = 'Username must be less than 15 characters.';            
        } else {
         $valid = true;
        }
        return $valid;
    }

    private function validatePassword($password)
    {
        $valid = false;
        if (empty($password) === true) {
          $this->_errors[] = 'Password is mandatory.';
        } elseif (ctype_alnum($password) === false) {
         $this->_errors[]  = 'Password must be alphanumeric.';      
        } elseif (strlen($password) !== 8) {
         $this->_errors[]  = 'Password must be 8 characters.';            
        } else {
         $valid = true;
        }
        return $valid;
    }

    private function validateTopic($topic, $comment)
    {
        $valid = false;
        if (empty($topic) === true) {
          $this->_errors[] = 'Topic is mandatory.';
        } elseif (mb_strlen($topic, $this->encode) > 100) {
          $this->_errors[] = 'Topic must be less than 100 characters.';
        } else {
           $valid = true;
        }
        if ($this->validateComment($comment) === false && $valid === true) {
           $valid = false;            
        }
        return $valid;
    }

    private function validateComment($comment)
    {
        $valid = false;
        if (empty($comment) === true) {
          $this->_errors[] = 'Comment is mandatory.';
        } elseif (mb_strlen($comment, $this->encode) > 300) {
           $this->_errors[] = 'Comment must be 300 characters.';            
        } else {
           $valid = true;
        }
        return $valid;
    }
}