<?php

namespace modules\core\common\helper;

class Util {

	public static function getCurrentURL ($withQueryString=true) {
		if ($withQueryString) {
			return (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		} else {
			$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$url = preg_replace('/\?.*$/', '', $url);
			return $url;
		}
	}

	public static function getRandomStr ($len=8, $dict='A-Z0-9a-z') {
		$dict = str_replace('\w', 'a-zA-Z0-9_', $dict);
		$dict = str_replace('A-Z', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $dict);
		$dict = str_replace('a-z', 'abcdefghijklmnopqrstuvwxyz', $dict);
		$dict = str_replace('0-9', '0123456789', $dict);
		$randomStr = '';
		for ($i=0; $i<$len; $i++) {
			$rand = mt_rand(0, strlen($dict)-2);
			$randomStr .= substr($dict, $rand, 1);
		}
		return $randomStr;
	}

	public static function getEnvironment () {
		return ENV;
	}

	public static function pr ($obj='') {
		echo '<pre>';
		print_r($obj);
		echo '</pre>';
		return true;
	}

	public static function prt ($obj='') {
		self::pr($obj);
		exit;
	}

	public static function dbg ($obj='') {
		echo '<pre>';
		var_dump($obj);
		echo '</pre>';
		return true;
	}

	public static function dbgt ($obj='') {
		self::dbg($obj);
		exit;
	}

	public static function dbDebug ($message) {
		DB::exec("INSERT INTO `debug` (`output`, `time`) VALUES (".DB::quote(print_r($message, true)).", ".time().")", CORE_DB_CONN);
	}

	public static function post ($url, $data=array(), $isJson=true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		if ($isJson) {
			$result = json_decode($result, true);
		}
		return $result;
	}

	public static function xmlPost ($url, $data) {
		$headers = array();
		$headers[] = 'Content-type: text/xml; charset=utf-8';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public static function sslPost ($url, $data=array(), $isJson=true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		if ($isJson) {
			$result = json_decode($result, true);
		}
		return $result;
	}

	public static function request ($url, $isJson=true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		if ($isJson) {
			$result = json_decode($result, true);
		}
		return $result;
	}

	public static function sslRequest ($url, $isJson=true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		if ($isJson) {
			$result = json_decode($result, true);
		}
		return $result;
	}

	public static function setIclpCookie ($name, $value, $expire=0, $effectNow=true) {
		if ($expire == 0) {
			$expire = time() + 86400;	// 1 days
		}
		// $name = md5($name);
		$key = 'da3e8fd2';
		$cookie = self::encrypt($key, $value);
		if ($effectNow) {
			$_COOKIE[$name] = $cookie;
		}
		return @setcookie($name, $cookie, $expire, '/', null, false, true);
	}

	public static function getIclpCookie ($name) {
		// $name = md5($name);
		$key = 'da3e8fd2';
		if (!isset($_COOKIE[$name])) {
			return null;
		} else {
			return self::decrypt($key, $_COOKIE[$name]);
		}
	}

	public static function deleteIclpCookie ($name) {
		// $name = md5($name);
		unset($_COOKIE[$name]);
		return self::setIclpCookie($name, null, time()-100);
	}

	public static function encrypt ($key, $payload) {
		return $payload;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
		return base64_encode(mcrypt_encrypt(MCRYPT_DES, $key, $payload, MCRYPT_MODE_ECB, $iv));
	}

	public static function decrypt ($key, $garble) {
		return $garble;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
		return preg_replace('/\0+$/', '', mcrypt_decrypt(MCRYPT_DES, $key, base64_decode($garble), MCRYPT_MODE_ECB, $iv));
	}

	public static function getActionName ($url='') {
		if (empty($url)) {
			$url = self::getCurrentURL();
		}
		$queryString = parse_url($url, PHP_URL_QUERY);
		$params = array();
		parse_str($queryString, $params);
		if (empty($params) || empty($params['action'])) {
			return 'index';
		} else {
			return $params['action'];
		}
	}

	public static function getCtrlName ($url='') {
		if (empty($url)) {
			$url = self::getCurrentURL();
		}
		$queryString = parse_url($url, PHP_URL_QUERY);
		$params = array();
		parse_str($queryString, $params);
		if (empty($params) || empty($params['ctrl'])) {
			return 'page';
		} else {
			return $params['ctrl'];
		}
	}

	public static function allow ($host = false) {
		$clientIP = $_SERVER['REMOTE_ADDR'];

		if (empty($host) || $host == 'none' || $host = 'false') {
			die('Host not allowed');
		} else if (in_array($host, array('localhost', 'local'))) {
			if (!in_array($clientIP, array('127.0.0.1', $_SERVER['SERVER_ADDR']))) {
				die('Host not allowed');
			}
		} else {
			if ($host != $clientIP) {
				die('Host not allowed');
			}
		}
		return true;
	}

	public static function fuzzMobile ($mobile, $from = 3, $to = 7) {
		return substr($mobile, 0, $from).str_repeat('*', ($to - $from)).substr($mobile, $to);
	}

	public static function digestAuth () {
		$authConfig = include APP_DIR.DS.'config'.DS.self::getEnvironment().DS.'auth.conf.php';
		$username = null;
		$password = null;

		// mod_php
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];

		// most other servers
		} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0) {
				list($username, $password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			}
		}

		if (is_null($username)) {
			header('WWW-Authenticate: Basic realm="My Realm"');
			header('HTTP/1.0 401 Unauthorized');
			return false;

		} else if (isset($authConfig[$username]) && $password === $authConfig[$username]) {
			return $username;
		} else {
			header('WWW-Authenticate: Basic realm="My Realm"');
			header('HTTP/1.0 401 Unauthorized');
			return false;
		}
	}

	public static function isCampaignStarted () {
		return time() >= CAMPAIGN_START_TIME;
	}

	public static function isCampaignEnded () {
		return time() >= CAMPAIGN_END_TIME;
	}

	public static function isCampaignTime () {
		return self::isCampaignStarted() && !self::isCampaignEnded();
	}

}
