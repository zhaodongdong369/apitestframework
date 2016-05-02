<?php

class CUrlHttp
{
    /**
     * cookie 字符串
     * 用于CURLOPT_COOKIE
     * @var String
     */
    private $cookiestr;
    /**
     * cookie 前缀
     */
    public $cookie_prefix;
    /**
     * 最近一次访问url
     * @var String
     */
    public $location;
    /**
     * curl资源
     * @var Resource
     */
    private $curl;
    /**
     * cookie数组
     * @var Array
     */
    public $cookie;

    /**
     * 是否开启debug模式
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
     * @var Integer
     */
    public $keepAlive = 300;

    /**
     * @var array BA 认证信息
     */
    protected $authData;

    /**
     * @var array 电影认证信息
     */
    protected $movieAuthData;
    
    /**
     * 用户代理
     * @var String
     */
    public $userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322)';
    /**
     * 全局超时
     * @var Integer
     */
    protected $globalTimeout = 30;
    /**
     * 连接超时
     * @var Integer
     */
    protected $connTimeout = 10;
    /**
     * 是否使用证书
     * @var Bool
     */
    public $useCert = false;
    /**
     * 若使用了证书要设置的属性
     *
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
     * @param Array $conf
     *        其中globalTimeout和connTimeout单位为秒，
     *        但可以使用小数，以实现按毫秒设置超时
     */
    public function __construct($conf = null)
    {
        // 修改默认配置
        if (!empty($conf) && is_array($conf)) {
            foreach ($conf as $key => $val) {
                $this->$key = $val;
            }
        }
        $this->curlInit();
    }

    public function __destruct()
    {
        is_resource($this->curl) && curl_close($this->curl);
    }

    public function curlClose()
    {
        is_resource($this->curl) && curl_close($this->curl);
    }

    public function setOpt($key, $val)
    {
        curl_setopt($this->curl, $key, $val);
    }

    public function setGlobalTimeout($timeout) {
        $this->globalTimeout = $timeout;
        $this->setOpt(CURLOPT_TIMEOUT_MS, $timeout * 1000);

        // 如果设置小于1s的超时，CURL会在DNS解析阶段立即超时
        // 设置 CURLOPT_NOSIGNAL 为1可以绕开这一问题
        if ($this->globalTimeout < 1 || $this->connTimeout < 1) {
            $this->setOpt(CURLOPT_NOSIGNAL, 1);
        }
    }

    public function setConnTimeout($timeout) {
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
     */
    private function curlInit()
    {
        // init
        $this->curl = curl_init();
        // cookie truncate
        $this->cookie = array();
        curl_setopt_array($this->curl, array(
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
        ));

        // 如果设置小于1s的超时，CURL会在DNS解析阶段立即超时
        // 设置 CURLOPT_NOSIGNAL 为1可以绕开这一问题
        if ($this->globalTimeout < 1 || $this->connTimeout < 1) {
            curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        }
    }

    /**
     * 更新cookie
     *
     */
    public function refreshCookie()
    {
        $this->cookiestr = '';
        foreach ($this->cookie as $key => $val) {
            $this->cookiestr .= $key . '=' . $val . '; ';
        }
        if ((!empty($this->cookie_prefix)) && count($this->cookie) !== 0) {
            $this->cookiestr = $this->cookie_prefix . '; ' . $this->cookiestr;
        }
        $this->cookiestr && curl_setopt($this->curl, CURLOPT_COOKIE, $this->cookiestr);
    }

    /**
     * 根据parse_url数组拼接url
     * parse_url的函数
     * @param  Array  $arr
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

    public function setAuthInfo($client_id, $client_secret)
    {
        $this->authData = array(
                'id'        => $client_id,
                'secret'    => $client_secret,
            );
    }

    public function setCustomMethod($method)
    {
        $this->customMethod = $method;
    }

    public function addHeader($header)
    {
        $this->header[] = $header;
    }

    /**
     * 执行http请求
     *
     * @param  String $url 请求url
     * @param  String &$body 响应文本
     * @param  String $charset 响应字符集 (为空则不转换)
     * @param  Boolean $isPost 是否为post请求
     * @param  Mixed $postData post数据
     * @param  bool $isMulti
     * @return Mixed   false:成功; String:失败信息
     */
    private function httpExec($url, &$body, $charset, $isPost, $postData = null, $isMulti = false)
    {
        // 更新cookie
        $this->refreshCookie();
        // 更新location
        $this->location = $url;
        // 额外header
        $header = $this->header;
        if ($isMulti) {
            $header[] = 'Content-Type: multipart/form-data; boundary=' . OAuthUtil::$boundary ;
            $header[] = 'Content-Length: ' . strlen($postData) ;
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
            $this->setCertOpt();
        }
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'readHeaderCallback'));
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

        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, 'self::emptyHeaderCallback');
        return $result;
    }

    /**
     * header处理回调
     *
     * @param  Resource $_nouse
     * @param  String   $header 一行header
     * @return Integer
     */
    private function readHeaderCallback($_nouse, $header)
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

    private static function emptyHeaderCallback($_nouse, $header)
    {
    }

    private function cmp($a, $b)
    {
        return strcasecmp($a, $b);
    }

    public function getHttpCode()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    public function getErrNo()
    {
        return curl_errno($this->curl);
    }

    public function getErrMsg()
    {
        return 'errno=' . curl_errno($this->curl) . ' error=' . curl_error($this->curl);
    }

    private function setCertOpt()
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
    * @brief 并发多个请求GET
    * @param $urlprefix
    * @param $params 参数数组，每个元素将转成query并和urlprefix拼接成一个url
    * @param $body 请求返回数据数组。若全部成功则数组大小和请求数一致
    * @return 
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
    * @brief 并发多个请求GET
    * @param $urlArr
    * @param $body 请求返回数据数组。若全部成功则数组大小和请求数一致
    * @return 
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
     * get请求
     *
     * @param  String $url     请求url
     * @param  String &$body   响应文本
     * @param  String $charset 字符集 (为空则不转换)
     * @param  array $performanceArgs 自定义的接口监控所需要的各种参数,
               具体可以包括如下字段:
                  String apiName  api的名称
                  float sampleRate  采样率
                  String project 性能监控平台的项目token，若不指定则使用默认的token
                  String hosts 性能监控平台parser的地址，若不指定则使用默认的host
     * @return Mixed  false:成功; String:失败信息
     */
    public function httpGet($url, &$body, $charset = null, $performanceArgs = array())
    {
        $startTime = microtime(true);

        $res = $this->httpExec($url, $body, $charset, false);

        $timeElapsed = intval((microtime(true) - $startTime) * 1000);

        return $res;
    }

    /**
    * @brief 并发多个请求POST
    * @param $urlprefix
    * @param $params 参数数组
    * @param $body 请求返回数据数组。若全部成功则数组大小和请求数一致
    * @return 
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
     * post请求
     *
     * @param  String $url 请求url
     * @param  Array $data post数据
     * @param  String &$body 响应文本
     * @param  String $charset 字符集 (为空则不转换)
     * @param bool $isMulti
     * @param  array $performanceArgs 自定义的接口监控所需要的各种参数,
               具体可以包括如下字段:
                  String apiName  api的名称
                  float sampleRate  采样率
                  String project 性能监控平台的项目token，若不指定则使用默认的token
                  String hosts 性能监控平台parser的地址，若不指定则使用默认的host
     * @return Mixed  false:成功; String:失败信息
     */
    public function httpPost($url, $data, &$body, $charset = null, $isMulti = false, $performanceArgs = array())
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $startTime = microtime(true);

        $ret = $this->httpExec($url, $body, $charset, true, $data, $isMulti);

        $timeElapsed = intval((microtime(true) - $startTime) * 1000);

        return $ret;
    }

    //TODO 分析复用httpExec可行性，但目前其特殊逻辑太多，调用时无法满足需求
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

    public static function smartDebug($method, $url, $str)
    {
        if (!Core::validStringIsUtf8($str)) {
            $str = iconv('GBK', 'UTF-8//IGNORE', $str);
        }
        echo '<div style="border:1px solid black; background-color:#ddd;" onclick="if (this.childNodes[1].style.display != \'block\') {this.childNodes[1].style.display = \'block\';} else {this.childNodes[1].style.display = \'none\';}  "><div>', $method, ': ', $url, '</div><div style="display:none;"><pre>', htmlspecialchars($str), '</pre></div></div>';
    }

    /**
        file_get_contents 替代品
        返回值同file_get_contents
     */
    public static function curl_get_contents($url, $conf = array())
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

    public static function RESTRequest($url, $data = null, &$returnCode = null, $method = null, $userpass = null, $mtAuthInfo = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            //CURLOPT_VERBOSE => true,
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'MIS/1.0',
        ));

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

