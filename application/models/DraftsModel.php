<?php
class DraftsModel extends Model {
    /**
     * Gets drafts by the user id
     * @param  int $userId
     * @return array       Drafts
     */
    public function getDraftsByUserId($userId) {
        $sql = 'SELECT p.ID as ID, post_title, post_subtitle, name, 
                    UNIX_TIMESTAMP(post_date) as post_date, word_count
                FROM posts p JOIN users u ON (post_author=u.id) 
                WHERE post_status = "draft" AND u.id = ? 
                ORDER BY post_date DESC LIMIT 0, 10';
        $sth = $this->db->prepare($sql);
        $sth->execute(array($userId));

        return $this->_processPostList($sth);
    }
}
?>