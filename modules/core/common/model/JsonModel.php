<?php

namespace modules\core\common\model;

class JsonModel {

	protected $data = array();

	public function __construct (array $data=array()) {
		$this->data = $data;
	}

	public function getData () {
		return $this->data;
	}

    public function toArray () {
        return $this->getData();
    }

	public function toJsonString ($escapeUnicode=true) {
		$data = $this->data;
		foreach ($data as $key => $val) {
			if ($val instanceof JsonModel) {
				$data[$key] = $val->toJsonString($escapeUnicode);
			}
		}
		if ($escapeUnicode) {
			return json_encode($data);
		} else {
			return $this->jsonEncode($data);
			/*if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
				return json_encode($data, JSON_UNESCAPED_UNICODE);
			} else {
				$json = json_encode($data);
				$json = preg_replace_callback('/\\u[a-z0-9]{4}/i', function ($matches) {
					return json_decode($matches[0], true);
				}, $json);
				return $json;
			}*/
		}
	}

	public function toString ($escapeUnicode=true) {
		return $this->toJsonString($escapeUnicode);
	}

    public static function parseJsonString ($json) {
        $model = new JsonModel(json_decode($json, true));
        return $model;
    }

	public function jsonEncode ($arr) {
        $parts = array();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;
        if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) { //Custom handling for arrays
                if ($is_list)
                    $parts [] = $this->jsonEncode($value); /* :RECURSION: */
                else
                    $parts [] = '"' . $key . '":' . $this->jsonEncode($value); /* :RECURSION: */
            } else {
                $str = '';
                if (!$is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (is_numeric($value) && $value < 2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes($value) . '"'; //All other things
                $parts [] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }

}
