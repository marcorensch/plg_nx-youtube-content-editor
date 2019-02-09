<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_nx_simple_eventlist
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


// no direct access
defined( '_JEXEC' ) or die;

if (!defined('NXYTBTN_PLUGIN_FOLDER')) {
	define('NXYTBTN_PLUGIN_FOLDER', 'nxyoutubebutton');
};

if (!defined('NXYTRNDR_PLUGIN_FOLDER')) {
	define('NXYTRNDR_PLUGIN_FOLDER', 'nxyoutuberender');
};

jimport('joomla.plugin.plugin');
jimport('joomla.form.form');
jimport('joomla.html.parameter');

class PlgContentNxyoutubeRender extends JPlugin
{
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	
	/** Core service object. */
	private $core;

	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{

		$text = $article->text;

		/* Activation tag used to produce videocontainer with the plug-in. */
	 	$tag = 'nxyt';

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer')
		{
			echo '<script>console.log("Indexer Mode");</script>';
			return true;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, $tag) === false )
		{
			echo '<script>console.log("Tag nicht gefunden '.$tag. '");</script>';
			return true;
		}

		// pattern for key/value parameter list
		$param_pattern = '(?:[^{/}]+|/(?!\})|\{\$[^{/}]+\})*';  // characters other than curly braces, or variable substitutions in the style "{$variable}"

		$playercount = 0;

		// find {nxyt}...{/nxyt} tags and emit code
		$tag_player = preg_quote($tag, '#');
		$pattern = '#\{'.$tag_player.'\b('.$param_pattern.')\}(.+?)\{/'.$tag_player.'\}#msSu';
		$playercount += $this->getPlayerReplacementAll($text, $pattern);

		

		return $playercount > 0;
	}

	/**
	* Replaces all occurrences of a gallery activation tag.
	* @param {string} $text Article (content item) text.
	* @param {string} $pattern Replacement regular expression pattern.
	*/
	private function getPlayerReplacementAll(&$text, $pattern)
	{
		$count = 0;
		$offset = 0;
		while (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE, $offset)) {

			$count++;
			$start = $match[0][1];
			$end = $start + strlen($match[0][0]);

			try {
				$innertext = isset($match[2]) ? $match[2][0] : null;  // text in between start and end tags (unless omitted)
				echo '<script>console.log("Inner Text '.$innertext. '");</script>';
				$paramtext = $match[1][0];
				echo "<script>console.log('Param Text $paramtext');</script>";
				$body = $this->getPlayerReplacementSingle($innertext, $paramtext);
				$text = substr($text, 0, $start).$body.substr($text, $end);
				$offset = $start + strlen($body);
			} catch (Exception $e) {
				$app = JFactory::getApplication();
				switch ($this->core->verbosityLevel()) {
					case 'none':
						// display no message, hide activation tag completely
						$text = substr($text, 0, $start).substr($text, $end);
						$offset = $start;
						break;
					case 'verbose':
					default:
						// display a specific, informative message
						$message = $e->getMessage();

						// leave activation tag as it appears
						$offset = $end;

						// show error message
						$app->enqueueMessage($message, 'error');
				}
			}
		}
		return $count;
	}

	/**
	* Replaces a single occurrence of a gallery activation tag.
	* @param {string} $sourcetext A string that identifies the image source.
	* @param {string} $paramtext A string that stores parameter key/value pairs.
	*/
	private function getPlayerReplacementSingle($sourcetext, $paramtext) {

		// the activation code {gallery key=value}myfolder{/gallery} translates into a source and a parameter string
		$paramtext = self::strip_html($paramtext);
		//$this->core->setParameterString($paramtext);

		echo '<script>console.log("The End is reached");</script>';
	}

	private static function strip_html($html) {
		$text = html_entity_decode($html, ENT_QUOTES, 'utf-8');  // translate HTML entities to regular characters
		$text = str_replace("\xc2\xa0", ' ', $text);  // translate non-breaking space to regular space
		$text = strip_tags($text);  // remove HTML tags
		return $text;
	}

	private static function setParameterString($params){
		return $params;
	}

}
?>
