<?php
class HomeModel extends Model {
	/**
	 * Fetches all published posts
	 * @return array 
	 */
    public function getPublishedPosts() {
        $sql = 'SELECT p.ID as ID, post_title, post_subtitle,
                    username, name, word_count, b.status as bookmark_status,
                    UNIX_TIMESTAMP(post_date) as post_date 
                FROM posts p JOIN users u ON (post_author=u.id) 
                    LEFT JOIN bookmarks b ON (b.post_id=p.ID AND b.user_id=?)
                WHERE post_status = "publish"   
                ORDER BY post_date DESC LIMIT 0, 10';
        $sth = $this->db->prepare($sql);
        $sth->execute(array($GLOBALS['user']['id']));

        $rows = array();
        while($row = $sth->fetch()) {
            $row['post_date_supertag'] = date('j<b\r>M', $row['post_date']);
            $row['reading_time'] = ceil($row['word_count'] / WORDS_PER_MINUTE);

            $rows[] = $row;
        }

        return $rows;
    }   

    public function getUserByUsername($username) {
        $sth = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $sth->execute(array($username));

        return $sth->fetch();
    }
}
?>