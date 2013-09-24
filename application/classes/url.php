<?php

class Url extends Kohana_Url {

	public static function currentUri() {
		$request = Request::$current;
		return $request->uri();
	}

	public static function get($data = array(), $useParams = false) {

		$request = Request::current();

		if(is_string($data)) {
			$newData = array();
			foreach(explode(' ', $data) as $line) {
				list($param, $value) = explode(':', $line);
				$newData[$param] =  $value;
			}

			$data = $newData;
		}

		$data = array_merge($useParams ? $request->param() : array(), array(
			'directory' => $request->directory(),
			'route' => 'default',
			'controller' => $request->controller(),
			'action' => $request->action(),
		), $data);

		$route = $data['route'];
		unset($data['route']);

		return Route::get($route)->uri($data);
	}
}