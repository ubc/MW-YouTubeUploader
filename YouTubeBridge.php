<?php
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

class YouTubeBridge
{
	/***********************
	 * CONSTANTS
	 * ********************/
	// These constants are used to login to YouTube
	const SERVICE = 'youtube'; // name of the service we want to login to
	const AUTHURL = 'https://www.google.com/accounts/ClientLogin'; // using the client login url
	const APPID = 'YouTube Bridge Tool'; // short string to identify the application

	/***********************
	 * PRIVATE MEMBERS
	 * ********************/
	private $user;
	private $yt; // Zend Youtube library object
	private $abc;


	/***********************
	 * PUBLIC METHODS
	 * ********************/
	function __construct($user, $pw, $key)
	{
		$this->user = $user;

		$httpClient = 
			Zend_Gdata_ClientLogin::getHttpClient(
				$username = $user,
				$password = $pw,
				$service = self::SERVICE,
				$client = null,
				$source = self::APPID,
				$loginToken = null,
				$loginCaptcha = null,
				self::AUTHURL);

		$this->yt = new Zend_Gdata_YouTube($httpClient, self::APPID, '', $key);
		$this->yt->setMajorProtocolVersion(2);
	}

	public function getPlaylists()
	{
		$ret = array();
		try
		{
			$plFeed = $this->yt->getPlaylistListFeed($this->user);
		}
		catch (Zend_Gdata_App_Exception $e)
		{
			return false;
		}

		foreach ($plFeed as $plEntry)
		{
			$id = $plEntry->getPlaylistId()->text;
			$ret[$id]['title'] = $plEntry->title->text;
			$ret[$id]['desc'] = $plEntry->getDescription();
		}

		return $ret;
	}

	public function uploadVideo($title, $desc, $tags, $cat)
	{
		$vid = new Zend_Gdata_Youtube_VideoEntry();

		$vid->setVideoTitle($title);
		$vid->setVideoDescription($desc);
		// TODO input checking
		$vid->setVideoCategory($cat); // must be valid category
		$vid->setVideoTags($tags); // must be comma-delimited, no whitespace in keywords
		$tokenurl = 'http://gdata.youtube.com/action/GetUploadToken';
		try 
		{
			$token = $this->yt->getFormUploadToken($vid, $tokenurl);
		} 
		catch (Zend_Gdata_App_Exception $e)
		{
			return false;
		}
		return $token;
	}

	public function addNewPlaylist($title, $desc)
	{
		$newpl = $this->yt->newPlaylistListEntry();
		$newpl->title = $this->yt->newTitle()->setText($title);
		$newpl->description = $this->yt->newDescription()->setText($desc);
		$postLocation = 'http://gdata.youtube.com/feeds/api/users/default/playlists';
		try 
		{
			$newpl = $this->yt->insertEntry($newpl, $postLocation, 'Zend_Gdata_YouTube_PlaylistListEntry');
			// workaround for crappy Gdata API v2 support in the Zend library,
			// it insists that $newpl is a v1 object, but var_dump shows that
			// it's clearly a v2 object, wtf? This just forces v2 recognition
			$newpl->setMajorProtocolVersion(2);
		} 
		catch (Zend_Gdata_App_Exception $e) 
		{
			return false;
		}
		return $newpl->getPlaylistId()->text;
	}

	public function addToPlaylist($videoid, $playlistid)
	{
		$feed = $this->getPlaylistEntry($playlistid);
		if (!$feed)
		{
			return false;
		}
		$posturl = $feed->getPlaylistVideoFeedUrl();
		$vid = $this->yt->getVideoEntry($videoid);

		$plentry = $this->yt->newPlaylistListEntry($vid->getDOM()); 

		try
		{
			$this->yt->insertEntry($plentry, $posturl);
		}
		catch (Zend_App_Exception $e)
		{
			return false;
		}
		return true;
	}

	/***********************
	 * PRIVATE METHODS
	 * ********************/
	private function getPlaylistEntry($playlistid)
	{
		$plfeed = $this->yt->getPlaylistListFeed($this->user);

		foreach ($plfeed as $plentry)
		{
			if (strcmp($plentry->getPlaylistId()->text, $playlistid) == 0)
			{
				return $plentry;
			}
		}
		return false;
	}
}
?>
