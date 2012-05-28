<pre><?php

	if (!file_exists(dirname(__FILE__).'/wordpress.pot')) {
		exit('wordpress.pot not found, download from: http://svn.automattic.com/wordpress-i18n/pot/trunk/wordpress.pot');
	}

	if (!file_exists(dirname(__FILE__).'/wordpress-admin.pot')) {
		exit('wordpress.pot not found, download from: http://svn.automattic.com/wordpress-i18n/pot/trunk/wordpress-admin.pot');
	}

	require dirname(__FILE__).'/strings.php';
	require dirname(__FILE__).'/lib/po.php';

	$po = new PO();
	$po->import_from_file(dirname(__FILE__).'/wordpress.pot');
	$po->import_from_file(dirname(__FILE__).'/wordpress-admin.pot');

	foreach ($strings as $string) {
		if (!has_translation($string)) print 'missing translation for: '.$string."\n";
	}
	print "\ndone...\n";

	function has_translation($string) {
		global $po;
		return isset($po->entries[$string]);
	}

?>