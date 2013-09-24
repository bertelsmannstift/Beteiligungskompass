<?php

class Helper_Utility {

    /**
     * @param $string
     * @param string $seperator
     * @return string
     */
    public static function slug($string, $seperator = '-') {
        $string = UTF8::strtolower($string);
        $umlauts = array('ä', 'ö', 'ü', 'ß');
        $umlautsReplace = array('ae', 'oe', 'ue', 'ss');

        $string = str_replace($umlauts, $umlautsReplace, $string);

        $string = preg_replace( array("/[^A-Za-z0-9\s\s+]/", "[ +]"), array("", $seperator), $string);
        $string = trim(strtolower($string), '-');
        return $string;
    }
}