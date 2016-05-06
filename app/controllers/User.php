<?php
/**
 * User
 *
 * PHP Version 5.3
 *
 * @category  User
 * @package   Api
 * @author    zhaodongdong <1562122082@qq.com>
 * @copyright 2015 phpstudylab.cn
 * @license   PHP Version 5.3
 * @link      http://www.phpstudylab.cn
 */

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User description
 *
 * @category   User
 * @package    Api
 * @author     zhaodongdong <1562122082@qq.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.2.0
 * @deprecated Class deprecated in Release 2.0.0
 */

class User extends CI_Controller
{
     /**
     * 首页
     *
     * @access public
     * @return null
     */
    public function index()
    {
        $user = $this->session->user;
        if (empty($user)) {
            $loginurl = site_url('/user/login');
            redirect($loginurl, 'location');

        } else {
            redirect("/bacenter");
        }
    }
     /**
     * 登陆页面
     *
     * @access public
     * @return null
     */
    public function login()
    {
        $this->form_validation->set_rules('username', 'Username', 'required');    
        $this->form_validation->set_rules('password', 'Password', 'required');
        if ($this->form_validation->run() == false) {
            $this->load->view('user/login.php');
        } else {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $checkUser = $this->checkUser($username, $password);
            if ($checkUser) {
                $_SESSION['user'] = $checkUser;
                redirect('/bacenter/index');
            } else {
                $this->load->view('user/login.php');
            }
        }
    }
   

    /**
    * 注册用户
    *
    * @access public
    * @return null
    */
    public function register()
    {
        $this->form_validation->set_rules('username', 'Username', 'required|min_length[5]|max_length[20]');    
        $this->form_validation->set_rules('password', 'Password', 'required');
        $this->form_validation->set_rules('passconf', 'Password confirmation', 'required|matches[password]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[apiuser.email]');
        if ($this->form_validation->run() == false) {
            $this->load->view('user/register.php');
        } else {
            $user = $this->apiuser->addUser(); 
            if ($user) {
                $this->load->view('user/login.php');
            } else {
                $this->load->view('user/register.php');
            }
        }
    }
 
    /**
     * 检测用户密码是否正确
     *
     * @param string $username 用户名
     * @param string $password 密码
     *
     * @access public
     * @return null
     */
    protected function checkUser($username, $password)
    {
        $hash = password_hash(
            $password, PASSWORD_BCRYPT, array(
                'salt' => 'jsadf32^$&jasdfouppawefjwaof22342432adsfa'
            )
        );
        $query = $this->db->query("select id,name,email from apiuser where name='{$username}' and passhash='{$hash}' and status=1");
        return $query->row_array();
    }
    
    /**
     * 注销用户
     *
     * @access public
     * @return null
     */
    public function logout()
    {
        unset($_SESSION['user']);
        redirect("/user/login");
    }
}
