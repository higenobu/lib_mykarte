<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/s-edit.php';

class mykarte_s_edit_application extends mykarte_edit_application {

	function object_edit($prefix) {
		return new s_edit_edit($prefix, $this->user);
	}

	function title_string() {
		return "さがしています";
	}
}
?>