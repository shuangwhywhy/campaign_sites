<?php

namespace modules\core\common\model;

class XmlModel {

	protected $data = array();

	public function __construct (array $data=array()) {
		$this->setData($data);
	}

	public function setData (array $data=array()) {
		$this->data = $data;
	}

	public function getData () {
		return $this->data;
	}

	public function toXmlString () {
		$data = $this->data;
		foreach ($data as $key => $val) {
			if ($val instanceof XmlModel) {
				$data[$key] = $val->getData();
			}
			if (!empty($data[$key]['leaf'])) {
				$type = $data[$key]['type'];
				if ($type == 'cdata') {
					$data[$key] = '<![CDATA['.$data[$key]['value'].']]';
				} else {
					$data[$key] = $data[$key]['value'];
				}
			}
		}
		// make xml:
		$xml = new \SimpleXMLElement('<root/>');
		array_walk_recursive($data, array($xml, 'addChild'));
		$doc = $xml->asXML();
		$doc = preg_replace('/<\?xml version="\d\.\d"\?>\s*/i', '', $doc);
		return $doc;
	}

	public function toString () {
		return $this->toXmlString();
	}

	private static function parseXmlDoc (array &$struct) {
		$data = array();
		foreach ($struct as $tag) {
			$type = $tag['type'];
			switch ($type) {
				case 'open':
					$data[$tag['tag']] = array();
					array_push($path, $tag['tag']);
					break;
				case 'close':
					array_pop($path);
					break;
				case 'complete':
					array_push($path, $tag['tag'], $tag['value']);
					break;
			}
		}
	}

	public static function parseSimpleXmlString ($xml) {
		$data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$arr = json_decode(json_encode($data), 1);
		$xml = null;
		if (is_array($arr)) {
			$xml = new XmlModel($arr);
		}
		return $xml;
	}

	public static function parseXmlString ($xml) {
		$data = array();
		$struct = array();
		$parser = xml_parser_create_ns('UTF-8');
		$result = xml_parse_into_struct($parser, $xml, $struct);
		if ($result) {
			$path = array();
			foreach ($struct as $tag) {
				$type = $tag['type'];
				switch ($type) {
					case 'complete':
						$data[strtolower($tag['tag'])] = isset($tag['value']) ? $tag['value'] : '';
						break;
				}
			}
		}

		// $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$xml = new XmlModel(json_decode(json_encode($data), 1));
		return $xml;
	}

}
