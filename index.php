<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
require 'YouTubeBridge.php';
require 'YouTubeCategories.php';

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
					<legend>Select Video Category</legend>
<?php
$cats = new YouTubeCategories();
$ret = $cats->getCategories();
foreach ($ret as $key => $val)
{
	echo "<input type='radio' name='category' value='$key' id='$key' title='$val' /> <label for='$key'>$val</label>";
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
