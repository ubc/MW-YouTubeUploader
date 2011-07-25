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
		$plFeed = $this->yt->getPlaylistListFeed($this->user);
		foreach ($plFeed as $plEntry)
		{
			$id = $plEntry->getPlaylistId()->text;
			$ret[$id]['title'] = $plEntry->title->text;
			$ret[$id]['desc'] = $plEntry->getDescription();
		}

		return $ret;
	}

	public function uploadVideo($title, $desc, $tags)
	{
		$vid = new Zend_Gdata_Youtube_VideoEntry();

		$vid->setVideoTitle($title);
		$vid->setVideoDescription($desc);
		$vid->setVideoTags($tags);
	}
}
?>
