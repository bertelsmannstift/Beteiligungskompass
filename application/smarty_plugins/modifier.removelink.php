<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * @param $string
 * @return mixed
 */
function smarty_modifier_removelink($string)
{
    return preg_replace('/<a\b[^>]*>(.*?)<\/a>/i', "$1", $string);
}

?>