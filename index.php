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
		<form method='POST' action="upload.php">
			<fieldset>
				<legend>Upload Video</legend>
				<fieldset>
					<legend>Describe the Video</legend>
					<label for="title">Title</label>
					<input type="text" name="title" id="title" />
					<label for="desc">Description</label>
					<textarea title="Description for the video" name="desc" id="desc"></textarea>
					<label for="tags">Tags</label>
					<input type="text" name="tags" id="tags" />
				</fieldset>
				<fieldset>
					<legend>Add Video to Playlists</legend>
<?php
$pl = $ytb->getPlaylists();
foreach ($pl as $key => $val)
{
	echo "<input type='checkbox' name='pl[]' id='$key' value='$key'
		title='{$val['desc']}' />
		<label for='$key'> {$val['title']}</label>";
}

?>
				</fieldset>
				<input type="submit" name="submit" value="Select Video to Upload" />	
			</fieldset>
		</form>


		<pre>
<?php

?>
		</pre>
	</body>
</html>
