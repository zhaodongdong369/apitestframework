<?php
class Apiuser extends CI_Model 
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addUser()
    {
        $this->name = $_POST['username'];
        $this->passhash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $check = $this->db->query("select * from apiuser where name='{$this->name}'");
        $check = $check->result();
        if($check) {
            return false;
        }
        $this->isadmin = 0;
        $this->status = 1;
        $this->ctime = $this->mtime = time();
        $this->email = $_POST['email'];
        $this->db->insert('apiuser', $this);
        return $this;
    }
}
