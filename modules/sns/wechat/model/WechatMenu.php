<?php

namespace modules\sns\wechat\model;

use \modules\core\common\helper\DB;
use \modules\core\common\model\JsonModel;
use \modules\sns\wechat\model\WechatMenuButton;

class WechatMenu {

	public $id;

	public $open_id;

	public $name;

	public $wx_menu_id;

	public $buttons = array();

	public $rule;

	public $create_time;

	public function __construct ($open_id) {
		$this->open_id = $open_id;
	}

	public function addButton (WechatMenuButton $btn) {
		$this->buttons[] = $btn;
	}

	public static function findByID ($id) {
		$menu = null;
		$result = DB::fetch("SELECT * FROM `wechat_menu` WHERE `id` = ".intval($id), CORE_DB_CONN);
		if ($result) {
			$menu = new WechatMenu($result['open_id']);
			$menu->id = $result['id'];
			$menu->name = $result['name'];
			$menu->wx_menu_id = $wx_menu_id;
			if (!empty($result['rule'])) {
				$menu->rule = JsonModel::parseJsonString($result['rule']);
			}
			$res = DB::fetchAll("SELECT `id` FROM `wechat_menu_item` WHERE `parent` = 0 AND `wx_menu_id` = ".DB::quote($wx_menu_id)." ORDER BY `order`", CORE_DB_CONN);
			if ($res) {
				foreach ($res as $row) {
					$button = WechatMenuButton::findByID($row['id']);
					$menu->addButton($button);
				}
			}
		}
		return $menu;
	}

	public static function findByWxMenuID ($wx_menu_id) {
		$menu = null;
		$result = DB::fetch("SELECT `id` FROM `wechat_menu` WHERE `wx_menu_id` = ".DB::quote($wx_menu_id), CORE_DB_CONN);
		if ($result) {
			$menu = self::findByID($result['id']);
		}
		return $menu;
	}

	public static function findByOpenID ($open_id) {
		$menus = array();
		$result = DB::fetchAll("SELECT `id` FROM `wechat_menu` WHERE `open_id` = ".DB::quote($open_id), CORE_DB_CONN);
		if ($result) {
			foreach ($result as $row) {
				$menu = self::findByID($row['id']);
				if ($menu) {
					$menus[] = $menu;
				}
			}
		}
		return $menus;
	}

	public function save () {
		$res = false;
		DB::exec("DELETE FROM `wechat_menu_item` WHERE `wx_menu_id` = ".DB::quote($this->wx_menu_id), CORE_DB_CONN);
		if (empty($this->id)) {
			$res = DB::exec(
				"INSERT INTO `wechat_menu`
					(`open_id`, `wx_menu_id`, `name`, `rule`, `create_time`)
				VALUES
					(
						".DB::quote($this->open_id).",
						".DB::quote($this->wx_menu_id).",
						".DB::quote($this->name).",
						".DB::quote($this->rule ? $this->rule->toString() : NULL).",
						".time()."
					)
				ON DUPLICATE KEY UPDATE
					`open_id` = VALUES(`open_id`),
					`name` = VALUES(`name`),
					`rule` = VALUES(`rule`),
					`create_time` = VALUES(`create_time`)", CORE_DB_CONN
			);
			if ($res) {
				$this->id = DB::lastID(CORE_DB_CONN);
				$this->create_time = time();
			}
		} else {
			$res = DB::exec(
				"UPDATE `wechat_menu`
					SET
						`open_id` = ".DB::quote($this->open_id).",
						`wx_menu_id` = ".DB::quote($this->wx_menu_id).",
						`name` = ".DB::quote($this->name).",
						`rule` = ".DB::quote($this->rule ? $this->rule->toString() : NULL).",
						`create_time` = ".time()."
					WHERE `id` = ".intval($this->id), CORE_DB_CONN
			);
			if ($res) {
				$this->create_time = time();
			}
		}
		if ($res) {
			if (!empty($this->buttons)) {
				foreach ($this->buttons as $button) {
					$button->save();
				}
			}
		}
		return $res;
	}

}
