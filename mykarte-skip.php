<?php // -*- mode: php; coding: euc-japan -*-
include_once $_SERVER['DOCUMENT_ROOT'].'/lib/mykarte/mykarte-app.php';

class mykarte_skip_application extends mykarte_application {
	function main () {
		if (! $this->auth[0])
			return mx_authorization_error($this->auth);
		$cookie = getenv('URL_PREFIX_COOKIE');
		if (!$this->m->data['patient_id'] || !$cookie)
			return mykarte_application::main();

		$pid = ('?SetPatient=1&PatientID=' .
			$this->m->data['patient_id']);
		$target = "/u/everybody/index-pt.php$pid";
		return mx_http_redirect("/au/$cookie$target");
	}
}
?>
