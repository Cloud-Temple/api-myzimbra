<?php

/*
 *
 * Copyright 2017 Maxime Désécot - Cloud Temple
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Description: Example of script in PHP to use the MyZimbra.net API
 *
 */

class MyZimbra {

	const base_api    = "/api/v1";
	const host_api    = "api.myzimbra.net";
	const method_api  = "POST";
	const port        = 443;
	const scheme      = "https";

	private $token = null;

	private $headers = array(
		'Accept: application/json',
		'Content-Type: application/json; charset=utf-8'
		);

	public function __construct() {}

	public function login($login, $password){
		$response = $this->_call("post", "auth", null, array('login' => $login, 'password' => $password));
		$this->_loginHandler($response);
	}

	private function _loginHandler($body) {
		$this->token = $body["data"]["session"]["token"];
		array_push($this->headers, "Authorization: Token token=".$this->token);
	}

	public function get_all_accounts($domain, $offset, $limit, $sortby, $sortascending) {
		$args = array("offset" => $offset, "limit" => $limit, "sortby" => $sortby, "sortascending" => $sortascending);
		$response = $this->_call("get", "accounts/".$domain, $args, null);
		return $response["data"]["accounts"];
	}

	public function create_account($body) {
		$response = $this->_call("post", "accounts", null, $body);
		return $response["data"];
	}

	private function _call($method, $path, $args = array(), $content = null, $options = array()) {
        if(!in_array($method, array('get', 'post', 'put', 'delete'))) throw new Exception('Method is not allowed', 405);
        
        if(substr($path, 0, 1) != '/') $path = '/'.$path;
        if($path == '/') throw new Exception('Endpoint is missing', 400);
        
        $url = self::scheme."://".self::host_api.self::base_api.$path;

        if ($args) $url .= '?'.implode('&', $this->flatten($args));
        
        $h = curl_init();
        curl_setopt($h, CURLOPT_URL, $url);
        if($content) curl_setopt($h, CURLOPT_POSTFIELDS, json_encode($content));
        curl_setopt($h, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($h, CURLOPT_PORT , self::port); 
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($h, CURLOPT_VERBOSE, 0); 
        
        switch($method) {
            case 'get' : break;
            case 'post' :
                curl_setopt($h, CURLOPT_POST, true);
                break;
            
            case 'put' :
                curl_setopt($h, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            
            case 'delete':
                curl_setopt($h, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($h);
        // echo "DEBUG";
        // echo $response;
        $error = curl_error($h);
        $code = (int)curl_getinfo($h, CURLINFO_HTTP_CODE);
        curl_close($h);
        
        if($error) throw new Exception('Client error : '.$error);
        
        if($code < 200 || $code > 299 ) {
            if(($method != 'post') || ($code != 201)) {
                throw new Exception('Http error '.$code.($response ? ' : '.$response : ''));
            }
        }
        
        if(!$response) throw new Exception('Empty response');
        
        return json_decode($response, true);
    }

	public function get($path, $args = array(), $options = array()) {
        return $this->_call('get', $path, $args, $options);
    }
    
    public function post($path, $args = array(), $content = null, $options = array()) {
        return $this->_call('post', $path, $args, $content, $options);
    }
    
    public function put($path, $args = array(), $content = null, $options = array()) {
        return $this->_call('put', $path, $args, $content, $options);
    }
    
    public function delete($path, $args = array(), $options = array()) {
        return $this->_call('delete', $path, $args, $options);
    }

    private function flatten($a, $p=null) {
        $o = array();
        ksort($a);
        foreach($a as $k => $v) {
            if(is_array($v)) {
                foreach($this->flatten($v, $p ? $p.'['.$k.']' : $k) as $s) $o[] = $s;
            }else $o[] = ($p ? $p.'['.$k.']' : $k).'='.$v;
        }
        return $o;
    }

}

$client = new MyZimbra();

// replace login and password by your credential MyZimbra
$client->login("login", "password");

$accounts = $client->get_all_accounts("domain.com", 0, 10, "name", 1);
for ($i=0; $i < count($accounts); $i++) {
	$account = $accounts[$i];
	echo $account["name"]."\n";
}

$attrs = array(
	"name"        => "jon.snow@domain.com",
	"password"    => "myzimbra!",
	"accountType" => "UPRO",
	"company"     => "CLOUD-TEMPLE",
	"description" => "Nouveau compte de test",
	"displayName" => "Jon SNOW",
	"mobile"      => "0666066606"
	);

$new_account = $client->create_account($attrs);
echo $new_account["id"];

?>