<pre><?php

	if (!file_exists(dirname(__FILE__).'/humanstxt.pot')) {
		exit('humanstxt.pot not found, download from: http://plugins.svn.wordpress.org/humanstxt/trunk/languages/humanstxt.pot');
	}

	$pot = file_get_contents(dirname(__FILE__).'/humanstxt.pot');
	$pot = preg_replace('~#\. translators: DO NOT TRANSLATE!.+?\n#~s', '#', $pot);
	file_put_contents(dirname(__FILE__).'/humanstxt-clean.pot', $pot);

	print "\nsaved...\n";

?>