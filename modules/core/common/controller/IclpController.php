<?php

namespace modules\core\common\controller;

use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\core\common\model\JsonModel;
use \modules\core\common\model\ResultObj;

class IclpController {

	protected $rights_level = 'normal';

	public function __construct () {
		DB::connect(Util::getEnvironment(), $this->rights_level);
	}

	public function dispatch ($action) {
		$action = $this->beforeDispatch($action);
		if ($action) {
			$result = $this->{$action.'Action'}();
			return $this->postDispatch($action, $result);
		}
		return $action;
	}

	public function beforeDispatch ($action) {
		return $action;
	}

	public function postDispatch ($action, $dispatchResult) {
		return $dispatchResult;
	}

	public function getDefaultPlatform () {
		return 'mobile';
	}

	public function clearCookieAction () {
		foreach ($_COOKIE as $key => $val) {
			Util::deleteIclpCookie($key);
		}
		Util::prt($_COOKIE);
	}

	public function viewCookieAction () {
		Util::prt($_COOKIE);
	}

	public function loginAction () {
		Util::setIclpCookie('wx_auth_openid', WX_FAKE_LOGIN);
		Util::prt('Done');
	}

	public function getLongURLAction () {
		$result = new ResultObj(false);
		$url = $_POST['url'];
		if (empty($url)) {
			$result->setMessage('Invalid input short URL');
			return new JsonModel($result->toArray());
		}
		$res = DB::fetch("SELECT `long_url` FROM `view_short_url` WHERE `short_url` = ".DB::quote($url), CORE_DB_CONN);
		if ($res) {
			$result->setSuccess(true);
			$result->setData(array('long_url'=>$res['long_url'], 'short_url'=>$url));
		} else {
			$result->setMessage('Long URL not found');
		}
		return new JsonModel($result->toArray());
	}

	public function getAllShortURLsAction () {
		$result = new ResultObj(false);
		$res = DB::fetchAll("SELECT `long_url`, `short_url`, `clicks` FROM `view_short_url_tracking`", CORE_DB_CONN);
		if ($res) {
			$result->setSuccess(true);
			$result->setData($res);
		} else {
			$result->setMessage('API error');
		}
		return new JsonModel($result->toArray());
	}

	public function trackShortURLAction () {
		$result = new ResultObj(false);
		$url = $_GET['url'];
		if (empty($url)) {
			$result->setMessage('Invalid URL');
			return new JsonModel($result->toArray());
		}
		DB::exec(
			"INSERT INTO `short_url_visit_log`
				(`short_url`, `ip`, `ua`, `time`)
			VALUES
				(
					".DB::quote($url).",
					".DB::quote($_SERVER['REMOTE_ADDR']).",
					".DB::quote($_SERVER['HTTP_USER_AGENT']).",
					".time()."
				)", CORE_DB_CONN
		);
		header("Location: $url");
		exit;
	}

	public function loadJavaScriptAction () {
		header('Content-Type: application/javascript');
		$file = $_GET['file'];
		$version = empty($_GET['version']) ? '' : $_GET['version'];
		$jsFile = WEB_ROOT.DS.'js'.DS.$file;
		if (empty($file) || !file_exists($jsFile)) {
			exit;
		}
		$hashedFileName = md5($file.$version);
		$hashedFile = APP_DIR.DS.'assets'.DS.'js'.DS.$hashedFileName;
		if (!file_exists($hashedFile)) {
			$jsqueeze = new \Patchwork\JSqueeze();
			$minifiedJs = $jz->squeeze(
				file_get_contents($jsFile),
				true,	// $singleLine
				false,	// $keepImportantComments
				false	// $specialVarRx
			);
			file_put_contents($hashedFile, $minifiedJs);
		}
		include $hashedFile;
		exit;
	}

	public function loadCSSAction () {
		$file = $_GET['file'];
	}

}
