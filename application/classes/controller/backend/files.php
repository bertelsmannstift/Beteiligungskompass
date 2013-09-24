<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Files extends Controller_Backend_Base {

	public function action_index()
	{
        $customfiles = $this->getCustomUploadFiles();
        $errors = array();

        if($_POST) {
            foreach($_POST as $data) {
                foreach($data as $index => $key) {
                    if(isset($customfiles[$key]) && !empty($_FILES['file']['tmp_name'][$index])) {
                        $confFile = $customfiles[$key];

                        $ext = pathinfo($_FILES['file']['name'][$index], PATHINFO_EXTENSION);

                        if($confFile['type'] == 'image') {


                            /* size check disabled
                            $tmpfile = $_FILES['file']['tmp_name'][$index];
                            list($width, $height) = getimagesize($tmpfile);
                            if($confFile['size'] != "{$width}x{$height}") {
                                $errors[$key] = 'size';
                                continue;
                            }
                            */
                        } elseif($ext != $confFile['ext']) {
                            $errors[$key] = 'ext';
                            continue;
                        }

                        move_uploaded_file($_FILES['file']['tmp_name'][$index], $confFile['file']);
                    }
                }
            }
            $this->view->done = true;
        }

        $this->view->errors = $errors;
        $this->view->files = $customfiles;
    }

    private function getCustomUploadFiles() {
        $fileTypes = Kohana::$config->load('project.files');
        $fileUploads = array();
        foreach($fileTypes as $fileType => $files) {
            foreach($files as $type => $file) {
                $fileUploads[$fileType.$type] = array('type' => $fileType,
                                            'key' => $type,
                                            'ext' => pathinfo($file, PATHINFO_EXTENSION),
                                            'filename' => basename($file),
                                            'file' => $file,
                                            'title' => Helper_Message::get("backend.file.{$fileType}.{$type}"),
                                            'path' => dirname($file));

                if($fileType == 'image') {
                    list($width, $height) = getimagesize($file);
                    $fileUploads[$fileType.$type]['size'] = "{$width}x{$height}";
                }
            }
        }
        return $fileUploads;
    }
}
