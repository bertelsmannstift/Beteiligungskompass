<?php

class Helper_Module {
    /**
     * @param $moduleName
     * @return bool
     */
    public static function isActive($moduleName) {
        return Kohana::$config->load('project.modules.' . $moduleName) == 'true' ? true : false;
	}
}