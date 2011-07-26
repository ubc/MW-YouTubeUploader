<?php
if (!defined('MEDIAWIKI')) {
	exit;
}
/**
 * Class provides a special page
 *
 * @ingroup Extensions
 */

class SpecialYouTubeUploader extends SpecialPage 
{
	function __construct() 
	{
		parent::__construct( 'YouTubeUploader' );
		wfLoadExtensionMessages( 'YouTubeUploader' );
	}

	function execute( $par ) 
	{
		global $wgRequest, $wgOut, $wgMemc, $wgUser;
		global $wgYTU_User, $wgYTU_Password, $wgYTU_APIKey;
		global $wgYTUIncludes;

		$this->setHeaders();

		# Check permissions
		if( !$wgUser->isAllowed( 'upload' ) ) {
			if( !$wgUser->isLoggedIn() ) {
				$wgOut->showErrorPage( 'uploadnologin', 'uploadnologintext' );
			}
			else {
				$wgOut->permissionRequired( 'upload' );
			}
			return;
		}
		$wgOut->addHTML('<h3>' .wfMsg('youtubeuploader-greet').'</h3>');

		# Initialize the youtube objects
		// workaround for loading the Zend Gdata library, just put the directory
		// to the library on the include path
		$oldpath = get_include_path();
		set_include_path($oldpath . PATH_SEPARATOR . $wgYTUIncludes);
		$ytb = new BridgeYouTubeUploader($wgYTU_User, $wgYTU_Password, $wgYTU_APIKey);
		// undo our library directory include, avoid conflict with other plugins
		set_include_path($oldpath);

		$cats = new CategoriesYouTubeUploader();
		$ret = $cats->getCategories();
		$catinputs = "<option value='' selected='true'></option>";
		foreach ($ret as $key => $val)
		{
			$catinputs .= "<option value='$key' id='$key'>$val</option>";
		}

		$wgOut->addHTML("<form method='POST' action='upload.php'>
			<fieldset>
				<legend>Upload Video</legend>
				<fieldset>
					<legend>Describe the Video</legend>
					<label for='title'>Title</label>
					<input type='text' name='title' id='title' />
					<label for='desc'>Description</label>
					<textarea title='Description for the video' name='desc' id='desc'></textarea>
					<label for='tags'>Tags</label>
					<input type='text' name='tags' id='tags' />
				</fieldset>
				<fieldset>
					<legend>Select Video Category</legend>
					<select name='category' id='category'>
					$catinputs
					</select>
				</fieldset>
				<input type='submit' name='submit' value='Select Video to Upload' />	
			</fieldset>
		</form>");



	}	
				
}
