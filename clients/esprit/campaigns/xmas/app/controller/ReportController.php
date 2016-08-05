<?php

namespace clients\esprit\campaigns\xmas\app\controller;

use \modules\core\common\helper\Util;
use \modules\core\common\helper\DB;
use \modules\core\common\model\ViewModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\model\User;

class ReportController extends \modules\sns\wechat\controller\WechatController {

	protected $rights_level = 'admin';

	protected $noDigestAuth = array();

	protected $adminUser = null;

	public function beforeDispatch ($action) {
		$this->adminUser = Util::digestAuth();
		if (!in_array($action, $this->noDigestAuth) && !$this->adminUser) {
			die('Unauthorized');
		}
		return parent::beforeDispatch($action);
	}

	public function getDefaultPlatform () {
		return 'admin';
	}

	public function getMemberReportAction () {
	}

	public function getPageTimeReportAction () {
	}

	public function getMenuClickReportAction () {
	}

	public function getKeywordReportAction () {
	}

}
