<?php

namespace clients\esprit\campaigns\xmas\app\controller;

use \modules\core\common\helper\Util;
use \modules\core\common\helper\DB;
use \modules\core\common\model\ViewModel;
use \modules\sns\wechat\helper\Wechat;
use \modules\sns\wechat\model\User;

class PageController extends \modules\sns\wechat\controller\WechatController {

	private $ongoing = array(
		'getCard',
		'viewCard',
		'wechatAuthCallback',
		'campaignEnd',
		'emailIncreaseSmsLanding',
	);

	public function beforeDispatch ($action) {
		if (!in_array($action, $this->ongoing) && Util::isCampaignEnded()) {
			return 'gameEnd';
		}
		return parent::beforeDispatch($action);
	}

	public function postDispatch ($action, $viewModel) {
		if (Util::isCampaignEnded()) {
			return parent::postDispatch($action, $viewModel);
		}
		Util::setIclpCookie('prev_page', $action);
		if ($viewModel instanceof ViewModel) {
			$data = $viewModel->getData();
			if ((empty($data) || empty($data['nowechat'])) && isset($_GET['share_from'])) {
				$from = explode('_', $_GET['share_from']);
				$from_wids = array();
				if (!empty($from)) {
					foreach ($from as $uid) {
						$user = User::findByID($uid);
						if ($user) {
							$from_wids[] = $user->openid;
						}
					}
				}
				Logger::logWxShareBack(Wechat::getOpenID(), Wechat::getCurrentUser()->openid, $from_wids, $_GET['share_page'], $_GET['share_type']);
			}
		}
		return parent::postDispatch($action, $viewModel);
	}

	public function indexAction () {
	}

	public function lotteryAction () {
	}

	public function campaignEndAction () {
		return new ViewModel('campaign_end', array('nowechat' => 1));
	}

}
