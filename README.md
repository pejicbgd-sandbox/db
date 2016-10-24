About
=============

Iporm is a basic [ORM](http://en.wikipedia.org/wiki/Object_relational_mapping) package written in PHP. Usage is very simple, it is based on method chaining pattern, so it feels natural and fluent. This is a extension of a simple wrapper that I wrote a long time ago, which expanded and matured with every project.

There are plenty of excellent wrappers out there, but I hope I will save you some precious time with this one.

##Installation

Simplest way to install it is via composer, just pop up you console, and hit 

	composer install iporm/iprom

or you can download it here directly. After installation, please adjust connection parameters in Connection.php file.

##Guidelines

Below you will fing usage examples for some of the main methods. For a complete reference and functional code examples check out index.php file.

###Select statement
	$db = new Iprom\Db();
	$db->select()
		->from('users')
		->run();

	print_r($db->getSelected());
