<?php

namespace modules\sns\wechat\model;

use \modules\core\common\helper\DB;

class WechatMenuButton {

	public $id;

	public $open_id;

	public $wx_menu_id;

	public $text;

	public $parent = 0;

	public $level = 1;

	public $type;

	public $action;

	public $order = 0;

	public $create_time;

	public $children = array();

	public $parent_button;

	public function __construct ($open_id) {
		$this->open_id = $open_id;
	}

	public function addSubButton (WechatMenuButton $btn) {
		$this->children[] = $btn;
		$btn->parent_button = $this;
		$btn->parent = $this->id;
		$btn->level = $this->level + 1;
		$btn->open_id = $this->open_id;
		$btn->wx_menu_id = $this->wx_menu_id;
	}

	public function save () {
		$res = false;
		$res = DB::exec(
			"INSERT INTO `wechat_menu_item`
				(`id`, `open_id`, `wx_menu_id`, `text`, `parent`, `level`, `type`, `action`, `order`, `create_time`)
			VALUES
			(
				".DB::quote($this->id).",
				".DB::quote($this->open_id).",
				".DB::quote($this->wx_menu_id).",
				".DB::quote($this->text).",
				".DB::quote($this->parent).",
				".DB::quote($this->level).",
				".DB::quote($this->type).",
				".DB::quote($this->action).",
				".DB::quote($this->order).",
				".time()."
			)
			ON DUPLICATE KEY UPDATE
				`open_id` = VALUES(`open_id`),
				`text` = VALUES(`text`),
				`parent` = VALUES(`parent`),
				`level` = VALUES(`level`),
				`type` = VALUES(`type`),
				`action` = VALUES(`action`),
				`order` = VALUES(`order`),
				`create_time` = VALUES(`create_time`)", CORE_DB_CONN
		);
		if ($res) {
			$this->id = DB::lastID(CORE_DB_CONN);
			$this->create_time = time();
			foreach ($this->children as $subButton) {
				$subButton->parent = $this->id;
				$subButton->save();
			}
		}
		return $res;
	}

	public static function findByID ($id) {
		$result = DB::fetch("SELECT * FROM `wechat_menu_item` WHERE `id` = ".intval($id), CORE_DB_CONN);
		$button = null;
		if ($result) {
			$button = new WechatMenuButton($result['open_id']);
			$button->id = $id;
			$button->wx_menu_id = $result['wx_menu_id'];
			$button->text = $result['text'];
			$button->parent = $result['parent'];
			$button->level = $result['level'];
			$button->type = $result['type'];
			$button->action = $result['action'];
			$button->order = $result['order'];
			$button->create_time = $result['create_time'];

			$subButtons = self::findByParentID($id);
			if (!empty($subButtons)) {
				foreach ($subButtons as $subBtn) {
					$button->addSubButton($subBtn);
				}
			}
		}
		return $button;
	}

	public static function findByParentID ($parentID) {
		$buttons = array();
		$result = DB::fetchAll("SELECT `id` FROM `wechat_menu_item` WHERE `parent` = ".intval($parentID)." ORDER BY `order`", CORE_DB_CONN);
		if ($result) {
			foreach ($result as $row) {
				$button = WechatMenuButton::findByID($row['id']);
				if ($button) {
					$buttons[] = $button;
				}
			}
		}
		return $buttons;
	}

}
