<?php
class CategoriesYouTubeUploader
{
	/***********************
	 * CONSTANTS
	 * ********************/
	const URL = 'http://gdata.youtube.com/schemas/2007/categories.cat';
	const XMLFILE = 'categories.cat';

	/***********************
	 * PRIVATE MEMBERS
	 * ********************/
	private $xml;

	/***********************
	 * PUBLIC METHODS
	 * ********************/
	function __construct()
	{
		global $wgYTUIncludes;

		$context = stream_context_create(array(
			'http' => array('timeout' => 1) // Timeout in seconds
		));

		$xml = file_get_contents(self::URL, 0, $context);

		$xmlfile = $wgYTUIncludes . self::XMLFILE;
		if (empty($xml))
		{ // timeout, fallback using file
			$xml = file_get_contents($xmlfile);
		}
		else
		{
			file_put_contents($xmlfile, $xml);
		}
		$this->xml = simplexml_load_string($xml);
	}

	/**
	 * Return the categories that we can assign videos to.
	 * */
	public function getCategories()
	{
		$ret = array();
		foreach ($this->xml->children('http://www.w3.org/2005/Atom') as $child)
		{
			foreach ($child->children('http://gdata.youtube.com/schemas/2007') as $grandchild)
			{
				if (strcmp($grandchild->getName(), 'assignable') == 0)
				{
					$key = (string)$child->attributes()->term;
					$val = (string)$child->attributes()->label;
					$ret[$key] = $val;
				}
			}
		}
		asort($ret);
		return $ret;
	}

	/***********************
	 * PRIVATE METHODS
	 * ********************/

}

/*
echo "<pre>";
$ytb = new YouTubeCategories();
$ret = $ytb->getCategories();
print_r($ret);
echo "</pre>";
 */


?>
