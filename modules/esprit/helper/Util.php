<?php

namespace modules\esprit\helper;

use \modules\core\common\model\XmlModel;
use \modules\sns\wechat\helper\Wechat;

class Util extends \modules\core\common\helper\Util {

	public static function sendSms ($smsAccount, $smsPass, $mobile, $content) {
	}

	public static function isTestUser ($wid = false, $configName = 'testers.conf.php') {
		if (empty($wid)) {
			$user = Wechat::getCurrentUser();
			$wid = $user->openid;
		}
		$testUsers = include APP_DIR.DS.'config'.DS.self::getEnvironment().DS.$configName;
		return in_array($wid, $testUsers);
	}

}
