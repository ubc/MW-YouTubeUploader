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
			$this->showError(wfMsg('youtubeuploader-upload-failed') . $code . " " . $status);
			return;
		}
		else if ($wgRequest->getVal('status') && $wgRequest->getVal('id'))
		{ // Step three, show playlist manipulation
			$this->addLogEntry($wgRequest->getVal('id'));
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
		else if (strcmp($wgRequest->getVal('view'), 'log') == 0)
		{
			$this->showLogs();
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
		$catinputs = "";
		foreach ($ret as $key => $val)
		{
			$catinputs .= "<option value='$key' id='$key' ";
			if (strcmp($key, "Education") == 0)
			{ // make education the default category
				$catinputs .= "selected='true'";
			}
			$catinputs .= ">$val</option>";
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
				</div>
				<label for='desc'>".wfMsg('youtubeuploader-desc')."</label>
				<textarea title='Description for the video' name='desc' id='desc'></textarea>
				
				<div class ='desc'>
				<span>".wfMsg('youtubeuploader-desclimit')."</span>
				<br />
				</div>
				<label for='tags'>".wfMsg('youtubeuploader-tags')."</label>
				<input type='text' name='tags' id='tags' />
				<br />
				<div class = 'desc'>
				<span>".wfMsg('youtubeuploader-taglimit')."</span>
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

		if (empty($title))
		{
			$this->showError(wfMsg('youtubeuploader-title-empty'));
			return;
		}
		$title = $this->validate($title, 100);
		if (!$title)
		{
			$this->showError(wfMsg('youtubeuploader-titlelimit-exceed') . wfMsg('youtubeuploader-titlelimit'));
			return;
		}
		$desc = $this->validate($desc, 5000);
		if (!$desc && !empty($desc))
		{
			$this->showError(wfMsg('youtubeuploader-desclimit-exceed') . wfMsg('youtubeuploader-desclimit'));
			return;
		}
		$tags = $this->validate($tags, 500);
		if (!$tags && !empty($tags))
		{
			$this->showError(wfMsg('youtubeuploader-taglimit-exceed') . wfMsg('youtubeuploader-taglimit'));
			return;
		}
		$tmptags = explode(',', $tags);
		foreach($tmptags as $val)
		{
			if (empty($val))
				continue;
			$val = trim($val);
			$res = $this->validate($val, 30);
			if (!$res)
			{
				$this->showError(wfMsg('youtubeuploader-tagtoolong') . "$val");
				return;
			}
			if (mb_strlen($res, 'latin1') < 2)
			{
				$this->showError(wfMsg('youtubeuploader-tagtooshort') . "$val");
				return;
			}
		}
		$ret = $this->ytb->uploadVideo($title, $desc, $tags, $cat);
		if (!$ret)
		{ 
			$this->showError(wfMsg('youtubeuploader-token-error'));
			return;
		}

		$nexturl = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		// build the form
		$form = '<form id="second" action="'. $ret['url'] .'?nexturl='.$spTitle->getFullURL().
			'" method="post" enctype="multipart/form-data">'. 
			'<input name="file" type="file"/>'. 
			'<input name="token" type="hidden" value="'. $ret['token'] .'"/>'.
			'<input value="'.wfMsg('youtubeuploader-upload').'" type="submit" 
			onclick="document.getElementById(\'youtubeuploader-uploading\').style.display = \'block\';"
			/>'. 
			'</form>'.
			'<p id="youtubeuploader-uploading" style="display:none;font-size: 1.1em;font-weight:bold;">'
			.wfMsg('youtubeuploader-uploading-status').'</p>';
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
			$this->showError(wfMsg('youtubeuploader-plretrival-failed'));
			return;
		}

		$plid = $wgRequest->getVal('id');

		$form = "
			<form method='POST' action='{$spTitle->getFullURL()}'>
			<fieldset id = 'third'>
			<legend>Playlist Configuration</legend>
			<div>
			<span>".wfMsg('youtubeuploader-addpl')."</span>";
		foreach ($pl as $key => $val)
		{
			$form .= "
			<div style='margin-left:1em;'>
				<input type='checkbox' name='pl[]' id='$key' value='$key'
				title='{$val['desc']}' />
				<label for='$key'><a href='https://www.youtube.com/view_play_list?p=$key'>{$val['title']}</a></label></div>";
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
				$pllist[$newplid] = $desc;
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
					$this->showError(wfMsg('youtubeuploader-addpl-error'));
					break;
				}
				$pllist[$ret->title->text] = $plid;
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
			$plinfo .= "** https://www.youtube.com/view_play_list?p=$val\n";
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

	private function showLogs()
	{
		global $wgOut;
		$dbr = wfGetDB(DB_SLAVE);
		$rows = $dbr->select('ytu_log', array('ytu_id','ytu_user','ytu_timestamp','ytu_title','ytu_link'));
		$table = "
{| border='1'
|-
!ID
!User
!Timestamp
!Title
!Link";
   	foreach ($rows as $row)
   	{
		$user = new User();
		$user->setId($row->ytu_user);
		$user->loadFromId();
   		$table .= "
|-
|{$row->ytu_id}
|{$user->getName()}
|{$row->ytu_timestamp}
|{$row->ytu_title}
|{$row->ytu_link}";
   	}
   	$table .= "
|}";
		$wgOut->addWikiText($table);
	}

	private function addLogEntry($vidid)
	{
		global $wgUser;

		$vid = $this->ytb->getVideo($vidid);
		$dbw = wfGetDB(DB_MASTER);
		$fields = array (
			'ytu_id'	=> $vidid,
			'ytu_user'	=> $wgUser->getID(),
			'ytu_timestamp' => $dbw->timestamp( time() ),
			'ytu_title' => $vid->getVideoTitle(),
			'ytu_link'	=> 	 $vid->getVideoWatchPageUrl()
		);
		$dbw->insert( 'ytu_log', $fields, __METHOD__, array('IGNORE'));

	}

	/**
	 * Cannot have any of the characters '<>' in the input and must not be
	 * great than length $len. Would be nice if we can use filter_var, but
	 * CentOS's php version doesn't support it.
	 */
	private function validate($var, $len)
	{
		if (preg_match('/[<>]/', $var))
		{
			return false;
		}
		else if (mb_strlen($var, 'latin1') > $len)
		{
			return false;
		}

		return $var;
	}

}
