<?php

class Helper_Planning {
    /**
     * @param $index
     * @return bool
     */
    public static function isQuestionActive($index) {
		return Helper_Message::get("questions.$index.active") === "true";
	}
}