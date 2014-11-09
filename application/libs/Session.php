<?php
/**
 * Sessions manager for logins and forgot password
 */
class Session extends Model {
    public $db;

    function __construct($db) {
        $this->db = $db;
        $this->db->exec('DELETE FROM sessions WHERE expire < NOW()');
    }

    private function _setCookie($name, $value, $expire) {
        $_COOKIE[$name] = $value;
        setcookie($name, $value, $expire, '/');
    }

    public function make($type, $data, $cookie = 1) {
        $sid = $this->_makeSid();

        $sql = 'INSERT INTO sessions (sid, type, data, expire) 
                VALUES (:sid, :type, :data, NOW() + INTERVAL 1 WEEK) 
                ON DUPLICATE KEY UPDATE expire = NOW() + INTERVAL 1 WEEK';
        $sth = $this->db->prepare($sql);
        $sth->execute(array('sid'  => $sid, 
                            'type' => $type, 
                            'data' => $data));

        if ($cookie) {
            $this->_setCookie($type, $sid, strtotime('+1 week'));
        }

        return $sid;
    }

    public function delete($sid) {
        $sth = $this->db->prepare('SELECT type FROM sessions WHERE sid = ? LIMIT 1');
        $sth->execute(array($sid));
        $row = $sth->fetch();
        if ($row) {
            $this->_setCookie($row['type'], '', strtotime('-1 week'));

            $sth = $this->db->prepare('DELETE FROM sessions WHERE sid = ? LIMIT 1');
            $sth->execute(array($sid));

            return true;
        } 

        return false;
    }

    public function get($sid) {
        if(!$this->isSid($sid)) {
            return false;   
        } 

        $sth = $this->db->prepare('SELECT type, data FROM sessions WHERE sid = ? LIMIT 1');
        $sth->execute(array($sid));
        
        return $sth->fetch();
    }
    
    // function update($sid) {
    //     if ($this->isSid($sid)) {
    //         $this->db->real_query(sprintf('UPDATE %ssessions SET time = NOW() WHERE sid = "%s" LIMIT 1',
    //                                         pre,
    //                                         $sid));

    //         return 1;
    //     } 

    //     return 0;
    // }

    function isSid($sid) {
        if(   isset($sid[31])
             &&!isset($sid[32])
             && ctype_alnum($sid)
        ) {
            $sth = $this->db->prepare('SELECT 1 FROM sessions WHERE sid = ? LIMIT 1');
            $sth->execute(array($sid));
            
            return (bool) $sth->fetch();
        } 

        return false;
    }

    private function _makeSid() {
        $sid = $this->uniq(32);
        $sid = $this->hashPassword($sid);

        return $sid;
    }
}
?>