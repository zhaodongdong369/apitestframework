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
        $ba_query = $this->db->query("select * from balist where userid={$uid} and status=1");
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

    //删除ba
    public function delba($baid)
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $uid = $user['id'];
        $ba_query = $this->db->query("select * from balist where id={$baid} and  userid={$uid} and status=1");
        if(empty($ba_query)) {
            redirect("/bacenter/balist");
        }
        $bas = $ba_query->result();
        if(count($bas) > 0) {
            $ba = $bas[0];        
            $delquery = $this->db->query("update balist set status=0 where id={$baid}");
        }
        redirect("/bacenter/balist");
    }
  
    //更新ba
    public function updateba($baid)
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $this->form_validation->set_rules('appkey', 'Appkey', 'required');    
        $this->form_validation->set_rules('secretkey', 'Secretkey', 'required');
        $uid = $user['id'];
        $ba_query = $this->db->query("select * from balist where id={$baid} and  userid={$uid} and status=1");
        if(empty($ba_query)) {
            redirect("/bacenter/balist");
        }
        $bas = $ba_query->result();
        $ba = null;
        if(count($bas)) {
            $ba = $bas[0];
        }
        if(!$ba || $this->form_validation->run() == FALSE) {
            $this->load->view('/bacenter/updateba', array('ba' => $ba));
        }else {
            $this->balist->updateBa($baid);
            redirect("/bacenter/balist");
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
    
    //删除api
    public function delapi($apiid)
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $uid = $user['id'];
        $api_query = $this->db->query("select * from apilist where id={$apiid} and  userid={$uid} and status=1");
        if(empty($api_query)) {
            redirect("/bacenter/apilist");
        }
        $apis = $api_query->result();
        if(count($apis) > 0) {
            $api = $apis[0];        
            $delquery = $this->db->query("update apilist set status=0  where id={$apiid}");
        }
        redirect("/bacenter/apilist");
    }
    
    //更新api
    public function updateapi($apiid)
    {
        $user = $_SESSION['user'];
        if(empty($user)) {
            redirect("/user/login");
        }
        $this->form_validation->set_rules('url', 'Url', 'required');    
        $uid = $user['id'];
        $api_query = $this->db->query("select * from apilist where id={$apiid} and  userid={$uid}");
        $apis = $api_query->result();
        $api = null;
        if(count($apis)) {
            $api = $apis[0];
        }
        if(!$api || $this->form_validation->run() == FALSE) {
            $this->load->view('/bacenter/updateapi', array('api' => $api));
        }else {
            $api = $this->apilist->updateApi($apiid);
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
        $api_query = $this->db->query("select * from apilist where userid={$uid} and status=1");
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
        $api_query = $this->db->query("select id,url,args from apilist where userid={$uid} and status=1");
        $ba_query  = $this->db->query("select id,appkey from balist where userid={$uid} and status=1");
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
            $url = $_POST['api'];
            $ba_query  = $this->db->query("select id,appkey,secretkey from balist where id={$_POST['ba']}");
            if(empty($url)) {
                $this->load->view("/bacenter/testapi", $vars);
                return;
            }
            $ba = $ba_query->result();
            if(empty($ba)) {
                $this->load->view("/bacenter/testapi", $vars);
                return;
            }
            $ba = $ba[0];
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
