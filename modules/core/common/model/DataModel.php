<?php

namespace modules\core\common\model;

class DataModel implements \Iterator, \ArrayAccess, \Countable, \Serializable {

	protected $data = array();

	protected $index = 0;

	public function __set ($name, $value) {
		$this->data[$name] = $value;
	}

	public function __get ($name) {
		return $this->data[$name];
	}

	public function __isset ($name) {
		return isset($this->data[$name]);
	}

	public function __unset ($name) {
		unset($this->data[$name]);
	}

	public function offsetExists ($offset) {
		return $this->__isset($offset);
	}

	public function offsetGet ($offset) {
		return $this->__get($offset);
	}

	public function offsetSet ($offset, $value) {
		$this->__set($offset, $value);
	}

	public function offsetUnset ($offset) {
		$this->__unset($offset);
	}

	public function count () {
		return count($this->data);
	}

	public function serialize () {
		return serialize($this->data);
	}

	public function unserialize ($serialized) {
		$this->data = unserialize($serialized);
	}

	public function current () {
		return current($this->data);
	}

	public function key () {
		return key($this->data);
	}

	public function next () {
		$this->index++;
		return next($this->data);
	}

	public function rewind () {
		$this->index = 0;
	}

	public function valid () {
		return $this->offsetExists($this->key());
	}

	public function toArray ($iteration = -1) {
		if ($iteration == 0) {
			return $this;
		} else {
			return $this->data;
		}
	}

}
