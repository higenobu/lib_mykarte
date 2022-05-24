<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/common.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/lib.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/model.php';

function mykarte_update_user_info()
{
	$u = mx_authenticate_user('do-not-redirect');
	if ($u) {
		$m = new mykarte_user($u);
		$m->update_stats();
	}
}

class mykarte_application extends mykarte_base_application {
	var $is_top = 1;

	function add_css () {
		print '
div#profile, div#search-list, div#recommend-list, div#thankq-list {
	width: 50%;
	height: 50%;
	position: absolute;
}
div#thankq-list, div#recommend-list {
	left: 50%;
}
div#search-list, div#recommend-list {
	top: 50%;
}
table.profile, table.lister {
	padding-left: 1em;
	padding-right: 1em;
	width: 100%;
}
table.profile tr th {
	text-align: left;
	width: 30%;
}
';
	}

	function add_script () {
	}

	function main () {
		if (! $this->auth[0])
			return mx_authorization_error($this->auth);

		$this->draw_header($this->auth[1]);
		print "<body>\n";
		$this->draw();
		print "</body></html>\n";
	}

	function draw_profile() {

		$this->draw_heading('プロファイル', 'p-edit.php');
		print '<div class="fullwidth">';
		print '<table class="profile">';

		print '<tr class="o"><th>ハンドル名</th><td>';
		print htmlspecialchars($this->m->data['handle']);
		print '</td></tr>';

		print '<tr class="e"><th>メールアドレス</th><td>';
		print htmlspecialchars($this->m->data['email']);
		print '</td></tr>';

		print '<tr class="o"><th>名前</th><td>';
		print htmlspecialchars($this->m->data['display_name']);
		if ($this->m->data['anonymous'] == 'Y')
			print " (非公開)";
		print '</td></tr>';

		print '<tr class="e"><th>住所</th><td>';
		$zip = $this->m->data['zip'];
		if ($zip) {
		    $m = array();
		    if (preg_match('/^([0-9]{3})([0-9]{4})$/', $zip, &$m)) {
			    $zip = $m[1] . '-' . $m[2];
		    }
		    $zip = '〒' . $zip;
		}
		print htmlspecialchars($zip);
		print " ";
		print htmlspecialchars($this->m->data['pref']);
		print " ";
		print htmlspecialchars($this->m->data['city']);
		print '</td></tr>';

		print '<tr class="o"><th>ThankQ ポイント</th><td>';
		$p = array();
		foreach (array('scount' => 'さがしています',
			       'rcount' => 'すいせんします',
			       'rscount' => 'マッチした数',
			       'tcount' => '送った ThankQ',
			       'trcount' => '受けた ThankQ') as $c => $l) {
			$p[] = sprintf('<span title="%s">%s %s</span>',
				       htmlspecialchars($l),
				       htmlspecialchars(mb_substr($l, 0, 1)),
				       htmlspecialchars($this->m->data[$c]));
		}
		printf ("%s (%s)",
			$this->m->data['tpoint'], implode(", ", $p));
		print '</td></tr>';

		print '<tr class="e"><th>ThankQ ランキング </th><td>';
		$t_rank = $this->m->get_t_rank();
		printf ("%s 位", $t_rank);
		print '</td></tr>';

		print '</table></div>';
	}

	function draw_list($class, $limit) {
		$o = new $class();
		$recent = $o->list_recent($this->m, $limit);

		$ix = 0;
		$header = 1;
		print "<div class=\"fullwidth\">";
		print "<table class=\"lister\">";
		foreach ($recent as $d) {
			if ($header) {
				$d->draw_header();
				$header = 0;
			}
			$d->draw_as_row(++$ix);
		}
		print "</table></div>\n";
	}

	function draw_search_list() {
		$this->draw_heading('さがしています', 's-edit.php');
		$this->draw_list('mykarte_s', 20);
	}

	function draw_recommend_list() {
		$this->draw_heading('すいせんします', 'r-edit.php');
		$this->draw_list('mykarte_r', 20);
	}

	function draw_thankq_list() {
		$this->draw_heading('ThankQ');
		$this->draw_list('mykarte_t', 20);
	}

	function draw_body() {
		print '<div id="fourpane">';

		print '<div id="profile">';
		$this->draw_profile();
		print '</div>';

		print '<div id="thankq-list">';
		$this->draw_thankq_list();
		print '</div>';

		print '<div id="search-list">';
		$this->draw_search_list();
		print '</div>';

		print '<div id="recommend-list">';
		$this->draw_recommend_list();
		print '</div>';

		print '</div>';
	}
}
?>