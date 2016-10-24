<?php

require 'vendor/autoload.php';

$db = new Iporm\Db();

$db->select('*')
	->from('members')
	->run();
