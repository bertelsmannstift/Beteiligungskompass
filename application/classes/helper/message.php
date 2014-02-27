<?php

class Helper_Message {

    /**
     * @param $filename
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function loadFile($filename) {
		$translations = array();

		if (!is_string($filename) || !strlen($filename)) {
			$msg = 'Filename must be a string and cannot be empty';
			throw new InvalidArgumentException($msg);
		}

		if(!file_exists($filename)) {
			$msg = sprintf('File %s could not be found',$filename);
			throw new InvalidArgumentException($msg);
		}

		$fp = fopen($filename, 'r');
		while (!feof($fp)) {
			$line = trim(fgets($fp));

            // remove empty lines and comments
			if(trim($line) == "" || substr($line,0,1) == "#") {
				continue;
			}

			$code = trim(mb_substr($line, 0, mb_stripos($line,"=")));
			$value = trim(mb_substr($line, mb_stripos($line,"=")+1, mb_strlen($line)));

			if(!empty($code) && isset($translations[$code])) {
				throw new Exception("The following message code is used twice in the translation file: " . $filename . ' Code: ' . $code, 1);
			} elseif(!empty($code)) {
                $translations[$code] = str_replace('\n', "\n", $value);
            } else {
                throw new Exception("Wrong config line: " . $line . ' in file: ' . $filename, 1);
            }

		}
		fclose($fp);

		return $translations;
	}

    /**
     * @param $code
     * @param array $params
     * @return string
     * @throws Exception
     */
    public static function get($code, $params = array()) {
		if(!is_string($code) || !strlen($code)) {
			throw new Exception("No message code was passed to " . __FUNCTION__, 1);
		}

		$messageparameter = array();
		foreach ($params as $key => $value) {
			$messageparameter[":".$key] = str_ireplace(':nl', "\n", $value);
		}

		$msg = Kohana::message("project",$code,$code);
        $msg = str_ireplace(':nl', "\n", $msg);

		return strtr($msg,$messageparameter);
	}

    /**
     * @param $file
     * @param bool $code
     * @param string $dir
     * @return array|bool
     */
    public static function getArr($file, $code = false, $dir = 'messages') {
        $messages = array();

        if ($files = Kohana::find_file($dir, $file))
        {
            foreach ($files as $f)
            {
                $messages = Arr::merge($messages, Kohana::load($f));
            }
        }

        $result = array();
        $config = array();

        foreach($messages as $key => $value) {
            $value =  str_ireplace(':nl', "\n", $value);
            $arrKeys = explode('.', $key);
            $command = '$config["'.join('"]["', $arrKeys).'"] = $value;';
            eval($command);
            $result = array_merge($result, $config);
        }

        if($code) {
            if(isset($result[$code])) {
                return $result[$code];
            }
            return false;
        }
        return $result;
    }
}