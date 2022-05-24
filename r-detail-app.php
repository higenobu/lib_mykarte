<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/anno-edit.php';

class mykarte_r_detail_application extends mykarte_srt_detail_application {

	function object_setup () {
		$r = new mykarte_r();
		$this->object = $r->fetch_one($_REQUEST['id']);
		$this->soe = new mykarte_a_r_edit('a-r-edit',
						  $this->user,
						  $this->object);
	}

	function title_string () {
		return "すいせんします";
	}

	function recent_details() {
		$o = new mykarte_a_r($this->object->data['id']);
		return $o->list_recent($this->m, 10);
	}

}
?>