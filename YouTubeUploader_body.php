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
		$this->addStyle();
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
	
	private function addStyle() 
	{	
		global $wgOut, $wgRequest;
		$wgOut->addHTML("
		  <style><!--
		  		
		  	legend{	color:#005896; font-size: 120%; }
		  		
			fieldset{ width: 750px; background-color: #F7FAFC; border: 1px solid #AFBAC7;
					-moz-border-radius: 10px; border-radius: 10px; padding: 0.5em 1.5em 1.5em;
					min-width: 750px;}				
				
			#first label, #newPlaylist label{ width: 75px; display:block; float:left;
					border-bottom: 1px dotted #014778; margin-top: 0.1em;}

			#third fieldset legend{ color:grey; font-size:110%; }
				
			#third fieldset{ border:none; width 100%; border-top: solid 1px grey;}
				
			#first input[type='text'], #newPlaylist input[type='text']{	margin-left: .8em; width: 600px;}
				
			#third span {font-size:105%; color:#4D4D4D; display:block;
					border-bottom: solid 1px #CED7DE; margin-bottom: 1em;}
				
			#newPlaylist {margin-left:1em;}
				
			textarea {width: 600px;	margin-left: .8em;}
				
			select {margin-left: .8em;}
				
			.desc {margin: 0.5em 0 1.5em 0.5em; font-size: 90%; color:#555;}
				
			.here {color:#BA0000;}
				
			.submit {width:100%; text-align: center; margin-top:0.5em;}
			
			.submit input {width: 100px;}
								
			#second {background-color: #F7FAFC;	border: 1px solid #AFBAC7; margin: 1em 0;
    				padding: 0.5em;	text-align: center;	width: 420px;}
		   --></style>
		");		
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
			<fieldset id ='first'>
				<legend>Video Metadata</legend>
				<label for='ytu_title'>".wfMsg('youtubeuploader-title')."</label>
				<input type='text' name='ytu_title' id='ytu_title' />
				<br />
				<div class='desc'>
				<span>".wfMsg('youtubeuploader-titlelimit')."</span>
				<br />
				<!---<span>".wfMsg('youtubeuploader-titlelimit-adv')."</span>				
				<br />--->
				</div>
				<label for='desc'>".wfMsg('youtubeuploader-desc')."</label>
				<textarea title='Description for the video' name='desc' id='desc'></textarea>
				
				<div class ='desc'>
				<!---<span>".wfMsg('youtubeuploader-desclimit')."</span>
				<br />--->
				<span>".wfMsg('youtubeuploader-desclimit-adv')."</span>
				<br />
				</div>
				<label for='tags'>".wfMsg('youtubeuploader-tags')."</label>
				<input type='text' name='tags' id='tags' />
				<br />
				<div class = 'desc'>
				<!---<span>".wfMsg('youtubeuploader-taglimit')."</span>
				<br />--->
				<span>".wfMsg('youtubeuploader-taglimit-adv')."</span>
				<br />
				</div>
				<label for='category'>".wfMsg('youtubeuploader-cat')."</label>
				<select name='category' id='category'>
				$catinputs
			
				</select>
				<br/>
					<div class ='submit'>
				<input type='submit' name='submit' value='".wfMsg('youtubeuploader-submit')."' />	
				</div>
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
		$form = '<form id="second" action="'. $ret['url'] .'?nexturl='.$spTitle->getFullURL().
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
			<fieldset id = 'third'>
			<legend>Playlist Configuration</legend>
			<div>
			<span>".wfmsg('youtubeuploader-addpl')."</span>";
		foreach ($pl as $key => $val)
		{
			$form .= "
			<div style='margin-left:1em;'>
				<input type='checkbox' name='pl[]' id='$key' value='$key'
				title='{$val['desc']}' />
				<label for='$key'> {$val['title']}</label></div>";
		}

		$form .= "
			</div>
			<br />
				<span>".wfmsg('youtubeuploader-newpl')."</span>
			<div id = 'newPlaylist'>
		
			
			<label for='ytu_title'>".wfmsg('youtubeuploader-title')."</label>
			<input type='text' name='ytu_title' id='ytu_title' style='width: 200px'/>
			<br /><br/>

			<label for='desc'>".wfmsg('youtubeuploader-desc')."</label>
			<textarea name='desc' id='desc'></textarea>
		
			</div>
			<input type='hidden' name='plid' id='plid' value='$plid' />
			
			<div class ='submit'>
			<input type='submit' name='submit' style='margin-top:1em;' value='".wfmsg('youtubeuploader-submit')."' />	
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
		$here = "<span class = 'here'> ".wfMsg('youtubeuploader-here')."</span>" ;

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
