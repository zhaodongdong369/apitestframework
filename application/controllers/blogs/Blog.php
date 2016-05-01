<?php
class Blog extends CI_Controller{
    public function index() {
        echo 'Hello World';
    }

    public function add($a,$b) {
        echo $a+$b;
    }
}
