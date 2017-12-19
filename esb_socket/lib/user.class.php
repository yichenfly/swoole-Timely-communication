<?php
class user
{
    static protected $self;
    private function __construct()
    {
    }
    static function get_new()
    {
        if(isset(self::$self)&&!empty(self::$self))
        {
            return self::$self;
        }
        self::$self = new self();
        return self::$self;
    }
}