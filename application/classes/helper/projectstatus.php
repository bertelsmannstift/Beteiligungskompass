<?php

class Helper_Projectstatus {
    /**
     * @return array
     */
    public static function getList() {
		return array_map(
			function($key) {
				return Helper_Message::get('article.projectstatus.' . $key);
			}
			, Kohana::$config->load('project.projectstatus')
		);
	}
}