<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bacenter extends CI_Controller 
{
    //ba中心首页
    public function index()
    {
        $user = $_SESSION['user'];
        if($user) {
            $this->load->view('bacenter/index', $user);
        }else {
            redirect('/user/login');
        }
    }

    //ba认证资料列表
    public function balist()
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $uid = $user['id'];
        $ba_query = $this->db->query("select * from balist where userid={$uid}");
        $bas = $ba_query->result();
        $this->load->view("/bacenter/list", array('bas' => $bas));
    }

    //添加ba
    public function addba()
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $this->form_validation->set_rules('appkey', 'Appkey', 'required');    
        $this->form_validation->set_rules('secretkey', 'Secretkey', 'required');
        if($this->form_validation->run() == FALSE) {
            $this->load->view('/bacenter/addba');
        }else {
            $ba = $this->balist->addBa();
            redirect('/bacenter/balist');
        }
    }
    
    //添加api
    public function addapi()
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $this->form_validation->set_rules('url', 'Url', 'required');    
        if($this->form_validation->run() == FALSE) {
            $this->load->view('/bacenter/addapi');
        }else {
            $api = $this->apilist->addApi();
            redirect("/bacenter/apilist");
        }
    }

    //api列表
    public function apilist()
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $uid = $user['id'];
        $api_query = $this->db->query("select * from apilist where userid={$uid}");
        $apis = $api_query->result();
        $this->load->view("/bacenter/apilist", array('apis' => $apis));
    }

    //api测试 
    public function testapi()
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $uid = $user['id'];
        $api_query = $this->db->query("select id,url,args from apilist where userid={$uid}");
        $ba_query  = $this->db->query("select id,appkey from balist where userid={$uid}");
        $apis = $api_query->result();
        $bas = $ba_query->result();
        $vars = array(
            'apis' => $apis,
            'bas' => $bas,
            'result' => '',
            );
        $this->form_validation->set_rules('api', 'Api', 'required');    
        $this->form_validation->set_rules('ba', 'Ba', 'required');
        if($this->form_validation->run() == FALSE) {
            $this->load->view('/bacenter/testapi', $vars);
        }else {
            require 'CUrlHttp.php';
            $api_query = $this->db->query("select id,url,args from apilist where id={$_POST['api']}");
            $ba_query  = $this->db->query("select id,appkey,secretkey from balist where id={$_POST['ba']}");
            $api = $api_query->result();
            if(empty($api)) {
                $this->load->view("/bacenter/testapi", $vars);
                return;
            }
            $api = $api[0];
            $ba = $ba_query->result();
            if(empty($ba)) {
                $this->load->view("/bacenter/testapi", $vars);
                return;
            }
            $ba = $ba[0];
            $url = $api->url;
            $authinfo = array(
                'client_id' => $ba->appkey,
                'client_secret' => $ba->secretkey,
                );
            $retcode = 0;
            $curl = new CUrlHttp();
            $ret  = $curl->RESTRequest($url, $_POST['args'], $retcode,  $_POST['HTTP'], null, $authinfo);
            $vars['result'] = $ret;
            $this->load->view("/bacenter/testapi", $vars);
        }
    }
}
