<?php

return array(

	'admin' => array(
		'host' => 'localhost',
		'username' => 'root',
		'password' => '',
		'min_rights_level' => 'admin',
	),

	'default' => array(
		'host' => 'localhost',
		'username' => 'username',
		'password' => '',
		'dbname' => 'adhoc',
		'min_rights_level' => 'normal',
	),

	'core' => array(
		'host' => 'localhost',
		'username' => 'username',
		'password' => '',
		'dbname' => 'core',
		'min_rights_level' => 'normal',
	),

	'legacy' => array(
		'host' => 'localhost',
		'username' => 'username',
		'password' => '',
		'dbname' => 'legacy',
		'min_rights_level' => 'normal',
		'table_prefix' => 'tbl_',
	),

);
