<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/soe.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

class mykarte_base_application {

	function mykarte_base_application () {
		$this->user = mx_authenticate_user();
		$this->auth = mx_authorization();
		if (! $this->auth[0])
			return;
		$this->m = new mykarte_user($this->user);
	}

	function add_css() {
	}

	function add_script() {
	}

	function draw() {

		print '<div id="toppane">';
		$this->draw_top_pane();
		print '</div>';

		$this->draw_body();
	}

	function draw_top_pane() {
		global $_mx_resource_dir;

		print "<div class=\"heading\"><h2>";
		print "<div class=\"fullwidth\">";
		print "<img src=\"/$_mx_resource_dir/images/tq.png\">";

		if ($this->m->data['patient_id']) {
			$pid = ('?SetPatient=1&PatientID=' .
				$this->m->data['patient_id']);
		}
		else
			$pid = '';

		print "<div class=\"right\">";
		if ($this->is_top) {
			print "<a href=\"../u/everybody/index-pt.php$pid\">";
			print 'MyKARTE へ';
			print '</a>';
		} else {
			print "<a href=\"index.php\">";
			print 'ThankQNote へ';
			print '</a>';
		}
		print "\n";
		print '<a href="../logout.php">';
		print 'ログアウト';
		print '</a>';
		print "</div>";

		print "</div>";

		print "</h2></div>";
	}

	function draw_heading($string, $where_to='') {
		$string = htmlspecialchars($string);
		if ($where_to)
			$string = "<a href=\"$where_to\">$string</a>";
		print "<div class=\"heading\"$xtra><h2>$string</h2></div>";
	}

	function draw_heading_l($level, $string, $where_to='') {
		$string = htmlspecialchars($string);
		$h2 = "h$level";
		if ($where_to)
			$string = "<a href=\"$where_to\">$string</a>";
		print "<div class=\"heading\"$xtra><$h2>$string</$h2></div>";
	}

	function draw_header($title) {
		mx_html_head($title, 1);
		print '<style>
body {
	background-image: none;
	background: #fde;
}
div.fullwidth {
	width: 100%;
}
div.padded {
	padding-left: 1em;
	padding-right: 1em;
}
div.right {
 position: absolute;
 right: 2em;
 display: inline;
}
div.right * {
 text-align: right;
 vertical-align: top;
}
div#toppane div.heading {
 background: #4cc;
}
div.heading {
 background: #4c0;
 color: #ff4;
 margin: 0.4em;
}
h3 {
 font-size: 100%;
 padding-left: 1em;
 padding-right: 1em;
}
h2 {
 font-size: 120%;
 padding-left: 1em;
 padding-right: 1em;
}
div.heading h2 a {
 background: #4c0;
 color: #ff4;
 font-size: 100%;
}
table.detailed {
 width: 100%
}
table.detailed tr th {
 text-align: left;
 width: 30%;
}
tr.e {
 background: #dec;
}
tr.o {
 background: #edc;
}
th {
 vertical-align: top;
}
';
		$this->add_css();
		print '</style>
<script>
var roundedCornersOnLoad = function() {
	roundClass("div", "heading");
};
addLoadEvent(roundedCornersOnLoad);
';
		$this->add_script();
		print "</script></head>\n";
	}
}

class mykarte_edit_application extends mykarte_base_application {

	function mykarte_edit_application() {
		mykarte_base_application::mykarte_base_application();
		if (! $this->auth[0])
			return;
		$this->soe = $this->object_edit('mkedit-');
	}

	function main () {
	    if (! $this->auth[0])
		    return mx_authorization_error($this->auth);

	    if ($this->soe->commit_ran)
		    $this->update_user_stats();

	    if ($this->soe->commit_ran || $this->soe->cancelled)
		    mx_http_redirect('index.php');

	    $this->draw_header($this->auth[1]);
	    print "<body>\n";
	    $this->draw();
	    print "</body></html>\n";
	}

	function update_user_stats() { // override
		$this->m->update_stats();
	}

	function draw_body_1() { // override
	}

	function draw_body() {
		$this->draw_heading($this->title_string());
		print '<div class="fullwidth"><div class="padded">';
		$this->draw_body_1();
		print '<form method="POST">';
		$this->soe->draw();
		if (!$this->soe->commit_tried)
			$this->soe->draw_control($this->soe->so_config);
		print '</form>';
		print '</div></div>';
	}

}

class mykarte_edit extends simple_object_edit {

//	var $empty_after_commit = 0;

	function mykarte_edit($prefix, $u, $cfg) {
		$this->user = $u;
		simple_object_edit::simple_object_edit($prefix, $cfg);
	}

        function precompute_insert_stmt_head() {}
        function resync() {}

	function reset() {
		simple_object_edit::reset();
		$this->data = array();
	}

	// this is inside "begin"
	function try_commit(&$db) {
		$this->change_nature = 'create';
		return $this->create_one($db, $this->data);
	}

	function _validate($force=NULL) {
		$this->commit_tried = 1;
		$status = simple_object_edit::_validate($force);
		return $status;
	}

	function annotate_form_data(&$data) {
		simple_object_edit::annotate_form_data($data);

		foreach ($this->so_config['ECOLS'] as $e) {
			if ($e['Draw'] != 'reason_codes')
				continue;
			$name = $this->prefix . $e['Column'];
			$value = '';
			foreach ($e['Option'] as $k => $v) {
				if ($_REQUEST[$name . '_' . $k]) {
					$value .= $k;
				}
			}
			$data[$e['Column']] = $value;
		}
	}

	function dx_reason_codes($desc, $name, $value) {
		$name = $this->de($name);
		foreach ($desc['Option'] as $k => $v) {
			$n = $this->prefix . $name . '_' . $k;
			mx_formi_checkbox($n, (strstr($value, $k) !== FALSE));
			print htmlspecialchars($v);
			print '<br />';
		}
	}

}

class mykarte_srt_detail_application extends mykarte_base_application {

	function mykarte_srt_detail_application () {
		mykarte_base_application::mykarte_base_application();

		$this->object_setup();
		if ($this->soe->cancelled || $this->soe->commit_ran) {
			$this->soe->anew(NULL);
		}
	}

	function main () {
		if (! $this->auth[0])
			return mx_authorization_error($this->auth);

	    if ($this->soe->cancelled)
		    mx_http_redirect('index.php');

		$this->draw_header($this->auth[1]);
		print "<body>\n";
		$this->draw();
		print "</body></html>\n";
	}

	function draw_body_1 () { // override
	}

	function draw_body () {
		$this->draw_heading($this->title_string());

		print '<div class="fullwidth"><div class="padded">';
		$this->object->draw_detailed();
		$this->draw_body_1();
		print '</div></div>';

		$recent = $this->recent_details();

		if (count($recent)) {
			$this->draw_heading("コメント");

			print '<div class="fullwidth"><div class="padded">';
			$first = 1;
			foreach ($recent as $d) {
				if (!$first)
					print "<hr />\n";
				$d->draw_detailed();
				$first = 0;
			}
			print '</div></div>';
		}

		$this->draw_heading("コメントの追加");

		print '<div class="fullwidth"><div class="padded">';
		print '<form method="POST">';
		$this->soe->draw();
		if ($this->soe->commit_ran || !$this->soe->commit_tried)
			$this->soe->draw_control($this->soe->so_config);
		print '</form>';
		print '</div></div>';
	}
}

?>