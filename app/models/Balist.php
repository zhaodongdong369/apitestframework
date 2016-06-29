<?php
class Balist extends CI_Model 
{

    public function addBa()
    {
        $user = $_SESSION['user'];
        $this->appkey = $_POST['appkey'];
        $this->secretkey = $_POST['secretkey'];
        $this->userid = $user['id'];
        $check = $this->db->query("select * from balist where userid={$user['id']} and appkey='{$this->appkey}' and status=1");
        $check = $check->result();
        if($check) {
            return false;
        }
        $this->status = 1;
        $this->ctime = $this->mtime = time();
        $this->db->insert('balist', $this);
        return $this;
    }

    //更新ba
    public function updateBa($baid)
    {
        $this->appkey = $_POST['appkey'];
        $this->secretkey = $_POST['secretkey'];
        $this->mtime = time();
        $this->db->update('balist', $this, array('id' => $baid));
    }

}
