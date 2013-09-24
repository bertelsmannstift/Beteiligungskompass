<?php

/**
 * @Entity
 * @Table(name="files")
 */
class Model_File extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	/**
	 * @Column(type="string", name="md5", length=32, unique=false, nullable=false)
	 */
	protected $md5 = null;

	/**
	 * @Column(type="string", name="filename", length=999, unique=false, nullable=false)
	 */
	protected $filename = null;

	/**
	 * @Column(type="string", name="mime", length=999, unique=false, nullable=false)
	 */
	protected $mime = null;

	/**
	 * @Column(type="string", name="ext", length=999, unique=false, nullable=false)
	 */
	protected $ext = null;

	/**
	 * @Column(type="integer", name="size")
	 */
	protected $size = null;

    function getFileSize() {
        return number_format($this->size / 1048576, 2, ',', '');
    }

	public function __construct($file) {
		if(!$file OR !file_exists($file)) {
			throw new Kohana_Exception('Model_File: File not found :file', array(':file' => $file));
		}

		$md5 = md5_file($file);

		$this->filename = basename($file);
		$this->md5 = $md5;

		$info = pathinfo($this->filename);
		$this->ext = strtolower($info['extension']);

		$this->mime = File::mime($file);
		$this->size = filesize($file);

		copy($file, Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . $md5 . '.' . $this->ext);
	}

	public function previewurl($size = '43x42') {
		$filename =  basename($this->filename,'.'.$this->ext);
		return Url::get(array('route' => 'previewimage', 'size' => $size, 'id' => $this->id, 'filename' => $filename . '.jpg'));
	}

	public function link() {
		return Url::get(array('route' => 'media', 'id' => $this->id, 'filename' => $this->filename));
	}

	public function path() {
		return Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . $this->md5 . '.' . $this->ext;
	}

}