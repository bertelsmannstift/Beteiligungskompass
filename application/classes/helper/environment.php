<?php

class Helper_Environment {
    /**
     * @return bool
     */
    public static function is_DEV() {
		return (Kohana::$environment === Kohana::DEVELOPMENT);
	}
}