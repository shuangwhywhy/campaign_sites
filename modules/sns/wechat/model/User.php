<?php

namespace modules\sns\wechat\model;

use \modules\core\db\helper\DB;
use \modules\core\common\helper\Util;
use \modules\sns\wechat\helper\Wechat;

class User {

	public $id;

	public $member_id = null;

	public $account_open_id;

	public $openid;

	public $nickname;

	public $sex = null;

	public $province;

	public $city;

	public $country;

	public $headimgurl;

	public $subscribe = null;

	public $subscribe_time = null;

	public $language;

	public $remark;

	public $groupid = null;

	public $unionid;

	public function __construct ($openid=false, $autoFill=true) {
		$this->openid = $openid;
		if ($autoFill) {
			$this->loadUserInfo();
		}
	}

	public function loadUserInfoFromDB () {
		$result = DB::fetch("SELECT * FROM `wechat_users` WHERE `wid` = ".DB::quote($this->openid), CORE_DB_CONN);
		if ($result) {
			$this->id = $result['id'];
			$this->account_open_id = $result['open_id'];
			$this->nickname = $result['nickname'];
			$this->sex = $result['gender'];
			$this->province = $result['province'];
			$this->city = $result['city'];
			$this->country = $result['country'];
			$this->headimgurl = $result['avatar'];
			$this->subscribe = $result['subscribe'];
			$this->subscribe_time = $result['subscribe_time'];
			$this->language = $result['language'];
			$this->remark = $result['remark'];
			$this->groupid = $result['groupid'];
			$this->unionid = $result['unionid'];
			$this->member_id = $result['member_id'];
			return true;
		}
		return false;
	}

	public function loadUserInfo () {
		if (empty($this->openid)) {
			$openid = Util::getIclpCookie('wx_auth_openid');
			if (empty($openid)) {
				$this->auth();
			} else {
				$this->openid = $openid;
				$this->getMemberID();
			}
		}
		if (!$this->loadUserInfoFromDB()) {
			$this->auth();
		}
		return $this;
	}

	public function auth () {
		return Wechat::auth();
	}

	public function save () {
		$dbResult = DB::exec(
            "INSERT INTO `wechat_users`
				(
				 	`open_id`,
				 	`wid`,
				 	`nickname`,
				 	`avatar`,
				 	`gender`,
				 	`country`,
				 	`province`,
				 	`city`,
				 	`subscribe`,
				 	`subscribe_time`,
				 	`language`,
				 	`remark`,
				 	`groupid`,
				 	`unionid`,
				 	`create_time`,
				 	`member_id`
				)
			VALUES
				(
					".DB::quote($this->account_open_id).",
					".DB::quote($this->openid).",
					".DB::quote($this->nickname).",
					".DB::quote($this->headimgurl).",
					".DB::quote($this->sex).",
					".DB::quote($this->country).",
					".DB::quote($this->province).",
					".DB::quote($this->city).",
					".(empty($this->subscribe) ? 0 : 1).",
					".(empty($this->subscribe_time) ? 0 : intval($this->subscribe_time)).",
					".DB::quote($this->language).",
					".DB::quote($this->remark).",
					".(empty($this->groupid) ? 0 : intval($this->groupid)).",
					".DB::quote($this->unionid).",
					".time().",
					".DB::quote($this->member_id)."
				)
			ON DUPLICATE KEY UPDATE
				`nickname` = VALUES(`nickname`),
				`avatar` = VALUES(`avatar`),
				`gender` = VALUES(`gender`),
				`country` = VALUES(`country`),
				`province` = VALUES(`province`),
				`city` = VALUES(`city`),
				`subscribe` = VALUES(`subscribe`),
				`subscribe_time` = VALUES(`subscribe_time`),
				`language` = VALUES(`language`),
				`remark` = VALUES(`remark`),
				`groupid` = VALUES(`groupid`),
				`unionid` = VALUES(`unionid`),
				`update_time` = ".time().",
				`member_id` = VALUES(`member_id`)", CORE_DB_CONN
		);
		return $dbResult;
	}

	public static function findByID ($id) {
		$user = new User(false, false);
		$result = DB::fetch("SELECT * FROM `wechat_users` WHERE `id` = ".DB::quote($id), CORE_DB_CONN);
		if ($result) {
			$user->id = $result['id'];
			$user->account_open_id = $result['open_id'];
			$user->openid = $result['wid'];
			$user->nickname = $result['nickname'];
			$user->sex = $result['gender'];
			$user->province = $result['province'];
			$user->city = $result['city'];
			$user->country = $result['country'];
			$user->headimgurl = $result['avatar'];
			$user->subscribe = $result['subscribe'];
			$user->subscribe_time = $result['subscribe_time'];
			$user->language = $result['language'];
			$user->remark = $result['remark'];
			$user->groupid = $result['groupid'];
			$user->unionid = $result['unionid'];
			$user->member_id = $result['member_id'];
			return $user;
		}
		return false;
	}

	public function isSubscribed () {
		return ($this->subscribe == 1);
	}

	public function isMember () {
		$result = DB::fetch("SELECT `type` FROM `ms_member_member` WHERE `member_id_port` > 0 AND `type` > 0 AND `weixin_open_id` = ".DB::quote($this->openid), LEGACY_DB_CONN);
		return (bool)($result);
	}

	public function getMemberID () {
		return $this->member_id;
	}

}
