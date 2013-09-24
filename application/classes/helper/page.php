<?php

class Helper_Page {

	private static $_js = array();
	private static $_css = array();

	public static function addJS($file) {
		self::$_js[$file] = $file;
	}
	public static function addCSS($file) {
		self::$_css[$file] = $file;
	}

    /**
     * @return string
     */
    public static function getJSSource() {
        $hash = md5(serialize(self::$_js));
        $content = '';
        $fileGlob = glob('merged/'.$hash.'_*.js');

        if(Kohana::$environment == Kohana::DEVELOPMENT) {
            foreach(self::$_js as $file) {
                $content .= '<script type="text/javascript" src="' . $file . '"></script>';
            }
            return $content;
        }

        if(count($fileGlob) == 0) {
            foreach(self::$_js as $file) {
                if(strpos($file, 'http') === 0 || strpos($file, 'https') === 0) {
                    $content .= JSMin::minify(file_get_contents($file));
                } else {
                    $content .= JSMin::minify(file_get_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $file));
                }
            }
            $hash2 = $content;
            $mergedFile = 'merged/'.$hash.'_'.md5($hash2).'.js';
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $mergedFile, $content);
            $fileGlob[0] = $mergedFile;
        }

        return '<script type="text/javascript" src="' . $fileGlob[0] . '"></script>';
	}

    /**
     * @param string $media
     * @return string
     */
    public static function getCSSSource($media = 'all') {
        $hash = md5(serialize(self::$_css));
        $content = '';
        $fileGlob = glob('merged/'.$hash.'_*.css');

        if(Kohana::$environment == Kohana::DEVELOPMENT) {
            foreach(self::$_css as $file) {
                $content .= '<link rel="stylesheet" href="' . $file . '" type="text/css" media="' . $media . '" />';
            }
            return $content;
        }

        if(count($fileGlob) == 0) {

            foreach(self::$_css as $file) {
                if(strpos($file, 'http') === 0 || strpos($file, 'https') === 0) {
                    $content .= CssMin::minify(file_get_contents($file));
                } else {
                    $content .= CssMin::minify(file_get_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $file));
                }
            }
            $hash2 = $content;
            $mergedFile = 'merged/'.$hash.'_'.md5($hash2).'.css';
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $mergedFile, $content);
            $fileGlob[0] = $mergedFile;
        }

        return '<link rel="stylesheet" href="' . $fileGlob[0] . '" type="text/css" media="' . $media . '" />';
	}

}