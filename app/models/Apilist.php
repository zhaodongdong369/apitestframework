<?php
class Apilist extends CI_Model 
{
    public function addApi()
    {
        $user = $_SESSION['user'];
        $this->userid = $user['id'];
        $this->url = $_POST['url'];
        $this->args = $_POST['args'];
        $this->status = 1;
        $this->ctime = $this->mtime = time();
        $this->db->insert('apilist', $this);
        return $this;
    }
}
