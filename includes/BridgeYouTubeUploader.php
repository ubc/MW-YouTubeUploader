<?php
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

class BridgeYouTubeUploader
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
	private $yt; // Zend Youtube library object
	private $abc;


	/***********************
	 * PUBLIC METHODS
	 * ********************/
	function __construct($user, $pw, $key)
	{
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
		try
		{
            $plFeed = $this->getAllPlaylistFeed();
		}
		catch (Zend_Gdata_App_Exception $e)
		{
			return false;
		}

		$titles = array();
		$descs = array();
		foreach ($plFeed as $plEntry)
		{
			$id = $plEntry->getPlaylistId()->text;
			$titles[$id] = $plEntry->title->text;
			$descs[$id] = $plEntry->description->text;
		}

		// we want the playlists to appear in alphabetical order
		natcasesort($titles);

		$ret = array();
		foreach ($titles as $key => $val)
		{
			$ret[$key]['title'] = $val;
			$ret[$key]['desc'] = $descs[$key];
		}

		return $ret;
	}

	public function uploadVideo($title, $desc, $tags, $cat, $unlist)
	{
		$vid = new Zend_Gdata_Youtube_VideoEntry();

		$vid->setVideoTitle($title);
		$vid->setVideoDescription($desc);
		// TODO input checking
		$vid->setVideoCategory($cat); // must be valid category
		$vid->setVideoTags($tags); // must be comma-delimited, no whitespace in keywords

		// check unlisted video option
		if (!empty($unlist))
		{ // set as unlisted video
			$accessControlElement = new Zend_Gdata_App_Extension_Element('yt:accessControl', 'yt', 'http://gdata.youtube.com/schemas/2007', ''); 
			$accessControlElement->extensionAttributes = array(
				array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
				array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')); 

			$vid->setExtensionElements(array($accessControlElement));
		}

		$tokenurl = 'http://gdata.youtube.com/action/GetUploadToken';
		try 
		{
			$token = $this->yt->getFormUploadToken($vid, $tokenurl);
		} 
		catch (Zend_Gdata_App_Exception $e)
		{
			#echo $e->getMessage();
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
		foreach ($this->yt->getPlaylistVideoFeed($posturl) as $val)
		{ // check for duplicates
			if (strcmp($val->getVideoId(), $videoid) == 0)
				return $feed; // duplicate detected
		}
		$vid = $this->yt->getVideoEntry($videoid);
		if (!$vid)
		{
			return false;
		}

		$plentry = $this->yt->newPlaylistListEntry($vid->getDOM()); 

		try
		{
			$this->yt->insertEntry($plentry, $posturl);
		}
		catch (Zend_App_Exception $e)
		{
			return false;
		}
		return $feed;
	}

	public function getVideo($videoid)
	{
		try
		{
			$videoEntry = $this->yt->getVideoEntry($videoid);
		}
		catch (Zend_App_Exception $e)
		{
			return false;
		}
		return $videoEntry;
	}

	/***********************
	 * PRIVATE METHODS
	 * ********************/
	private function getPlaylistEntry($playlistid)
	{
		$plfeed = $this->getAllPlaylistFeed();

		foreach ($plfeed as $plentry)
		{
			if (strcmp($plentry->getPlaylistId()->text, $playlistid) == 0)
			{
				return $plentry;
			}
		}
		return false;
	}

    private function getAllPlaylistFeed()
    {
        $plFeed = $this->yt->getPlaylistListFeed('default');
        // by default, a feed is limited to 25 results, so we need
        // to indicate that we want all the results
        $plFeed = $this->yt->retrieveAllEntriesForFeed($plFeed);
        return $plFeed;
    }
}

?>
