<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
require 'YouTubeBridge.php';

$user = 'youtubeuser';
$pw = 'youtubepassword';
$key = 'youtubeapikey';

$ytb = new YouTubeBridge($user, $pw, $key);

?>
<html>
	<head>
		<title>YouTube Dev Test</title>
	</head>
	<body>
		<h2>Hello YouTube API!</h2>

		<pre>
<?php
print_r($_POST);
?>
		</pre>
	</body>
</html>
