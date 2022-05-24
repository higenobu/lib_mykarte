<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/anno-edit.php';

class mykarte_s_detail_application extends mykarte_srt_detail_application {

	function object_setup () {
		$s = new mykarte_s();
		$this->object = $s->fetch_one($_REQUEST['id']);
		$this->soe = new mykarte_a_s_edit('a-s-edit',
						  $this->user,
						  $this->object);
	}

	function title_string () {
		return "さがしています";
	}

	function recent_details() {
		$o = new mykarte_a_s($this->object->data['id']);
		return $o->list_recent($this->m, 10);
	}

	function draw_recommend_link() {
		$id = $this->object->data['id'];

		print "<a href=\"r-edit.php?sid=$id\">";
		print htmlspecialchars("すいせんできる先生知ってます！");
		print "</a>\n";

		if (count($this->object->r_in_same_area_unmatched())) {
			print "<a href=\"s-r-match.php?sid=$id\">";
			print htmlspecialchars("すいせんされた先生知ってます！");
			print "</a>\n";
		}
	}


	function draw_body_1() {

		$id = $this->object->data['id'];

		$this->draw_heading_l(3, "すいせん");
		$r_list = $this->object->matching_r();
		if ($r_list) {
			print '<div class="fullwidth">';
			$first = 1;
			foreach ($r_list as $rid => $d) {
				if (!$first)
					print "<hr />";
				$d->draw_detailed();
				$first = 0;
			}
			print "</div>";
		}

		if ($this->object->data['createdby'] != $this->m->data['mykarte_user'])
			$this->draw_recommend_link();
	}

}
?>