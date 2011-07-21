<html>
	<head>
		<title>YouTube Dev Test</title>
	</head>
	<body>
		<h2>Hello YouTube API!</h2>
		<pre>
<?php
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

$authenticationURL= 'https://www.google.com/accounts/ClientLogin';
$user = 'youtubeuser';
$httpClient = 
  Zend_Gdata_ClientLogin::getHttpClient(
              $username = $user,
              $password = 'youtubepassword',
              $service = 'youtube',
              $client = null,
              $source = 'Test YouTube Playlists Tool', // a short string identifying your application
              $loginToken = null,
              $loginCaptcha = null,
              $authenticationURL);
$developerKey = 'youtubeapikey';
$applicationId = 'Test YouTube Playlists Tool v1';
$clientId = ''; // no longer needed

$yt = new Zend_Gdata_YouTube($httpClient, $applicationId, $clientId, $developerKey);
$yt->setMajorProtocolVersion(2); // default to the newer youtube protocol, 1.7.4 or above php client library

//How to list a user's playlist
$playlistListFeed = $yt->getPlaylistListFeed($user);

$count = 0;
foreach ($playlistListFeed as $playlistEntry) {
	echo 'Entry # ' . $count . "\n";

	echo $playlistEntry->title->text . "\n";
	echo $playlistEntry->description->text . "\n";
	echo $playlistEntry->getPlaylistVideoFeedUrl() . "\n";

	echo "\n";
	$count++;
}

?>
		</pre>
	</body>
</html>
