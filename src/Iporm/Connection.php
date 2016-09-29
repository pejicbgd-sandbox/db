<?php

namespace Iporm;

class Connection
{
    /**
    * DB host
    */
    private static $_host;

    /**
    * DB username
    */
    private static $_username;

    /**
    * DB pass
    */
    private static $_password;

    /**
    * DB name
    */
    private static $_db;

    /**
    * Connection object
    */
    private static $_con;

    /**
    * Connect to DB
    * Print out error message on fail
    * @return void
    */
    private static function connect()
    {
        try
        {
            self::$_con = mysqli_connect(self::$_host, self::$_username, self::$_password, self::$_db);
            self::$_con->set_charset("utf8");
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }

    }

    /**
    * Get active connection
    * @return object
    */
    public static function getInstance()
    {
        if(!is_object(self::$_con))
        {
            self::$_host = 'localhost';
            self::$_username = 'kreatorci';
            self::$_password = 'vuGMaL98vQEc23AU';
            self::$_db = 'kreatornica';

            self::connect();
        }
        return self::$_con;
    }
}
