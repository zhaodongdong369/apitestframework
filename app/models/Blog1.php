<?php
class Blog1 extends CI_Model {
    public $title;
    public $content;
    public $date;

    public function __construct()
    {
        parent::__construct();
    }

    public function insert_entry()
    {
        $this->title = $_POST['title'];
        $this->content = $_POST['content'];
        $this->date = time();
        $this->db->insert('blog', $this);
    }
}
