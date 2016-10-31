About
=============

Iporm is a basic [ORM](http://en.wikipedia.org/wiki/Object_relational_mapping) package written in PHP. Usage is very simple, it is based on method chaining pattern, so it feels natural and fluent. This is a extension of a simple wrapper that I wrote a long time ago, which expanded and matured over time.

There are plenty of excellent wrappers out there, but I hope you will have fun using this one.

##Installation

Simplest way to install it is via composer, package name is "iporm/iporm", so include it in your composer.json with: 

	composer require iporm/iprom

or you can download it here directly. After installation, please adjust connection parameters in Connection.php file.

##Guidelines

Below you will fing usage examples for some of the main methods. For a complete reference and functional code examples check out index.php.

###Select statement
	$db = new Iprom\Db();
	$db->select()
        ->from('users')
        ->run();

	print_r($db->getSelected());

###Insert statement
	$db = new Iporm\Db();
	$db->insertInto('users', ['name' => 'Sample', 'last_name' => 'Name'])
	    ->run();
	    
    print_r($db->getInsertedId());
	    
###Update statement
    $db = new Iporm\DB();
    $db->update('users', ['name' => 'New'])
        ->where(['name' => 'Sample'])
        ->run();
        
    print_r($db->getAffected());
    
###Delete statement
    $db = new Iporm\Db();
    $db->delete()
        ->from('users')
        ->where(['name' => 'New'])
        ->run();
        
    print_r($db->getAffected());