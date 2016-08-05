<?php

namespace modules\sns\wechat\model\message;

use \modules\sns\wechat\model\Message;
use \modules\core\common\helper\DB;
use \modules\sns\wechat\helper\Wechat;

class NewsMessage extends Message {

	public $articles = array();

	public $media_id;

	public function __construct ($to='', array $articles=array()) {
		$this->from = Wechat::getOpenID();
		$this->to = $to;
		$this->articles = $articles;
		$this->type = 'news';
	}

	public function addArticle (Article $article) {
		$article->news = $this;
		$this->articles[] = $article;
	}

	public function setArticleAt ($index, Article $article) {
		$article->news = $this;
		$this->articles[$index] = $article;
	}

	public function removeArticleAt ($index) {
		if (!empty($this->articles[$index])) {
			$this->articles[$index]->news = null;
			unset($this->articles[$index]);
		}
	}

	public function getArticles () {
		return $this->articles;
	}

	public function getCustomerServiceFormatArray () {
		$articles = $this->articles;
		if (!empty($articles)) {
			foreach ($articles as $key => $val) {
				$articles[$key] = $val->getCustomerServiceFormatArray();
			}
			$data = array(
				'touser' => $this->to,
				'msgtype' => $this->type,
				'news' => array(
					'articles' => array_values($articles)
				)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getAutoReplyFormatArray () {
		$articles = $this->articles;
		if (!empty($articles)) {
			foreach ($articles as $key => $val) {
				$articles[$key] = $val->getAutoReplyFormatArray();
			}
			$data = array(
				'ToUserName' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->to),
				'FromUserName' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->from),
				'CreateTime' => time(),
				'MsgType' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->type),
				'ArticleCount' => count($articles),
				'Articles' => array_values($articles)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getBroadcastMessageFormatArray () {

	}

	public function getUploadMaterialFormatArray () {
		$articles = $this->articles;
		if (!empty($articles)) {
			foreach ($articles as $key => $val) {
				$articles[$key] = $val->getUploadMaterialFormatArray();
			}
			$data = array(
				'articles' => array_values($articles)
			);
			return $data;
		} else {
			return false;
		}
	}

	public function getData () {
		return $this->getCustomerServiceFormatArray();
	}

	public function removeFromDB () {
		if (!empty($this->media_id)) {
			DB::exec("DELETE FROM `wechat_material_news_article` WHERE `media_id` = ".DB::quote($this->media_id), CORE_DB_CONN);
		}
	}

	public function save ($replace=true) {
		if ($replace) {
			$this->removeFromDB();
		}
		foreach ($this->articles as $article) {
			$article->save();
		}
	}

}
