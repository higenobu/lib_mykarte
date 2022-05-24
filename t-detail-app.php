<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/anno-edit.php';

class mykarte_t_detail_application extends mykarte_srt_detail_application {

	function object_setup () {
		$t = new mykarte_t();
		$this->object = $t->fetch_one($_REQUEST['id']);
		$this->soe = new mykarte_a_t_edit('a-t-edit',
						  $this->user,
						  $this->object);
	}

	function title_string () {
		return "ThankQ";
	}

	function recent_details() {
		$o = new mykarte_a_t($this->object->data['id']);
		return $o->list_recent($this->m, 10);
	}

}
?>