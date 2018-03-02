<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 3/2/18
 * Time: 12:22 PM
 */

namespace Jorjsmile\AS2;


class AS2Configs
{
    /**
     * @var self
     */
    protected static $instance;

    private $_configs = [];

    public function setConfigs($data)
    {
        $this->_configs = $data;
        return $this;
    }

    /**
     * @param $name
     * @param $info
     * @return mixed
     */
    public function setConfig($name, $info)
    {
        $this->_configs[$name] = $info;
        return $this;
    }

    public function getConfig($name)
    {
        return $this->_configs[$name]??[];
    }

    /**
     * @return AS2Configs
     */
    public static function instance()
    {
        if(is_null(self::$instance))
            self::$instance = new self;

        return self::$instance;
    }
}