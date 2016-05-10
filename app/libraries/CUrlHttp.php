<?php
/**
 * CurlHttp
 *
 * PHP Version 5.3
 *
 * @category  CUrlHttp
 * @package   Api
 * @author    zhaodongdong <1562122082@qq.com>
 * @copyright 2015 phpstudylab.cn
 * @license   PHP Version 5.3
 * @link      http://www.phpstudylab.cn
 */


/**
 * CUrlHttp description
 *
 * @category   CUrlHttp
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

class CUrlHttp
{
    /**
     * Cookie 字符串
     * 用于CURLOPT_COOKIE
     *
     * @var String
     */
    private $_cookiestr;
    /**
     * Cookie 前缀
     */
    public $cookie_prefix;
    /**
     * 最近一次访问url
     *
     * @var String
     */
    public $location;
    /**
     * Curl资源
     *
     * @var Resource
     */
    protected $curl;
    /**
     * Cookie数组
     *
     * @var Array
     */
    public $cookie;

    /**
     * 是否开启debug模式
     *
     * @var Boolean
     */
    public $debug;

    /**
     * 额外头数据
     *
     * @var Array
     */
    public $header = array(
        //'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
        //'Accept-Language: zh-cn,zh;q=0.5',
        //'Accept-Charset: gb2312,utf-8;q=0.7,*;q=0.7',
        'Accept: */*',
        //Accept-Encoding gzip, deflate
        'Accept-Language: zh-cn',
        'UA-CPU: x86',
    );
    /**
     * 自定义的请求方式
     *
     * @var String
     */
    public $customMethod = null;
    /**
     * 是否启用gzip支持
     *
     * @var Boolean
     */
    public $enableGZip = true;
    /**
     * 持久连接超时
     * 为0时禁用持久链接
     *
     * @var Integer
     */
    public $keepAlive = 300;

    /**
     * 认证信息
     *
     * @var array BA 认证信息
     */
    protected $authData;

    /**
     * 电影认证信息
     *
     * @var array 电影认证信息
     */
    protected $movieAuthData;
    
    /**
     * 用户代理
     *
     * @var String
     */
    public $userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322)';
    /**
     * 全局超时
     *
     * @var Integer
     */
    protected $globalTimeout = 30;
    /**
     * 连接超时
     *
     * @var Integer
     */
    protected $connTimeout = 10;
    /**
     * 是否使用证书
     *
     * @var Bool
     */
    public $useCert = false;
    /**
     * 若使用了证书要设置的属性
     */
    // 重设header
    public $sslHeader = array();
    // 设置证书路径
    public $certPath = '';
    // 设置证书密码
    public $certPwd = '';
    // 设置证书类型
    public $certType = 'pem';
    // 设置https端口
    public $sslPort = 443;

    /**
     * 构造函数
     *
     * @param Array $conf 其中globalTimeout和connTimeout单位为秒，但可以使用小数，以实现按毫秒设置超时
     *
     * @access public
     * @return nul
     */
    public function __construct($conf = null)
    {
        // 修改默认配置
        if (!empty($conf) && is_array($conf)) {
            foreach ($conf as $key => $val) {
                $this->$key = $val;
            }
        }
        $this->_curlInit();
    }

    /**
     * 析构函数  
     *
     * @access public
     * @return null
     */
    public function __destruct()
    {
        is_resource($this->curl) && curl_close($this->curl);
    }
    
    /**
     * 关闭curl
     *
     * @access public
     * @return null
     */
    public function curlClose()
    {
        is_resource($this->curl) && curl_close($this->curl);
    }
    
    /**
     * 设置curl选项
     *
     * @param var $key key
     * @param var $val val
     *
     * @access public
     * @return null
     */
    public function setOpt($key, $val)
    {
        curl_setopt($this->curl, $key, $val);
    }
    
    /**
     * 设置全局超时时间
     *
     * @param int $timeout 全局超时时间
     *
     * @access public
     * @return null
     */
    public function setGlobalTimeout($timeout) 
    {
        $this->globalTimeout = $timeout;
        $this->setOpt(CURLOPT_TIMEOUT_MS, $timeout * 1000);

        // 如果设置小于1s的超时，CURL会在DNS解析阶段立即超时
        // 设置 CURLOPT_NOSIGNAL 为1可以绕开这一问题
        if ($this->globalTimeout < 1 || $this->connTimeout < 1) {
            $this->setOpt(CURLOPT_NOSIGNAL, 1);
        }
    }
    /**
     * 设置连接超时时间
     *
     * @param int $timeout 连接超时时间
     *
     * @access public
     * @return null
     */
    public function setConnTimeout($timeout) 
    {
        $this->connTimeout = $timeout;
        $this->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout * 1000);

        // 如果设置小于1s的超时，CURL会在DNS解析阶段立即超时
        // 设置 CURLOPT_NOSIGNAL 为1可以绕开这一问题
        if ($this->globalTimeout < 1 || $this->connTimeout < 1) {
            $this->setOpt(CURLOPT_NOSIGNAL, 1);
        }
    }

    /**
     * 初始化curl资源
     *
     * @access private
     * @return null
     */
    private function _curlInit()
    {
        // init
        $this->curl = curl_init();
        // cookie truncate
        $this->cookie = array();
        curl_setopt_array(
            $this->curl, array(
            // gzip
            CURLOPT_ENCODING => $this->enableGZip ? 'gzip,deflate' : 0 ,
            // use return
            CURLOPT_RETURNTRANSFER => true,
            // user agent
            //CURLOPT_USERAGENT => $this->userAgent,
            // support redirect
            CURLOPT_FOLLOWLOCATION => true,
            // max redirect(for safty)
            CURLOPT_MAXREDIRS => 10,
            // connect timeout
            CURLOPT_CONNECTTIMEOUT_MS => intval($this->connTimeout * 1000),
            // global timeout
            CURLOPT_TIMEOUT_MS => intval($this->globalTimeout * 1000),
            // clean cookie
            CURLOPT_COOKIESESSION => true,
            // enable HTTPS
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            )
        );

        // 如果设置小于1s的超时，CURL会在DNS解析阶段立即超时
        // 设置 CURLOPT_NOSIGNAL 为1可以绕开这一问题
        if ($this->globalTimeout < 1 || $this->connTimeout < 1) {
            curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        }
    }

    /**
     * 更新cookie
     *
     * @access public
     * @return null
     */
    public function refreshCookie()
    {
        $this->_cookiestr = '';
        foreach ($this->cookie as $key => $val) {
            $this->_cookiestr .= $key . '=' . $val . '; ';
        }
        if ((!empty($this->cookie_prefix)) && count($this->cookie) !== 0) {
            $this->_cookiestr = $this->cookie_prefix . '; ' . $this->_cookiestr;
        }
        $this->_cookiestr && curl_setopt($this->curl, CURLOPT_COOKIE, $this->_cookiestr);
    }

    /**
     * 根据parse_url数组拼接urlparse_url的函数
     *
     * @param array $arr 数据
     *
     * @access public
     * @return String
     */
    public static function glueUrl($arr)
    {
        if (!is_array($arr)) {
            return null;
        }
        $u = null;
        extract($arr);
        !empty($scheme) && $u .= $scheme . ':' . ($scheme === 'mailto' ? '' : '//');
        isset($user) && $u .= $user . (isset($pass) ? ':' . $pass : '') . '@';
        !empty($host) && $u .= $host;
        isset($port) && $u .= ':' . $port;
        !empty($path) && $u .= $path;
        isset($query) && $u .= '?' . $query;
        isset($fragment) && $u .= '#' . $fragment;

        return $u;
    }
    /**
     * 设置认证信息
     *
     * @param string $client_id     appkey
     * @param string $client_secret secretkey
     *
     * @access public
     * @return String
     */
    public function setAuthInfo($client_id, $client_secret)
    {
        $this->authData = array(
                'id'        => $client_id,
                'secret'    => $client_secret,
        );
    }

    /**
     * 设置请求方式
     *
     * @param var $method 请求方式
     *
     * @access public
     * @return null
     */
    public function setCustomMethod($method)
    {
        $this->customMethod = $method;
    }
    
    /**
     * 添加header头
     *
     * @param array $header header头
     *
     * @access public
     * @return null
     */
    public function addHeader($header)
    {
        $this->header[] = $header;
    }

    /**
     * 执行http请求
     *
     * @param String  $url      请求url
     * @param String  $body     响应文本
     * @param String  $charset  响应字符集 (为空则不转换)
     * @param Boolean $isPost   是否为post请求
     * @param Mixed   $postData post数据
     * @param bool    $isMulti  是否是multi表单
     *
     * @access private
     * @return Mixed   false:成功; String:失败信息
     */
    private function _httpExec($url, &$body, $charset, $isPost, $postData = null, $isMulti = false)
    {
        // 更新cookie
        $this->refreshCookie();
        // 更新location
        $this->location = $url;
        // 额外header
        $header = $this->header;
        if ($isMulti) {
            $header[] = 'Content-Type: multipart/form-data; boundary=' . OAuthUtil::$boundary ;
            $header[] = 'Content-Length: ' . strlen($postData);
            $header[] = "SaeRemoteIP: " . $_SERVER['REMOTE_ADDR'];
            $header[] = "Expect: ";
        }
        // keep alive
        if ($this->keepAlive > 0) {
            $header[] = 'Keep-Alive: ' . $this->keepAlive;
            $header[] = 'Connection: keep-alive';
        } else {
            $header[] = 'Connection: close';
        }
        // 是否需要添加auth信息
        if (!empty($this->authData)) {
            $date = gmdate('D, j M Y H:i:s T');
            if ($this->customMethod) {
                $method = $this->customMethod;
            } elseif ($isPost) {
                $method = 'POST';
            } else {
                    $method = 'GET';
            }
            $arr_urlinfo = parse_url($url);
            if (!isset($arr_urlinfo['path'])) {
                return "error wrong url for auth $url";
            }
            $uri = $arr_urlinfo['path'];
            $string_to_sign = "$method $uri\n$date";
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->authData['secret'], true));
            $authorization = "MWS " . $this->authData['id'] . ":" . $signature;
            $header[] = "Date: $date";
            $header[] = "Authorization: $authorization";
        }
        // url
        curl_setopt($this->curl, CURLOPT_URL, $url);
        // 设置header
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        // UA
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->userAgent);
        // post
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $isPost ? $postData : null);
        if (is_string($this->customMethod)) {
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->customMethod);
        } else {
            curl_setopt($this->curl, CURLOPT_POST, $isPost);
        }
        // refer
        // 这里注释掉refer是因为腾讯的oauth openapi要求curl不能含有以下参数
        // 如果大家调用curl的时候需要启用refer，需要在其他地方启用，特此说明！！！
        //curl_setopt($this->curl, CURLOPT_REFERER, $this->location);

        if ($this->useCert) {
            $this->_setCertOpt();
        }
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, '_readHeaderCallback'));
        // 执行请求
        $body = curl_exec($this->curl);
        $errorCode = curl_errno($this->curl);
        // 执行成功
        if ($errorCode == CURLE_OK) {
            $this->debug && self::smartDebug($isPost ? 'POST' : 'GET', $url, $body);
            // 解码
            if ($charset) {
                $body = iconv($charset, 'UTF-8//IGNORE', $body);
            }
            // 更新cookie
            $this->refreshCookie();

            $result = false;
        } else {
            // 执行出错
            $result = $this->getErrMsg();
        }

        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, 'self::_emptyHeaderCallback');
        return $result;
    }

    /**
     * Header处理回调
     *
     * @param Resource $_nouse nouse
     * @param String   $header 一行header
     *
     * @access private
     * @return Integer
     */
    private function _readHeaderCallback($_nouse, $header)
    {
        // 设置 location
        if (!strncmp($header, "Location:", 9)) {
            $this->location = trim(substr($header, 9, -1));
        }
        // 设置cookie
        if (!strncmp($header, "Set-Cookie:", 11)) {
            $str = trim(substr($header, 11, -1));

            $pos = strpos($str, ';');
            if ($pos !== false) {
                $str = substr($str, 0, $pos);
            }

            $pos = strpos($str, '=');
            if ($pos !== false) {
                $name = trim(substr($str, 0, $pos));
                $value = trim(substr($str, $pos + 1));
                $this->cookie[$name] = $value;
            }
        }

        return strlen($header);
    }
    /**
     * 回调函数
     *
     * @param var $_nouse nouse
     * @param var $header header
     *
     * @access private
     * @return null
     */
    private static function _emptyHeaderCallback($_nouse, $header)
    {
    }

    /**
     * 比较
     *
     * @param var $a 参数a
     * @param var $b 参数b
     *
     * @access protected
     * @return null
     */
    protected function cmp($a, $b)
    {
        return strcasecmp($a, $b);
    }
        
    /**
     * 获取http code
     *
     * @access public
     * @return null
     */
    public function getHttpCode()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }
    /**
     * 获取curl错误编码
     *
     * @access pubic
     * @return int
     */
    public function getErrNo()
    {
        return curl_errno($this->curl);
    }
     /**
     * 获取curl出错信息
     *
     * @access public
     * @return string
     */
    public function getErrMsg()
    {
        return 'errno=' . curl_errno($this->curl) . ' error=' . curl_error($this->curl);
    }
     /**
     * 设置认证信息
     *
     * @access private
     * @return string
     */
    private function _setCertOpt()
    {
        // 设置https端口
        if (!empty($this->sslPort)) {
            curl_setopt($this->curl, CURLOPT_PORT, $this->sslPort);
        }
        // 重新设置header
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->sslHeader);
        // 设置证书路径
        curl_setopt($this->curl, CURLOPT_SSLCERT, $this->certPath);
        // 设置证书密码
        curl_setopt($this->curl, CURLOPT_SSLCERTPASSWD, $this->certPwd);
        // 设置证书类型
        curl_setopt($this->curl, CURLOPT_SSLCERTTYPE, $this->certType);
    }

    /**
     * 并发多个请求GET
     *
     * @param array  $urlprefix url信息
     * @param array  $params    参数数组，每个元素将转成query并和urlprefix拼接成一个url
     * @param string $body      请求返回数据数组。若全部成功则数组大小和请求数一致
     *
     * @access public
     * @return mixed
     */
    public function httpMultiGet($urlprefix, $params, &$body)
    {
        // 额外header
        $header = $this->header;
        // keep alive
        if ($this->keepAlive > 0) {
            $header[] = 'Keep-Alive: ' . $this->keepAlive;
            $header[] = 'Connection: keep-alive';
        } else {
            $header[] = 'Connection: close';
        }
        // 是否需要添加auth信息
        if (!empty($this->authData)) {
            $date = gmdate('D, j M Y H:i:s T');
            $method = 'GET';
            $arr_urlinfo = parse_url($urlprefix);
            if (!isset($arr_urlinfo['path'])) {
                return "error wrong url for auth $urlprefix";
            }
            $uri = $arr_urlinfo['path'];
            $string_to_sign = "$method $uri\n$date";
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->authData['secret'], true));
            $authorization = "MWS " . $this->authData['id'] . ":" . $signature;
            $header[] = "Date: $date";
            $header[] = "Authorization: $authorization";
        }

        $mh = curl_multi_init();
        $conn = array();
        foreach ($params as $i => $param) {
            $url = $urlprefix . '?'. http_build_query($param); 
            $conn[$i] = curl_init();
            curl_setopt($conn[$i], CURLOPT_URL, $url);
            curl_setopt($conn[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($conn[$i], CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, null);
            curl_setopt($conn[$i], CURLOPT_POST, 0);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        $err = false;
        // XXX: 有个改进版的Rolling curl并发方式，可以了解下
        // http://www.searchtb.com/2012/06/rolling-curl-best-practices.html
        $active = false;
        do {
            $status = curl_multi_exec($mh, $active);
            usleep(10000);
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        if ($status != CURLM_OK) {
            $err = 'curl_multi_exec fail';
        }

        $body = array();
        foreach ($conn as $i => $one) {
            $errno = curl_errno($one);
            if ($errno == CURLE_OK) {
                $body[$i] = curl_multi_getcontent($one);
            } else {
                $err = 'errno=' . $errno . ' error=' . curl_error($one);
            }
            curl_multi_remove_handle($mh, $one);
            curl_close($one);
        }
        curl_multi_close($mh);
        return $err;
    }
    /**
     * 并发多个请求GET
     *
     * @param array  $urlArr url参数
     * @param string $body   请求返回数据数组。若全部成功则数组大小和请求数一致
     *
     * @access public
     * @return mixed
     */
    public function httpMultiUrlGet($urlArr, &$body)
    {
        $mh = curl_multi_init();
        $conn = array();
        foreach ($urlArr as $i => $url) {
            $conn[$i] = curl_init();

            // 额外header
            $header = $this->header;
            // keep alive
            if ($this->keepAlive > 0) {
                $header[] = 'Keep-Alive: ' . $this->keepAlive;
                $header[] = 'Connection: keep-alive';
            } else {
                $header[] = 'Connection: close';
            }
            // 是否需要添加auth信息
            if (!empty($this->authData)) {
                $date = gmdate('D, j M Y H:i:s T');
                $method = 'GET';
                $arr_urlinfo = parse_url($url);
                if (!isset($arr_urlinfo['path'])) {
                    return "error wrong url for auth $url";
                }
                $uri = $arr_urlinfo['path'];
                $string_to_sign = "$method $uri\n$date";
                $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->authData['secret'], true));
                $authorization = "MWS " . $this->authData['id'] . ":" . $signature;
                $header[] = "Date: $date";
                $header[] = "Authorization: $authorization";
            }

            curl_setopt($conn[$i], CURLOPT_URL, $url);
            curl_setopt($conn[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($conn[$i], CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, null);
            curl_setopt($conn[$i], CURLOPT_POST, 0);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        $err = false;
        // XXX: 有个改进版的Rolling curl并发方式，可以了解下
        // http://www.searchtb.com/2012/06/rolling-curl-best-practices.html
        $active = false;
        do {
            $status = curl_multi_exec($mh, $active);
            usleep(1000);
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        if ($status != CURLM_OK) {
            $err = 'curl_multi_exec fail';
        }

        $body = array();
        foreach ($conn as $i => $one) {
            $errno = curl_errno($one);
            if ($errno == CURLE_OK) {
                $body[$i] = curl_multi_getcontent($one);
            } else {
                $err = 'errno=' . $errno . ' error=' . curl_error($one);
            }
            curl_multi_remove_handle($mh, $one);
            curl_close($one);
        }
        curl_multi_close($mh);
        return $err;
    }

    /**
     * Get请求
     *
     * @param String $url             请求url
     * @param String $body            响应文本
     * @param String $charset         字符集 (为空则不转换)
     * @param array  $performanceArgs 监控选项
     *
     * @access public
     * @return Mixed  false:成功; String:失败信息
     */
    public function httpGet($url, &$body, $charset = null, $performanceArgs = array())
    {
        $startTime = microtime(true);

        $res = $this->_httpExec($url, $body, $charset, false);

        $timeElapsed = intval((microtime(true) - $startTime) * 1000);

        return $res;
    }

    /**
     * 并发多个请求POST
     *
     * @param array  $urlprefix url信息
     * @param array  $params    参数数组
     * @param string $body      请求返回数据数组。若全部成功则数组大小和请求数一致
     *
     * @access public
     * @return mixed
     */
    public function httpMultiPost($urlprefix, $params, &$body)
    {
        // 额外header
        $header = $this->header;
        // keep alive
        if ($this->keepAlive > 0) {
            $header[] = 'Keep-Alive: ' . $this->keepAlive;
            $header[] = 'Connection: keep-alive';
        } else {
            $header[] = 'Connection: close';
        }
        // 是否需要添加auth信息
        if (!empty($this->authData)) {
            $date = gmdate('D, j M Y H:i:s T');
            $method = 'POST';
            $arr_urlinfo = parse_url($urlprefix);
            if (!isset($arr_urlinfo['path'])) {
                return "error wrong url for auth $urlprefix";
            }
            $uri = $arr_urlinfo['path'];
            $string_to_sign = "$method $uri\n$date";
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->authData['secret'], true));
            $authorization = "MWS " . $this->authData['id'] . ":" . $signature;
            $header[] = "Date: $date";
            $header[] = "Authorization: $authorization";
        }
        $mh = curl_multi_init();
        $conn = array();
        foreach ($params as $i => $param) {
            $url = $urlprefix;
            $conn[$i] = curl_init();
            curl_setopt($conn[$i], CURLOPT_URL, $url);
            curl_setopt($conn[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($conn[$i], CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $param);
            curl_setopt($conn[$i], CURLOPT_POST, 1);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        $err = false;
        // XXX: 有个改进版的Rolling curl并发方式，可以了解下
        // http://www.searchtb.com/2012/06/rolling-curl-best-practices.html
        $active = false;
        do {
            $status = curl_multi_exec($mh, $active);
            usleep(10000);
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        if ($status != CURLM_OK) {
            $err = 'curl_multi_exec fail';
        }

        $body = array();
        foreach ($conn as $i => $one) {
            $errno = curl_errno($one);
            if ($errno == CURLE_OK) {
                $body[$i] = curl_multi_getcontent($one);
            } else {
                $err = 'errno=' . $errno . ' error=' . curl_error($one);
            }
            curl_multi_remove_handle($mh, $one);
            curl_close($one);
        }
        curl_multi_close($mh);
        return $err;
    }
    /**
     * Post请求
     *
     * @param String $url             请求url
     * @param Array  $data            post数据
     * @param String $body            响应文本
     * @param String $charset         字符集 (为空则不转换)
     * @param bool   $isMulti         是否mutli格式
     * @param array  $performanceArgs 自定义的接口监控所需要的各种参数
     *
     * @access public
     * @return Mixed  false:成功; String:失败信息
     */
    public function httpPost($url, $data, &$body, $charset = null, $isMulti = false, $performanceArgs = array())
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $startTime = microtime(true);

        $ret = $this->_httpExec($url, $body, $charset, true, $data, $isMulti);

        $timeElapsed = intval((microtime(true) - $startTime) * 1000);

        return $ret;
    }
    /**
     * 分析复用_httpExec可行性，但目前其特殊逻辑太多，调用时无法满足需求
     *
     * @param String $url  请求url
     * @param Array  $data post数据
     *
     * @access public
     * @return Mixed
     */
    public function httpUploadFile($url, $data)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        //传递一个数组到CURLOPT_POSTFIELDS，cURL会把数据编码成 multipart/form-data，而传递一个URL-encoded字符串时，数据会被编码成 application/x-www-form-urlencoded
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        $header = $this->header;
        // 是否需要添加auth信息
        if (!empty($this->authData)) {
            $date = gmdate('D, j M Y H:i:s T');
            $method = 'POST';
            $arr_urlinfo = parse_url($url);
            if (!isset($arr_urlinfo['path'])) {
                return false;
            }
            $uri = $arr_urlinfo['path'];
            $string_to_sign = "$method $uri\n$date";
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->authData['secret'], true));
            $authorization = "MWS " . $this->authData['id'] . ":" . $signature;
            $header[] = "Date: $date";
            $header[] = "Authorization: $authorization";
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        $body = curl_exec($this->curl);
        return $body;
    }
    
    /**
     * 调试信息
     *
     * @param String $method http请求方式
     * @param String $url    请求url
     * @param String $str    文本
     *
     * @access public
     * @return Mixed
     */
    public static function smartDebug($method, $url, $str)
    {
        if (!Core::validStringIsUtf8($str)) {
            $str = iconv('GBK', 'UTF-8//IGNORE', $str);
        }
        echo '<div style="border:1px solid black; background-color:#ddd;" onclick="if (this.childNodes[1].style.display != \'block\') {this.childNodes[1].style.display = \'block\';} else {this.childNodes[1].style.display = \'none\';}  "><div>', $method, ': ', $url, '</div><div style="display:none;"><pre>', htmlspecialchars($str), '</pre></div></div>';
    }
 
    /**
     * 获取内容
     *
     * @param String $url  请求url
     * @param String $conf 配置
     *
     * @access public
     * @return Mixed
     */
    public static function curlGetContents($url, $conf = array())
    {
        $curl = new CUrlHttp($conf);
        $content = null;
        $result = $curl->httpGet($url, $content);
        if ($result === false) {
            return $content;
        } else {
            return false;
        }
    }
     /**
     * REST接口
     *
     * @param String $url        请求url
     * @param array  $data       请求数据
     * @param int    $returnCode 响应码
     * @param string $method     请求方式
     * @param string $userpass   用户密码
     * @param array  $mtAuthInfo 认证信息
     *
     * @access public
     * @return Mixed
     */
    public static function RESTRequest($url, $data = null, &$returnCode = null, $method = null, $userpass = null, $mtAuthInfo = null)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl, array(
            //CURLOPT_VERBOSE => true,
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'MIS/1.0',
            )
        );

        $headers = array('Content-type: application/json');

        if (!empty($data)) {
            if (is_array($data)) {
                $data = json_encode($data);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $m = 'POST';
        } else {
            $m = 'GET';
        }
        if (!empty($method)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        } else {
            $method = $m;
        }

        if (!empty($userpass)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $userpass);
        } elseif (!empty($mtAuthInfo)) {
            $date = gmdate('D, j M Y H:i:s T');
            $arr_urlinfo = parse_url($url);
            if (!isset($arr_urlinfo['path'])) {
                return null;
            }
            $uri = $arr_urlinfo['path'];
            $string_to_sign = "$method $uri\n$date";
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $mtAuthInfo['client_secret'], true));
            $authorization = "MWS " . $mtAuthInfo['client_id'] . ":" . $signature;
            $headers[] = "Date: $date";
            $headers[] = "Authorization: $authorization";

        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($curl);
        $returnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $body;
    }
}
