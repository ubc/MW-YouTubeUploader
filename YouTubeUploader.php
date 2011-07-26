<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) 
{
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once("extensions/MyExtension/MyExtension.php");
EOT;
        exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'YouTubeUploader',
	'author' => 'John Hsu',
	'version' => '0.1',
	'description' => 'Improved Upload videos to YouTube',
	'descriptionmsg' => 'youtubeuploader-desc',
);

$wgYTU_User = "youtubeuser";
$wgYTU_Password = "youtubepassword";
$wgYTU_APIKey = "youtubeapikey";

$dir = dirname(__FILE__) . '/';

# Messages file
$wgExtensionMessagesFiles['YouTubeUploader'] = $dir . 'YouTubeUploader.i18n.php';

# Add special page
$wgSpecialPages['YouTubeUploader'] = 'SpecialYouTubeUploader';
$wgAutoloadClasses['SpecialYouTubeUploader'] = $dir . 'YouTubeUploader_body.php';

# Library imports
$wgYTUIncludes = $dir . 'includes/';

$wgAutoloadClasses['Zend_Loader'] = $wgYTUIncludes . 'Zend/Loader.php';
$wgAutoloadClasses['BridgeYouTubeUploader'] = $wgYTUIncludes . 'BridgeYouTubeUploader.php';
$wgAutoloadClasses['CategoriesYouTubeUploader'] = $wgYTUIncludes . 'CategoriesYouTubeUploader.php';
$wgSpecialPageGroupes['YouTubeUploader'] = 'other';

?>
