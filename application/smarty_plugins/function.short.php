<?php

/**
 * @param $params
 * @return string
 */
function smarty_function_short($params)
{

    if (empty($params['length'])) {
        trigger_error("length parameter cannot be empty",E_USER_NOTICE);
        return;
    }
    if (empty($params['str'])) {
        trigger_error("str parameter cannot be empty",E_USER_NOTICE);
        return;
    }
    $allowtags = isset($params['allowtags']) ? $params['allowtags'] : null;

    $str = $params['str']; //str_replace('&nbsp;', '', $params['str']); # ticket #7511

    if(isset($params['removetags']) && $params['removetags'] == true) {
        $str = trim(strip_tags($str, $allowtags));
    }

    $str = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');

    if(strlen($str) >= $params['length']) {
        $str = trim(mb_substr($str, 0, intval($params['length'])-3)) . '&hellip;';
    }

    return mb_convert_encoding($str, 'UTF-8');
}

?>