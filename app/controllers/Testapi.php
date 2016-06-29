<?php
/**
 * Testapi
 *
 * PHP version 5.3
 *
 * @category  Testapi
 * @package   Api
 * @author    zhaodongdong <1562122082@qq.com>
 * @copyright 2015 phpstudylab.cn
 * @license   php version 5.3
 * @link      http://www.phpstudylab.cn
 */


defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Testapi description
 *
 * @category   Testapi
 * @package    Testapi
 * @author     zhaodongdong <1562122082@qq.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.2.0
 * @deprecated Class deprecated in Release 2.0.0
 */

class Testapi extends CI_Controller
{
    protected $appkey;
    public static $ARR_BASICAUTH_USER = array(
        'test' => 'test',
        );

    /**
     * 有ba功能的测试接口
     *
     * @access public
     * @return null
     */
    public function checkba()
    {
        $date = $_SERVER['HTTP_DATE']; 
        if (empty($date)) {
            die('EMPTY DATE');
        }
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        if (empty($authorization)) {
            die('EMPTY AUTHRIZATION');
        }
        if (strpos($authorization, 'MWS') !== 0) {
            die('WRONG FORMAT AUTHORIZATION');
        }
        $arr_split = explode(':', substr($authorization, 4));
        if (!is_array($arr_split) || count($arr_split) !== 2) {
            die('WRONG FORMAT AUTHORIZATION');
        }
        $client_id = $arr_split[0];
        if (!isset(self::$ARR_BASICAUTH_USER[$client_id])) {
            die('WRONG CLIENT_ID');
        }
        defined('URI') ? '' : 
        define(
            'URI', isset(
                $_SERVER['REQUEST_URI']
            ) ? $_SERVER['REQUEST_URI'] : ''
        );
        $client_secret = self::$ARR_BASICAUTH_USER[$client_id];
        $string_to_sign = 'GET'.' '.URI . "\n" . $date;
        $signature = base64_encode(
            hash_hmac(
                'sha1', $string_to_sign, $client_secret, true
            )
        );
        if ($signature !== $arr_split[1]) {
            die('WRONG SIGNATURE');
        }
        $arr = array(
            'name' => 'test',
            'age' => 18,
            'ext' => array(
                'sex' => 'male',
                ),
            );
        die(json_encode($arr));
    }
}
