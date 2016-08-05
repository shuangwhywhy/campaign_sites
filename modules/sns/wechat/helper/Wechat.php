<?php

namespace modules\sns\wechat\helper;

use \modules\core\common\helper\DB;
use \modules\core\common\model\ResultObj;
use \modules\core\common\helper\Util;
use \modules\sns\wechat\helper\Logger;
use \modules\sns\wechat\model\User;
use \modules\sns\wechat\model\message\TemplateMessage;
use \modules\sns\wechat\model\message\CardMessage;
use \modules\sns\wechat\model\message\NewsMessage;
use \modules\sns\wechat\model\message\Article;
use \modules\sns\wechat\model\Coupon;
use \modules\sns\wechat\model\WechatMenu;
use \modules\sns\wechat\model\WechatMenuButton;
use \modules\core\common\model\XmlModel;

class Wechat {

	private static $currentUser = null;
	private static $config = array();

	public static function getConfig ($openID) {
		if (!empty(self::$config[$openID])) {
			return self::$config[$openID];
		}
		$config = DB::fetch("SELECT * FROM `a5_sys_myweixin` WHERE `weixin_code` = ".DB::quote($openID), LEGACY_DB_CONN);
		self::$config[$openID] = $config;
		return $config;
	}

	public static function checkSignature () {
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$token = self::getToken();
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}

	public static function getCurrentUser () {
		if (!self::$currentUser) {
			self::$currentUser = new User();
		}
		return self::$currentUser;
	}

	public static function setCurrentUser (User $user) {
		self::$currentUser = $user;
	}

	public static function getOpenID () {
		return WX_OPEN_ID;
	}

	public static function getAppID () {
		$config = self::getConfig(self::getOpenID());
		if (!empty($config)) {
			return $config['weixin_appId'];
		} else {
			return null;
		}
	}

	public static function getToken () {
		$config = self::getConfig(self::getOpenID());
		if (!empty($config)) {
			return $config['weixin_token'];
		} else {
			return null;
		}
	}

	public static function getAppSecret () {
		$config = self::getConfig(self::getOpenID());
		if (!empty($config)) {
			return $config['weixin_appSecret'];
		} else {
			return null;
		}
	}

	public static function handleUserMessage ($data, $textHandler, $imageHandler, $audioHandler, $eventHandler, $locationHandler) {
		$xml = XmlModel::parseSimpleXmlString($data);
		$arrData = $xml->getData();
		$open_id = $arrData['ToUserName'];
		$wid = $arrData['FromUserName'];
		Logger::logWxMessage($open_id, $wid, $data);
		switch ($arrData['MsgType']) {
			case 'text':
				$textHandler->processMessage($arrData);
				break;

			case 'event':
				$eventHandler->processMessage($arrData);
				break;

			case 'location':
				$locationHandler->processMessage($arrData);
				break;
		}
		return '';
	}

	public static function getAuthCallbackUrl ($redirectUrl='') {
		if (empty($redirectUrl)) {
			$redirectUrl = Util::getCurrentURL();
		}
		if (strpos(WX_AUTH_CALLBACK_URL, '?') === false) {
			return WX_AUTH_CALLBACK_URL.'?iclp_redirect='.rawurlencode($redirectUrl);
		} else {
			return WX_AUTH_CALLBACK_URL.'&iclp_redirect='.rawurlencode($redirectUrl);
		}
	}

	public static function getAuthState () {
		return WX_AUTH_STATE;
	}

	public static function auth () {
		$callback = rawurlencode(self::getAuthCallbackUrl());
		header('Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::getAppID().'&redirect_uri='.$callback.'&response_type=code&scope=snsapi_userinfo&state='.self::getAuthState().'#wechat_redirect');
	}

	public static function getAccessToken ($refresh=false) {
		if ($refresh) {
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::getAppID().'&secret='.self::getAppSecret();
			$result = Util::sslRequest($url);
			Logger::logWxApi(self::getOpenID(), 'get_access_token', $url, $result);
			if (is_array($result) && empty($result['errcode'])) {
				DB::exec("UPDATE `a5_sys_myweixin` SET
							`weixin_access_token` = ".DB::quote($result['access_token']).",
							`weixin_access_expire` = ".DB::quote($result['expires_in']).",
							`weixin_access_addtime` = ".time()."
							WHERE `weixin_code` = ".DB::quote(self::getOpenID()), LEGACY_DB_CONN);
				return $result['access_token'];
			} else {
				return false;
			}
		} else {
			$config = self::getConfig(self::getOpenID());
			$accessExpire = $config['weixin_access_expire'];
			$accessTime = $config['weixin_access_addtime'];
			if ($accessTime < time() - $accessExpire + 2) {
				return self::getAccessToken(true);
			}
			return $config['weixin_access_token'];
		}
	}

	public static function validateAccessToken ($accessToken) {
		if (empty($accessToken)) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$accessToken;
		$result = Util::sslRequest($url);
		if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			return false;
		} else {
			return true;
		}
	}

	private static function getJsApiTicket ($refresh=false, $refreshAccessToken=false) {
		if ($refresh) {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=jsapi';
			$result = Util::sslRequest($url);
			Logger::logWxApi(self::getOpenID(), 'get_js_api_ticket', $url, $result);
			if (is_array($result) && empty($result['errcode'])) {
				DB::exec("UPDATE `a5_sys_myweixin` SET
							`weixin_jsapi_ticket` = ".DB::quote($result['ticket']).",
							`weixin_jsapi_ticket_expire` = ".DB::quote($result['expires_in']).",
							`weixin_jsapi_ticket_addtime` = ".time()."
							WHERE `weixin_code` = ".DB::quote(self::getOpenID()), LEGACY_DB_CONN);
				return $result['ticket'];
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::getJsApiTicket($refresh, true);
			} else {
				return false;
			}
		} else {
			$config = self::getConfig(self::getOpenID());
			$ticket = $config['weixin_jsapi_ticket'];
			$ticketExpire = $config['weixin_jsapi_ticket_expire'];
			$ticketTime = $config['weixin_jsapi_ticket_addtime'];
			if (empty($ticket) || empty($ticketExpire) || empty($ticketTime) || $ticketTime < time() - $ticketExpire + 2) {
				return self::getJsApiTicket(true);
			}
			return $ticket;
		}
	}

	public static function getJsApiSignature () {
		$fields = array(
			'jsapi_ticket' => self::getJsApiTicket(),
			'noncestr' => Util::getRandomStr(16),
			'timestamp' => time(),
			'url' => Util::getCurrentURL()
		);
		$pairs = array();
		foreach ($fields as $key => $val) {
			$pairs[] = $key.'='.$val;
		}
		$str = implode('&', $pairs);
		$signature = sha1($str);
		$fields['signature'] = $signature;
		return $fields;
	}

	private static function getCardApiTicket ($refresh=false, $refreshAccessToken=false) {
		if ($refresh) {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=wx_card';
			$result = Util::sslRequest($url);
			Logger::logWxApi(self::getOpenID(), 'get_card_api_ticket', $url, $result);
			if (is_array($result) && empty($result['errcode'])) {
				DB::exec("UPDATE `a5_sys_myweixin` SET
							`weixin_cardapi_ticket` = ".DB::quote($result['ticket']).",
							`weixin_cardapi_ticket_expire` = ".DB::quote($result['expires_in']).",
							`weixin_cardapi_ticket_addtime` = ".time()."
							WHERE `weixin_code` = ".DB::quote(self::getOpenID()), LEGACY_DB_CONN);
				return $result['ticket'];
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::getCardApiTicket($refresh, true);
			} else {
				return false;
			}
		} else {
			$config = self::getConfig(self::getOpenID());
			$ticket = $config['weixin_cardapi_ticket'];
			$ticketExpire = $config['weixin_cardapi_ticket_expire'];
			$ticketTime = $config['weixin_cardapi_ticket_addtime'];
			if (empty($ticket) || empty($ticketExpire) || empty($ticketTime) || $ticketTime < time() - $ticketExpire + 2) {
				return self::getCardApiTicket(true);
			}
			return $ticket;
		}
	}

	public static function getCardApiSignature (array $fields=array()) {
		$fields['api_ticket'] = self::getCardApiTicket();
		$fields['nonce_str'] = Util::getRandomStr(16);
		$fields['timestamp'] = time();
		if (isset($fields['outer_id'])) {
			unset($fields['outer_id']);
		}
		asort($fields, SORT_STRING);
		$str = implode('', $fields);
		$signature = sha1($str);
		$fields['signature'] = $signature;
		return $fields;
	}

	public static function getMemberOpenIDList ($nextWID='', $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$accessToken.'&next_openid='.$nextWID;
		$result = Util::sslRequest($url);
		Logger::logWxApi(self::getOpenID(), 'get_member_list', $url, $result);
		if (is_array($result) && empty($result['errcode'])) {
			return $result;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::getMemberOpenIDList($nextWID, true);
		} else {
			return false;
		}
	}

	public static function getMemberList ($nextWID='') {
		$users = array();
		$result = self::getMemberOpenIDList($nextWID);
		if ($result) {
			$list = $result['data']['openid'];
			if (!empty($list)) {
				foreach ($list as $wid) {
					$user = new User($wid, false);
					if ($user->loadUserInfoFromDB()) {
						$users[] = $user;
					} else {
						$user = self::getMemberDetail($wid);
						if ($user) {
							$users[] = $user;
						}
					}
				}
			}
		}
		return $users;
	}

	public static function getAllMemberOpenIDList (&$resultList, $nextWID='') {
		$count = 0;
		do {
			$result = self::getMemberOpenIDList($nextWID);
			if ($result) {
				$count += $result['count'];
				$resultList = array_merge($resultList, $result['data']['openid']);
				$nextWID = $result['next_openid'];
			} else {
				$nextWID = '';
			}
		} while (!empty($nextWID) && $count < $result['total'] && $count == 10000);
		return true;
	}

	public static function getAllMemberList ($nextWID='') {
		$users = array();
		$list = array();
		self::getAllMemberOpenIDList($list, $nextWID);
		if (!empty($list)) {
			foreach ($list as $wid) {
				$user = new User($wid, false);
				if ($user->loadUserInfoFromDB()) {
					$users[] = $user;
				} else {
					$user = self::getMemberDetail($wid);
					if ($user) {
						$users[] = $user;
					}
				}
			}
		}
		return $users;
	}

	public static function getMemberDetail ($openID, $fromAPI=false, $refreshAccessToken=false, $autoSave=true) {
		$user = new User($openID, false);

		$dbResult = $user->loadUserInfoFromDB();
		$shouldRefresh = false;
		if ($dbResult) {
			if (is_null($user->language)) {
				$shouldRefresh = true;
			}
		}

		// if user is from web page auth, then language is null
		if ($shouldRefresh || $fromAPI || !$dbResult) {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$accessToken.'&openid='.$openID.'&lang=zh_CN';
			$result = Util::sslRequest($url);
			Logger::logWxApi(self::getOpenID(), 'get_member_detail', $url, $result);
			if (is_array($result) && empty($result['errcode'])) {
				$user->account_open_id = self::getOpenID();
				$user->subscribe = $result['subscribe'];
				$user->nickname = $result['nickname'];
				$user->sex = $result['sex'];
				$user->language = $result['language'];
				$user->city = $result['city'];
				$user->province = $result['province'];
				$user->country = $result['country'];
				$user->headimgurl = $result['headimgurl'];
				$user->subscribe_time = $result['subscribe_time'];
				$user->remark = $result['remark'];
				$user->groupid = $result['groupid'];
				if ($autoSave) {
					$user->save();
				}
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::getMemberDetail($nextWID, true, true, $autoSave);
			} else {
				return false;
			}
		}
		return $user;
	}

	public static function sendCSMessage ($msg, $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$accessToken;
		$postData = $msg->getCustomerServiceFormat();
		$result = Util::sslPost(
			$url,
			$postData
		);
		Logger::logWxApi(self::getOpenID(), 'send_cs_message', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			return $result;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::sendCSMessage($msg, true);
		} else {
			return false;
		}
	}

	public static function getMenus ($fromAPI=false, $refreshAccessToken=false) {
		$openID = self::getOpenID();
		if (!$fromAPI) {
			$menus = WechatMenu::findByOpenID($openID);
			return $menus;
		} else {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$accessToken;
			$result = Util::sslRequest($url);
			Logger::logWxApi($openID, 'get_menu', $url, $result);
			if (is_array($result) && empty($result['errcode'])) {
				$resultMenus = array();
				$resultMenus[] = $result['menu'];
				if (!empty($result['conditionalmenu'])) {
					$resultMenus = array_merge($resultMenus, $result['conditionalmenu']);
				}

				foreach ($resultMenus as $mnu) {
					$buttons = $mnu['button'];
					$menus = array();
					$menu = new WechatMenu($openID);
					$menu->wx_menu_id = $mnu['menuid'];
					if (!empty($mnu['matchrule'])) {
						$menu->rule = new JsonModel($mnu['matchrule']);
					}
					foreach ($buttons as $button) {
						$action = '';
						switch ($button['type']) {
							case 'view':
								$action = $button['url'];
								break;
							case 'media_id':
							case 'view_limited':
								$action = $button['media_id'];
								break;
							default:
								$action = $button['key'];
								break;
						}
						$btn = new WechatMenuButton($openID);
						$btn->action = $action;
						$btn->wx_menu_id = $mnu['menuid'];
						$btn->type = $button['type'];
						$btn->text = $button['name'];

						if (!empty($button['sub_button'])) {
							foreach ($button['sub_button'] as $subButton) {
								$subBtn = new WechatMenuButton($openID);
								$subBtn->action = $action;
								$subBtn->type = $button['type'];
								$subBtn->text = $button['name'];
								$btn->addSubButton($subBtn);
							}
						}
						$menu->addButton($btn);
					}
					$menu->save();
					$menus[] = $menu;
				}
				return $menus;
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::getMenus(true, true);
			} else {
				return false;
			}
		}
	}

	public static function uploadNews (NewsMessage $news, $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token='.$accessToken;
		$postData = $news->getUploadMaterialFormat();
		$result = Util::sslPost(
			$url,
			$postData
		);
		Logger::logWxApi(self::getOpenID(), 'upload_news', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			// init news
			$news->media_id = $result['media_id'];

			// init articles
			$articles = $news->getArticles();
			foreach ($articles as $article) {
				$article->create_time = $result['created_at'];
			}
			$news->save();
			return $news->media_id;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::uploadNews($news, true);
		} else {
			return false;
		}
	}

	public static function getAllNewsMaterials (array &$newsMessages, $fromAPI=false, $refreshAccessToken=false) {
		if (!$fromAPI) {
			$result = DB::fetchAll("SELECT * FROM `wechat_material_news_article` WHERE `open_id` = ".DB::quote(self::getOpenID()), CORE_DB_CONN);
			if ($result) {
				$articles = array();
				foreach ($result as $row) {
					$article = new Article();
					$article->title = $row['title'];
					$article->description = $row['digest'];
					$article->url = $row['content_source_url'];
					$article->content = $row['content'];
					$article->thumb_media_id = $row['thumb_media_id'];
					$article->author = $row['author'];
					$article->show_cover_pic = $row['show_cover_pic'];
					$articles[$row['media_id']][] = $article;
				}
				foreach ($articles as $mediaID => $arts) {
					$newsMessages[] = new NewsMessage('', $arts);
				}
				return $newsMessages;
			} else {
				return self::getAllNewsMaterials(true);
			}
		} else {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$accessToken;
			$offset = 0;
			$totalNews = 0;
			do {
				$postData = array(
					'type' => 'news',
					'offset' => $offset,
					'count' => 20
				);
				$result = Util::sslPost(
					$url,
					$postData
				);
				Logger::logWxApi(self::getOpenID(), 'get_all_news_materials', $url, $result, $postData);
				if (is_array($result) && empty($result['errcode'])) {
					$offset += $result['item_count'];
					$totalNews = $result['total_count'];
					// news:
					$newsMessages = array();
					foreach ($result['item'] as $row) {
						$news = new NewsMessage();
						$news->media_id = $row['media_id'];
						foreach ($row['content']['news_item'] as $item) {
							$article = new Article();
							$article->title = $item['title'];
							$article->description = $item['digest'];
							$article->news_url = $item['url'];
							$article->url = $item['content_source_url'];
							$article->content = $item['content'];
							$article->thumb_media_id = $item['thumb_media_id'];
							$article->thumb_url = $item['thumb_url'];
							$article->author = $item['author'];
							$article->show_cover_pic = $item['show_cover_pic'];
							$news->addArticle($article);
						}
						$news->save();
						$newsMessages[] = $news;
					}
				} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
					// If access token is wrong, refresh it and try again:
					return self::getAllNewsMaterials($newsMessages, true, true);
				} else {
					break;
				}
			} while ($offset < $totalNews);
			return $newsMessages;
		}
	}

	public static function getQRCode ($scene, $fromAPI=false, $refreshAccessToken=false) {
		if (!$fromAPI) {
			$result = DB::fetch("SELECT `url` FROM `qr_code` WHERE `open_id` = ".DB::quote(self::getOpenID())." AND `scene` = ".DB::quote($scene), CORE_DB_CONN);
			if ($result) {
				return $result['url'];
			} else {
				return self::getQRCode($scene, true);
			}
		} else {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$accessToken;
			$postData = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene.'"}}}';
			$result = Util::sslPost(
				$url,
				$postData
			);
			Logger::logWxApi(self::getOpenID(), 'generate_qr_code', $url, $result, $postData);
			if (is_array($result) && empty($result['errcode'])) {
				$ticket = $result['ticket'];
				$picurl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
				DB::exec(
					"INSERT INTO `qr_code`
						(`open_id`, `scene`, `ticket`, `url`, `create_time`)
					VALUES
					(
						".DB::quote(self::getOpenID()).",
						".DB::quote($scene).",
						".DB::quote($ticket).",
						".DB::quote($picurl).",
						".time()."
					)
					ON DUPLICATE KEY UPDATE
						`ticket` = VALUES(`ticket`),
						`url` = VALUES(`url`),
						`create_time` = VALUES(`create_time`)", CORE_DB_CONN
				);
				return $picurl;
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::getQRCode($scene, true, true);
			} else {
				return false;
			}
		}
	}

	public static function sendCard (CardMessage $card, $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$accessToken;
		$postData = $card->getBroadcastMessageFormat();
		$result = Util::sslPost(
			$url,
			$postData
		);
		Logger::logWxApi(self::getOpenID(), 'send_card', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			$sql = "INSERT INTO `wechat_card_deliver`
						(`open_id`, `wid`, `card_id`, `message_id`, `time`, `result`)
					VALUES";
			$subSql = '';
			$wids = $card->to;
			if (!is_array($wids)) {
				$wids = array(0 => $wids);
			}
			foreach ($wids as $wid) {
				$subSql .= ",(
								".DB::quote(self::getOpenID()).",
								".DB::quote($wid).",
								".DB::quote($card->card_id).",
								".DB::quote($result['msg_id']).",
								".time().",
								".DB::quote(json_encode($result))."
							)";
			}
			if (!empty($subSql)) {
				$subSql = substr($subSql, 1);
			}
			$sql .= $subSql;
			DB::exec($sql);
			return count($wids);
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::sendCard($card, true);
		} else {
			return false;
		}
	}

	public static function sendTemplateMessage (TemplateMessage $message, $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$accessToken;
		$postData = $message->getTemplateFormat();
		$result = Util::sslPost(
			$url,
			$postData
		);
		Logger::logWxApi(self::getOpenID(), 'send_template_message', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			return new ResultObj(true, 0, $result);
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::sendTemplateMessage($msg, true);
		} else if (is_array($result) && in_array($result['errcode'], array(40003, 43004, 43005, 46004))) {
			// If user is not subscribed:
			return new ResultObj(false, 5001, $result, 'Not subscribed');
		} else {
			return new ResultObj(false, 5000, $result);
		}
	}

	public static function generateShortUrl ($longURL, $fromAPI=false, $refreshAccessToken=false) {
		if ($fromAPI) {
			$accessToken = self::getAccessToken($refreshAccessToken);
			if (!$accessToken) {
				return false;
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token='.$accessToken;
			$postData = array(
				'action' => 'long2short',
				'long_url' => $longURL
			);
			$result = Util::sslPost(
				$url,
				json_encode($postData)
			);
			Logger::logWxApi(self::getOpenID(), 'generate_short_url', $url, $result, $postData);
			if (is_array($result) && empty($result['errcode'])) {
				DB::exec(
					"INSERT INTO `short_url`
						(`long_url`, `short_url`, `time`)
					VALUES
						(
							".DB::quote($longURL).",
							".DB::quote($result['short_url']).",
							".time()."
						)", CORE_DB_CONN
				);
				return $result['short_url'];
			} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
				// If access token is wrong, refresh it and try again:
				return self::generateShortUrl($longURL, true, true);
			} else {
				return false;
			}
		} else {
			$result = DB::fetch("SELECT `short_url` FROM `short_url` WHERE `long_url` = ".DB::quote($longURL), CORE_DB_CONN);
			if ($result) {
				return $result['short_url'];
			} else {
				return self::generateShortUrl($longURL, true);
			}
		}
	}

	public static function getWechatServerIP ($refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$accessToken;
		$result = Util::sslRequest($url);
		Logger::logWxApi(self::getOpenID(), 'get_wechat_server_ip', $url, $result);
		if (is_array($result) && empty($result['errcode'])) {
			return $result;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::getWechatServerIP(true);
		} else {
			return false;
		}
	}

	public static function getCurrentPageShareLink ($type='') {
		$url = Util::getCurrentURL(false);
		$queryParams = $_GET;
		$from = array();
		if (!empty($queryParams['share_from'])) {
			$from = explode('_', $queryParams['share_from']);
		}
		$from[] = self::getCurrentUser()->id;
		$from = array_slice($from, -30);
		$from = implode('_', $from);
		$queryParams['share_from'] = $from;
		$queryParams['share_page'] = empty($queryParams['action']) ? 'index' : $queryParams['action'];
		$queryParams['action'] = 'index';
		if (!empty($type)) {
			$queryParams['share_type'] = $type;
		}

		$params = array();
		foreach ($queryParams as $key => $val) {
			$params[] = $key.'='.rawurlencode($val);
		}
		$queryString = implode('&', $params);

		if (strpos($url, '?') === false) {
			return $url.'?'.$queryString;
		} else {
			return $url.'&'.$queryString;
		}
	}

	public function createCouponShelf ($name, $bannerImg, $pageTitle, array $couponList, $canShare=false, $scene='SCENE_H5', $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}

		$cardList = array();
		foreach ($couponList as $cp) {
			$cardList[] = array('card_id' => $cp['id'], 'thumb_url' => $cp['icon']);
		}

		$url = 'https://api.weixin.qq.com/card/landingpage/create?access_token='.$accessToken;
		$postData = array(
			'banner' => $bannerImg,
			'page_title' => $pageTitle,
			'can_share' => $canShare,
			'scene' => $scene,
			'card_list' => $cardList
		);
		$result = Util::sslPost(
			$url,
			json_encode($postData)
		);
		Logger::logWxApi(self::getOpenID(), 'create_coupon_landing_page', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			$coupons = '';
			$index = 1;
			foreach ($couponList as $cp) {
				$coupons .= ", `coupon_".$index."_id` = ".DB::quote($cp['id']).", `coupon_".$index."_icon` = ".DB::quote($cp['icon']);
				$index++;
				if ($index > 5) {
					break;
				}
			}
			DB::exec(
				"INSERT INTO
					`wechat_coupon_landing_page`
				SET
					`open_id` = ".DB::quote(self::getOpenID()).",
					`name` = ".DB::quote($name).",
					`banner` = ".DB::quote($bannerImg).",
					`title` = ".DB::quote($pageTitle).",
					`can_share` = ".DB::quote($canShare).",
					`scene` = ".DB::quote($scene).",
					`url` = ".DB::quote($result['url']).",
					`page_id` = ".DB::quote($result['page_id']).",
					`create_time` = ".time()."
					$coupons", CORE_DB_CONN);
			return $result;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::createCouponShelf($bannerImg, $pageTitle, $couponList, $canShare, $scene, true);
		} else {
			return false;
		}
	}

	public function createCoupon (Coupon $coupon, $refreshAccessToken=false) {
		$accessToken = self::getAccessToken($refreshAccessToken);
		if (!$accessToken) {
			return false;
		}
		$date_info = array(
			'type' => $coupon->dateType,
			'end_timestamp' => $coupon->endTime,
		);
		switch ($dateType) {
			case 'DATE_TYPE_FIX_TIME_RANGE':
				$date_info['start_timestamp'] = $coupon->startTime;
				break;
			case 'DATE_TYPE_FIX_TERM':
				$date_info['fixed_term'] = $coupon->fixedTerm;
				$date_info['fixed_begin_term'] = $coupon->fixedBeginTerm;
				break;
		}

		$url = 'https://api.weixin.qq.com/card/create?access_token='.$accessToken;
		$postData = array(
			'card' => array(
				'card_type' => $coupon->cardType,
				strtolower($cardType) => array(
					'base_info' => array(
						'logo_url' => $coupon->logoURL,
						'code_type' => $coupon->codeType,
						'brand_name' => $coupon->brandName,
						'title' => $coupon->title,
						'sub_title' => $coupon->subTitle,	// optional
						'color' => $coupon->color,
						'notice' => $coupon->notice,
						'description' => $coupon->description,
						'sku' => array(
							'quantity' => $coupon->quantity,
						),
						'date_info' => $date_info,
					),
					'advanced_info' => array(),
				)
			)
		);
		$result = Util::sslPost(
			$url,
			json_encode($postData)
		);
		Logger::logWxApi(self::getOpenID(), 'create_coupon', $url, $result, $postData);
		if (is_array($result) && empty($result['errcode'])) {
			return $result;
		} else if (is_array($result) && in_array($result['errcode'], array(40001, 40014, 41001, 42001))) {
			// If access token is wrong, refresh it and try again:
			return self::createCoupon($coupon, true);
		} else {
			return false;
		}
	}

}
