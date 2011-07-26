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
<?php
if (isset($_GET['code']))
{
	echo "<div class='ytb_error'>Error, uploaded failed with code: {$_GET['code']} status: {$_GET['status']}</div>";
}
else if (!empty($_POST))
{
	echo "<h2>Adding Playlists</h2>";
	if (!empty($_POST['title']))
	{
		$newplid = $ytb->addNewPlaylist($_POST['title'], $_POST['desc']);
		if ($newplid)
		{
			$ytb->addToPlaylist($_POST['id'], $newplid);
		}
		else
		{
			echo "<div class='ytb_error'>Error: Unable to create new playlist</div>";
		}
	}

	if (isset($_POST['pl']))
	{
		foreach ($_POST['pl'] as $plid)
		{
			$ytb->addToPlaylist($_POST['id'], $plid);
		}
	}
}
else
{
	echo "<h2>Upload Successful</h2>";

	echo "
		<form method='POST' action='playlist.php'>
			<fieldset>
				<legend>Playlist Configuration</legend>
				<fieldset>
					<legend>Add Video to Existing Playlists</legend>";
	$pl = $ytb->getPlaylists();
	if (!$pl)
	{
		echo "<p class='ytb_error'>Error: Failed to get playlists.</p>";
	}
	foreach ($pl as $key => $val)
	{
		echo "
				<input type='checkbox' name='pl[]' id='$key' value='$key'
				title='{$val['desc']}' />
				<label for='$key'> {$val['title']}</label>";
	}

	echo "
				</fieldset>
				<fieldset>
					<legend>Create New Playlist</legend>
					<label for='title'>Title</label>
					<input type='text' name='title' id='title' />
					<label for='desc'>Description</label>
					<textarea title='Description for playlist' name='desc' id='desc'></textarea>
				</fieldset>
				<input type='hidden' name='id' id='id' value='{$_GET['id']}' />
				<input type='submit' name='submit' value='Set Playlists' />	
			</fieldset>
		</form>";
}
?>
	</body>
</html>
