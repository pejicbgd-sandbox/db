<?php

namespace Iporm;

class Connection
{
    private static $_host;
    private static $_username;
    private static $_password;
    private static $_db;
    private static $_con;

    private static function connect()
    {
        try {
            self::$_con = mysqli_connect(self::$_host, self::$_username, self::$_password, self::$_db);
            self::$_con->set_charset("utf8");
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function getInstance()
    {
        if(!is_object(self::$_con)) {
            self::$_host = 'localhost';
            self::$_username = 'kreatorci';
            self::$_password = 'vuGMaL98vQEc23AU';
            self::$_db = 'kreatornica';

            self::connect();
        }
        
        return self::$_con;
    }
}
