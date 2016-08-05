<?php

namespace modules\core\common\helper;

class CsvReader {

	public static function readFromFile ($fileName) {
		$file = fopen($fileName, 'r');
		$data = array();
		$count = 0;
		do {
			$csv = fgetcsv($file, 0, ';');
			if (empty($csv)) {
				break;
			}
			$data[] = $csv;
			$count++;
		} while (true);
		for ($i=1; $i<$count; $i++) {
			$data[$i] = array_combine($data[0], $data[$i]);
		}
		$data = array_slice($data, 1);
		fclose($file);
		return $data;
	}

}
