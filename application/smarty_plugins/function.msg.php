<?php

/**
 * @param $params
 * @return string
 * @throws Exception
 */
function smarty_function_msg($params) {

	if(!isset($params["code"])) {
		throw new Exception("No message code was passed to " . __FUNCTION__, 1);
	}
	
	$messagecode = $params["code"];
	unset($params["code"]);

	return Helper_Message::get($messagecode,$params);
}

?>