<?php

function smarty_function_form($params, $template) {
	foreach($params as $key => $value) {
		$template->smarty->assign($key, $value);
	}

	return $template->smarty->fetch('inc/form.html');
}

?>