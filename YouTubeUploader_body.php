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

	private $ytb;
		
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

		# Initialize the youtube objects
		// workaround for loading the Zend Gdata library, just put the directory
		// to the library on the include path
		$oldpath = get_include_path();
		set_include_path($oldpath . PATH_SEPARATOR . $wgYTUIncludes);
		$this->ytb = new BridgeYouTubeUploader($wgYTU_User, $wgYTU_Password, $wgYTU_APIKey);

		if ($wgRequest->getVal('status') && $wgRequest->getVal('code'))
		{ // Step two failed, show error message
			$status = $wgRequest->getVal('status');
			$code = $wgRequest->getVal('code');
			$wgOut->addHTML("<p>Error, uploaded failed with code: $code status: $status</p>");
			return;
		}
		else if ($wgRequest->getVal('status') && $wgRequest->getVal('id'))
		{ // Step three, show playlist manipulation
			$this->showStepThree();
		}
		else if ($wgRequest->wasPosted() && $wgRequest->getVal('plid'))
		{ // We're finally done, show the result 
			$this->showResult();
		}
		else if ($wgRequest->wasPosted())
		{ // Step two, upload video
			$this->showStepTwo();
		}
		else
		{ // Step one, input video metadata
			$this->showStepOne();
		}

		// undo our library directory include, avoid conflict with other plugins
		set_include_path($oldpath);
	}
	
	private function showStepOne()
	{
		global $wgOut;
		$spTitle = $this->getTitle();
		$this->showStep(1);

		$cats = new CategoriesYouTubeUploader();
		$ret = $cats->getCategories();
		$catinputs = "<option value='' selected='true'></option>";
		foreach ($ret as $key => $val)
		{
			$catinputs .= "<option value='$key' id='$key'>$val</option>";
		}

		$wgOut->addHTML("
		<form method='POST' action='{$spTitle->getFullURL()}'>
			<fieldset>
				<legend>Video Metadata</legend>
				<label for='ytu_title'>".wfMsg('youtubeuploader-title')."</label>
				<input type='text' name='ytu_title' id='ytu_title' />
				<br />
				<span>".wfMsg('youtubeuploader-titlelimit')."</span>
				<br />
				<span>".wfMsg('youtubeuploader-titlelimit-adv')."</span>
				<br />
				<label for='desc'>".wfMsg('youtubeuploader-desc')."</label>
				<textarea title='Description for the video' name='desc' id='desc'></textarea>
				<br />
				<span>".wfMsg('youtubeuploader-desclimit')."</span>
				<br />
				<span>".wfMsg('youtubeuploader-desclimit-adv')."</span>
				<br />
				<label for='tags'>".wfMsg('youtubeuploader-tags')."</label>
				<input type='text' name='tags' id='tags' />
				<br />
				<span>".wfMsg('youtubeuploader-taglimit')."</span>
				<br />
				<span>".wfMsg('youtubeuploader-taglimit-adv')."</span>
				<br />
				<label for='category'>".wfMsg('youtubeuploader-cat')."</label>
				<select name='category' id='category'>
				$catinputs
				</select>
				<input type='submit' name='submit' value='".wfMsg('youtubeuploader-submit')."' />	
			</fieldset>
		</form>");
	}

	private function showStepTwo()
	{
		global $wgOut, $wgRequest;
		$spTitle = $this->getTitle();
		$this->showStep(2);

		$title = $wgRequest->getVal('ytu_title');
		$desc = $wgRequest->getVal('desc');
		$tags = $wgRequest->getVal('tags');
		$cat = $wgRequest->getVal('category');

		$ret = $this->ytb->uploadVideo($title, $desc, $tags, $cat);
		if (!$ret)
		{ // TODO ERROR
			return;
		}

		$nexturl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		// build the form
		$form = '<form action="'. $ret['url'] .'?nexturl='.$spTitle->getFullURL().
			'" method="post" enctype="multipart/form-data">'. 
			'<input name="file" type="file"/>'. 
			'<input name="token" type="hidden" value="'. $ret['token'] .'"/>'.
			'<input value="'.wfMsg('youtubeuploader-upload').'" type="submit" />'. 
			'</form>';
		$wgOut->addHTML($form);
	}

	private function showStepThree()
	{
		global $wgOut, $wgRequest;
		$spTitle = $this->getTitle();

		$this->showStep(3);

		$pl = $this->ytb->getPlaylists();
		if (!$pl)
		{
			$wgOut->addHTML("<p>Error: Failed to get playlists.</p>");
			return;
		}

		$plid = $wgRequest->getVal('id');

		$form = "
			<form method='POST' action='{$spTitle->getFullURL()}'>
			<fieldset>
			<legend>Playlist Configuration</legend>
			<fieldset>
			<legend>".wfmsg('youtubeuploader-addpl')."</legend>";
		foreach ($pl as $key => $val)
		{
			$form .= "
				<input type='checkbox' name='pl[]' id='$key' value='$key'
				title='{$val['desc']}' />
				<label for='$key'> {$val['title']}</label>";
		}

		$form .= "
			</fieldset>
			<fieldset>
			<legend>".wfmsg('youtubeuploader-newpl')."</legend>
			<label for='ytu_title'>".wfmsg('youtubeuploader-title')."</label>
			<input type='text' name='ytu_title' id='ytu_title' />
			<br />
			<label for='desc'>".wfmsg('youtubeuploader-desc')."</label>
			<textarea name='desc' id='desc'></textarea>
			</fieldset>
			<input type='hidden' name='plid' id='plid' value='$plid' />
			<input type='submit' name='submit' value='".wfmsg('youtubeuploader-submit')."' />	
			</fieldset>
			</form>";
		$wgOut->addHTML($form);
	}


	private function showResult()
	{
		global $wgOut, $wgRequest;
		$title = $wgRequest->getVal('ytu_title');
		$desc = $wgRequest->getVal('desc');
		$id = $wgRequest->getVal('plid');
		$pl = $wgRequest->getArray('pl');
		$pllist = array();
		if ($title)
		{
			$newplid = $this->ytb->addNewPlaylist($title, $desc);
			if ($newplid)
			{
				$this->ytb->addToPlaylist($id, $newplid);
				$pllist[$title] = $desc;
			}
			else
			{
				$this->showError(wfMsg('youtubeuploader-newpl-error'));
			}
		}

		if ($pl)
		{
			foreach ($pl as $plid)
			{
				$ret = $this->ytb->addToPlaylist($id, $plid);
				if (!$ret)
				{
					$this->showError("Error: Unable to add to playlist");
					break;
				}
				$pllist[$ret->title->text] = $ret->description->text;
			}
		}

		$videoEntry = $this->ytb->getVideo($id);
		if (!$videoEntry)
		{
			$this->showError("youtubeuploader-uploadfailed");
			return;
		}
		$wgOut->addWikiText(wfMsg('youtubeuploader-done') . " {$videoEntry->getVideoWatchPageUrl()}");
		$wgOut->addWikiText("==".wfMsg('youtubeuploader-vidinfo')."==");
		$wgOut->addWikiText(wfMsg('youtubeuploader-title').": {$videoEntry->getVideoTitle()}");
		$wgOut->addWikiText(wfMsg('youtubeuploader-cat').": {$videoEntry->getVideoCategory()}");
		$wgOut->addWikiText(wfMsg('youtubeuploader-tags').": ".implode(", ", $videoEntry->getVideoTags()));
		$wgOut->addWikiText(wfMsg('youtubeuploader-pl').": ");
		$plinfo = "";
		foreach ($pllist as $key => $val)
		{
			$plinfo .= "* ".wfMsg('youtubeuploader-title').": $key\n";
		}
		$wgOut->addWikiText($plinfo);
		
	}


	private function showStep($step)
	{
		global $wgOut;

		$instructions = "";
		$step1 = wfMsg('youtubeuploader-step1');
		$step2 = wfMsg('youtubeuploader-step2');
		$step3 = wfMsg('youtubeuploader-step3');
		$here = wfMsg('youtubeuploader-here');
		switch($step)
		{
		case 1:
			$instructions = '# '."'''$step1''' - ''$here''\n".'# '. "$step2\n".'# ' . "$step3\n";
			break;
		case 2:
			$instructions = '# '."$step1\n".'# '. "'''$step2''' - ''$here''\n".'# ' . "$step3\n";
			break;
		case 3:
			$instructions = '# '."$step1\n".'# '. "$step2\n".'# ' . "'''$step3''' - ''$here''\n";
			break;
		default:
			$instructions = "How did I get here?!";
		}

		$wgOut->addWikiText(wfMsg('youtubeuploader-greet'));
		$wgOut->addWikiText($instructions);
	}

	private function showError($error)
	{
		global $wgOut;

		$wgOut->addHTML("<p style='background-color:#F08080; padding:1em; text-align:center;'>$error</p>");
	}

}
