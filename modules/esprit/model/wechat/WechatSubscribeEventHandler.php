<?php

namespace modules\esprit\model\wechat;

use \modules\sns\wechat\model\WechatEvent;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\model\message\TextMessage;
use \modules\core\common\helper\DB;
use \modules\core\common\helper\Util;
use \modules\esprit\helper\Logger;

class WechatSubscribeEventHandler extends \modules\sns\wechat\model\message_handler\event_handler\WechatSubscribeEventHandler {

	public function beforeProcessEvent (WechatEvent $event) {
		parent::beforeProcessEvent($event);

		$wid = $event->getWid();
		// update local database:
		DB::exec("UPDATE `ms_member_member` SET `is_follow` = 1 WHERE `weixin_open_id` = ".DB::quote($wid), LEGACY_DB_CONN);

		// call defacto to update ECDB:
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/home/vip/defollow_api.php?wid='.$wid.'&type=follow';
		$result = Util::request($url, false);
		Logger::logMemberApi($_SERVER['REMOTE_ADDR'], 'member_follow', $url, $result, $wid);

		// Get Esprit Member ID from ECDB:
		$user = Wechat::getMemberDetail($wid);
		if ($user) {
			$memberResult = Util::post(
				'http://'.$_SERVER['SERVER_NAME'].'/home/vip/get_member_info.php',
				array('auth_key' => 'P4J(&Y6ijO*Ij@p8o', 'openid' => $wid)
			);
			if ($memberResult && $memberResult['success']) {
				DB::exec("UPDATE `wechat_users` SET `member_id` = ".DB::quote($memberResult['data']['customerdata']['customerid'])." WHERE `wid` = ".DB::quote($wid), CORE_DB_CONN);
			}
		}

		// send original welcome message:
		$message = new TextMessage($wid, "感谢关注ESPRIT！我们将在这里和你分享时尚新品与穿搭灵感，更有新鲜活动和丰富礼遇等你来赢！\n\n现在<a href=\"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6f3b86e53270cbf8&redirect_uri=http://wechat.esprit.cn/home/weixinauth.php&response_type=code&scope=snsapi_base&state=fd2417d62e7a194ae9fbf2df29d76565#wechat_redirect\">注册</a>/<a href=\"https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6f3b86e53270cbf8&redirect_uri=http://wechat.esprit.cn/home/weixinauth.php&response_type=code&scope=snsapi_base&state=527ef2507a23f943bdbf96360eb09651#wechat_redirect\">绑定</a>微信会员，即刻开始享受会员专属礼遇。");
		Wechat::sendCSMessage($message);
		return true;
	}

}
