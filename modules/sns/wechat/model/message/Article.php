<?php

namespace modules\sns\wechat\model\message;

use \modules\sns\wechat\model\Message;
use \modules\core\common\helper\DB;
use \modules\sns\wechat\helper\Wechat;

class Article extends Message {

	public $news;

	public $title;

	public $description;

	public $url;

	public $picurl;

	public $content = '';

	public $thumb_media_id = '';

	public $author;

	public $show_cover_pic;

	public $thumb_url;

	public $news_url;

	public $create_time;

	public $update_time;

	public function __construct ($title='', $description='', $url=null, $picurl=null) {
		$this->title = $title;
		$this->description = $description;
		$this->url = $url;
		$this->picurl = $picurl;
	}

	public function getCustomerServiceFormatArray () {
		$data = array(
			'title' => $this->title,
			'description' => $this->description
		);
		if (!empty($this->url)) {
			$data['url'] = $this->url;
		}
		if (!empty($this->picurl)) {
			$data['picurl'] = $this->picurl;
		}
		return $data;
	}

	public function getAutoReplyFormatArray () {
		$data = array(
			'item' => array(
				'Title' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->title),
				'Description' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->description),
				'PicUrl' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->picurl),
				'Url' => array('leaf'=>true, 'type'=>'cdata', 'value'=>$this->url)
			)
		);
		return $data;
	}

	public function getBroadcastMessageFormatArray () {

	}

	public function getUploadMaterialFormatArray () {
		$data = array(
			'title' => $this->title,
			'content' => $this->content,
			'thumb_media_id' => $this->thumb_media_id
		);
		if (!empty($this->author)) {
			$data['author'] = $this->author;
		}
		if (!empty($this->description)) {
			$data['digest'] = $this->description;
		}
		if (!empty($this->url)) {
			$data['content_source_url'] = $this->url;
		}
		if (!empty($this->show_cover_pic)) {
			$data['show_cover_pic'] = $this->show_cover_pic;
		}
		return $data;
	}

	public function getData () {
		return array();
	}

	public function save () {
		if (empty($this->create_time)) {
			$this->create_time = time();
		}
		return DB::exec(
			"INSERT IGNORE INTO `wechat_material_news_article`
				(`open_id`, `media_id`, `title`, `author`, `thumb_media_id`, `digest`, `show_cover_pic`, `content`, `content_source_url`, `create_time`)
			VALUES
			(
				".DB::quote(Wechat::getOpenID()).",
				".DB::quote($this->news->media_id).",
				".DB::quote($this->title).",
				".DB::quote($this->author).",
				".DB::quote($this->thumb_media_id).",
				".DB::quote($this->description).",
				".DB::quote($this->show_cover_pic).",
				".DB::quote($this->content).",
				".DB::quote($this->url).",
				".intval($this->create_time)."
			)", CORE_DB_CONN
		);
	}

}
