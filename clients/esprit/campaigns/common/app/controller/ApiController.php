<?php

namespace campaigns\common\app\controller;

use \modules\core\common\helper\DB;
use \modules\esprit\helper\Util;
use \modules\core\common\model\JsonModel;
use \modules\core\common\model\XmlModel;
use \modules\core\common\model\ResultObj;
use \modules\core\common\model\RawModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\helper\Logger;
use \modules\sns\wechat\model\message\Article;
use \modules\sns\wechat\model\message\NewsMessage;
use \modules\sns\wechat\model\message\TextMessage;
use \modules\sns\wechat\model\message_handler\WechatTextMessageHandler;
use \modules\sns\wechat\model\message_handler\WechatImageMessageHandler;
use \modules\sns\wechat\model\message_handler\WechatAudioMessageHandler;
use \modules\sns\wechat\model\message_handler\WechatEventMessageHandler;
use \modules\sns\wechat\model\message_handler\WechatLocationMessageHandler;

class ApiController extends \modules\sns\wechat\controller\WechatController {

	public function beforeDispatch ($action) {
		return parent::beforeDispatch($action);
	}

	public function postDispatch ($action, $dispatchResult) {
		$ret = null;
		if ($dispatchResult instanceof JsonModel || $dispatchResult instanceof XmlModel) {
			$ret = $dispatchResult->toString();
		}
		Logger::logApi($_SERVER['REMOTE_ADDR'], $action, Util::getCurrentURL(), $_POST, $ret);
		return parent::postDispatch($action, $dispatchResult);
	}

	protected function getUserTextMessageHandler () {
		return new WechatTextMessageHandler();
	}

	protected function getUserImageMessageHandler () {
		return new WechatImageMessageHandler();
	}

	protected function getUserAudioMessageHandler () {
		return new WechatAudioMessageHandler();
	}

	protected function getUserEventMessageHandler () {
		return new WechatEventMessageHandler();
	}

	protected function getUserLocationMessageHandler () {
		return new WechatLocationMessageHandler();
	}

	public function handleWechatMessageAction () {
		$data = $_POST['data'];
		$textHandler = $this->getUserTextMessageHandler();
		$imageHandler = $this->getUserImageMessageHandler();
		$audioHandler = $this->getUserAudioMessageHandler();
		$eventHandler = $this->getUserEventMessageHandler();
		$locationHandler = $this->getUserLocationMessageHandler();
		Wechat::handleUserMessage($data, $textHandler, $imageHandler, $audioHandler, $eventHandler, $locationHandler);
	}

	public function logShareAction () {
		if (empty($_POST['wid']) || empty($_POST['page']) || empty($_POST['url'])) {
			$result = new ResultObj(false, 100, null, 'Invalid Request');
			return new JsonModel($result->toArray());
		}
		$result = Logger::logWxShare(Wechat::getOpenID(), $_POST['wid'], $_POST['page'], $_POST['url'], $_POST['type']);
		return new ResultObj(!!$result);
	}

	public function trackingAction () {
	}

}
