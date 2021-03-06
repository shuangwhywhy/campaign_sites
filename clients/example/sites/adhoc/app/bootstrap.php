<?php

date_default_timezone_set('UTC');
ini_set('memory_limit', '-1');
set_time_limit(0);
ini_set('max_execution_time', '0');
session_start();

define('DS', DIRECTORY_SEPARATOR);
define('APP_DIR', __DIR__);
define('WEB_DIR', str_replace('app', 'web', APP_DIR));
define('MODULE_DIR', preg_replace('/[\\/]sites[\\/].+$/', 'modules', APP_DIR));
define('CLIENT_DIR', preg_replace('/[\\/]sites[\\/].+$/', '', APP_DIR));
define('ROOT_DIR', preg_replace('/[\\/]clients[\\/].+$/', '', CLIENT_DIR));
define('SITES_DIR', CLIENT_DIR.DS.'sites');

require_once SITES_DIR.DS.'autoload.php';

$environment = apache_getenv('APPLICATION_ENVIRONMENT');
switch ($environment) {
	case 'live':
		define('ENV', 'live');
		break;
	case 'staging':
		define('ENV', 'staging');
		break;
	case 'test':
		define('ENV', 'test');
		break;
	default:
		define('ENV', 'dev');
		break;
}
include_once APP_DIR.DS.'config'.DS.ENV.DS.'application.conf.php';

if (DEBUG_MODE) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
} else {
	error_reporting(0);
}



// The Main App Class:

use \modules\core\common\model\JsonModel;
use \modules\core\common\model\ViewModel;
use \modules\core\common\model\ResultObj;

class IclpClass {

	public function run () {
		$ctrl = 'page';
		$action = 'index';
		if (!empty($_GET['ctrl'])) {
			$ctrl = $_GET['ctrl'];
		}
		if (!empty($_GET['action'])) {
			$action = $_GET['action'];
		}

		$controller = null;

		switch ($ctrl) {
			case 'page':
				$controller = new \clients\example\sites\adhoc\app\controller\PageController();
				break;
			case 'api':
				$controller = new \clients\example\sites\adhoc\app\controller\ApiController();
				break;
			case 'admin':
				$controller = new \clients\example\sites\adhoc\app\controller\AdminController();
				break;
			case 'report':
				$controller = new \clients\example\sites\adhoc\app\controller\ReportController();
				break;
			default:
				die('Controller \''.$ctrl.'\' not found');
		}

		$retObj = $controller->dispatch($action);
		if ($retObj instanceof JsonModel) {
			header('Content-Type: application/json');
			echo $retObj->toString();
			exit;
		} else if ($retObj instanceof ViewModel) {
			$platform = null;
			if (!empty($_GET['platform'])) {
				$platform = $_GET['platform'];
			}
			if (empty($platform)) {
				$platform = $retObj->getDefaultPlatform();
			}
			if (empty($platform)) {
				$platform = $controller->getDefaultPlatform();
			}
			$retObj->render($platform);
			exit;
		} else if ($retObj instanceof ResultObj) {
			$json = new JsonModel($retObj->toArray());
			header('Content-Type: application/json');
			echo $json->toString();
			exit;
		} else {
			die($retObj);
		}
	}

}

?>
