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
$title = $_POST['title'];
$desc = $_POST['desc'];
$tags = $_POST['tags'];
$cat = $_POST['category'];

$ret = $ytb->uploadVideo($title, $desc, $tags, $cat);
if (!$ret)
{
	echo "<div class='ytb_error'>Error: Unable to get upload token.</div>";
}

#$nexturl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$nexturl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME']."/playlist.php";

// build the form
$form = '<form action="'. $ret['url'] .'?nexturl='. $nexturl .
        '" method="post" enctype="multipart/form-data">'. 
        '<input name="file" type="file"/>'. 
        '<input name="token" type="hidden" value="'. $ret['token'] .'"/>'.
        '<input value="Upload Video File" type="submit" />'. 
        '</form>';
echo $form;
?>
	</body>
</html>
