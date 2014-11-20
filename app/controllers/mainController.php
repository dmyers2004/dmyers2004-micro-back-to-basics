<?php

class mainController {
	public function indexAction() {	
		$data['name'] = 'Johnny Appleseed';

		echo '<pre>';
		var_dump(mvc());

		return view('main/index', $data);
	}

	public function indexPostAjaxAction() {	
		$data['name'] = print_r($_POST, true);

		return view('main/index', $data);
	}

	public function indexPostAction() {	
		$data['name'] = print_r($_POST, true);

		return view('main/index', $data);
	}
}