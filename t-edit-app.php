<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/t-edit.php';

class mykarte_t_edit_application extends mykarte_edit_application {

	function object_edit($prefix) {
		return new t_edit_edit($prefix, $this->user);
	}

	function title_string() {
		return "ThankQ";
	}

	function draw_body_1() {
		$thank_r = $this->soe->thank_r;
		$thankee = $this->soe->thankee;

		if ($thank_r) {
			$rfac = new mykarte_r();
			$r = $rfac->fetch_one($thank_r);
			$r->draw_link_to_me();
			print "をすいせんして下さった、";
		}
		$tu = new mykarte_user(NULL, $thankee);
		print htmlspecialchars($tu->get_name());
		print "さんに ThankQ を送ります。";
	}

	function update_user_stats() {
		mykarte_edit_application::update_user_stats();
		$tu = new mykarte_user(NULL, $this->soe->thankee);
		$tu->update_stats();
	}

}
?>