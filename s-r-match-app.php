<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/s-r-match.php';

class mykarte_s_r_match_application extends mykarte_edit_application {

	function object_edit($prefix) {
		return new mykarte_s_r_match($prefix, $this->user);
	}

	function title_string() {
		return "すいせんマッチ";
	}

	function draw_body_1() {

		$this->draw_heading_l(3, "さがしています");
		$this->soe->s->draw_detailed();
		$this->draw_heading_l(3, "すいせんします");

	}

}
?>