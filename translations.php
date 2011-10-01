<pre><?php

	if (!file_exists(dirname(__FILE__).'/wordpress.pot')) {
		exit('wordpress.pot not found, download from: http://svn.automattic.com/wordpress-i18n/pot/trunk/wordpress.pot');
	}

	require dirname(__FILE__).'/../../../../wp-includes/pomo/po.php';

	$po = new PO();
	$po->import_from_file(dirname(__FILE__).'/wordpress.pot');

	$strings = array(
		// singular
		'Cheatin&#8217; uh?',
		'These revisions are identical.',
		'Settings',
		'Settings saved.',
		'5 stars',
		'4 stars',
		'3 stars',
		'2 stars',
		'1 star',
		'Save Changes',
		'An unknown error occurred.',
		'Save',
		'These revisions are identical.',
		'Older: %s',
		'Newer: %s',
		'Revisions',
		'Compare Revisions',
		'Author',
		'Actions',
		'%1$s [Current Revision]',
		'Restore',
		// plural
		'(based on %s rating)',
		// context
		'revision date format'.chr(4).'j F, Y @ G:i',
		'revisions column name'.chr(4).'Old',
		'revisions column name'.chr(4).'New',
		'revisions column name'.chr(4).'Date Created'
	);

	foreach ($strings as $string) {
		if (!has_translation($string)) print 'missing translation for: '.$string."\n";
	}
	print "all good...\n";

	function has_translation($string) {
		global $po;
		return isset($po->entries[$string]);
	}

?>