<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller 
{
    public function index()
    {
        $user = $this->session->user;
        if(empty($user)) {
            $loginurl = site_url('/user/login');
            redirect($loginurl, 'location');

        }else {
            redirect("/bacenter");
        }
    }

    //登陆页面
    public function login()
    {
        $this->form_validation->set_rules('username', 'Username', 'required');    
        $this->form_validation->set_rules('password', 'Password', 'required');
        if($this->form_validation->run() == FALSE) {
            $this->load->view('user/login.php');
        }else {
            $username = $_POST['username'];
            $password = $_POST['passworrd'];
            $checkUser = $this->checkUser($username, $password);
            if($checkUser) {
                $_SESSION['user'] = $checkUser;
                redirect('/bacenter/index');
            }else {
                $this->load->view('user/login.php');
            }
        }
    }
   
   //注册用户
   public function register()
   {
        $this->form_validation->set_rules('username', 'Username', 'required|min_length[5]|max_length[20]');    
        $this->form_validation->set_rules('password', 'Password', 'required');
        $this->form_validation->set_rules('passconf', 'Password confirmation', 'required|matches[password]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[apiuser.email]');
        if($this->form_validation->run() == FALSE) {
            $this->load->view('user/register.php');
        }else {
            $user = $this->apiuser->addUser(); 
            if($user) {
                $this->load->view('user/login.php');
            }else {
                $this->load->view('user/register.php');
            }
        }
    

   }

    protected function checkUser($username, $password)
    {
        echo $hash = password_hash($password, PASSWORD_BCRYPT, array(
            'salt' => 'jsadf32^$&jasdfouppawefjwaof22342432adsfa'));
        $query = $this->db->query("select id,name,email from apiuser where name='{$username}' and passhash='{$hash}' and status=1");
        return $query->row_array();
    }

    //登陆逻辑 
    public function dologin()
    {
        $username = isset($_POST['username'])? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if(empty($username) || empty($password)) {
            redirect("/user/login");
        }
    }
}
