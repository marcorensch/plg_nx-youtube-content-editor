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

		// Add Stylesheet for responsive behavior
		$document = JFactory::getDocument();
		$document->addStyleSheet('plugins/content/nxyoutuberender/media/css/nxyoutuberender.css');
		JHtml::_('jquery.framework');
		//$document->addScript('plugins/content/nxyoutuberender/media/js/nxyoutubereplacer.js.php');

		// pattern for key/value parameter list
		$param_pattern = '(?:[^{/}]+|/(?!\})|\{\$[^{/}]+\})*';  // characters other than curly braces, or variable substitutions in the style "{$variable}"

		$playercount = 0;

		// find {nxyt}...{/nxyt} tags and emit code
		$tag_player = preg_quote($tag, '#');
		$pattern = '#\{'.$tag_player.'\b('.$param_pattern.')\}(.+?)\{/'.$tag_player.'\}#msSu';

		$article->text = $this->getPlayerReplacementAll($text, $pattern); // counter for idk
	}


	private function setPlayerParameter($paramtext){
		// Plugin Parameters:
		$plg_params = $this->params;
/*
		var_dump($this->params->get('layout'));
		highlight_string("<?php\n\$data =\n" . var_export($this->params, true) . ";\n?>");
*/
		// User Parameters:
		$array= explode(' ', trim($paramtext));
		$usr_params = array();
		foreach($array as $arr){
			$element = explode('=',$arr);
			// dirty fix for source url containing "="
			if($element[0] === 'source' && count($element)>2){
				// Whole url contains =
				$value = str_replace("\"", "", $element[2]);
				
			}else{
				// simple data
				$value = str_replace("\"", "", $element[1]);
			}
			$new_arr = array($element[0]=> $value);
			$usr_params = array_merge($usr_params, $new_arr);
		}
		// Combine Parameters
		$video_params = new stdClass();
		$video_params->nxytid = 'nxyt_' . rand(5, 99);
		$video_params->source = $usr_params['source'];
		foreach($plg_params as $key => $value){
			if(array_key_exists($key, $usr_params)){
				$video_params->$key = $usr_params[$key];
			}else{
				$video_params->$key = $value;
			};
		}
		return $video_params;
	}


	/**
	* Replaces all occurrences of a video activation tag.
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

			$innertext = isset($match[2]) ? $match[2][0] : null;  // text in between start and end tags (unless omitted)
			echo '<script>console.log("Inner Text '.$innertext. '");</script>';
			$paramtext = $match[1][0];
			echo "<script>console.log('Param Text $paramtext');</script>";
			$body = $this->getPlayerReplacementSingle($paramtext);
			$text = substr($text, 0, $start).$body.substr($text, $end);

			$offset = $start + strlen($body);

		};

		return $text;
	}

	/**
	* Replaces a single occurrence of a video activation tag.
	* @param {string} $paramtext A string that stores parameter key/value pairs.
	*/
	private function getPlayerReplacementSingle($paramtext) {

		// the activation code {nxyt key=value}urlorid{/nxyt} translates into a source and a parameter string
		$paramtext = self::strip_html($paramtext);
		// Parameters
		
		$playerParameters = self::setPlayerParameter($paramtext);
		$cls = ' nx-float-'.$playerParameters->pl_float;
		echo '<pre>' . var_export($playerParameters, true) . '</pre>';

		switch($playerParameters->cont_width){
			case '25':
				$cls .= ' nx-col-3';
				break;
			case '33':
				$cls .= ' nx-col-4';
				break;
			case '50':
				$cls .= ' nx-col-6';
				break;
			case '75':
				$cls .= ' nx-col-9';
				break;
			case '100':
			default:
				$cls .= ' nx-col-12';
				break;
		};

		$playersetupstring = '?autoplay='.$playerParameters->pl_ap.'&controls='.$playerParameters->pl_ctrl.'&disablekb='.$playerParameters->pl_dis_kb.'&cc_load_policy='.$playerParameters->pl_sub.'&playsinline='.$playerParameters->pl_ios.'&modestbranding='.$playerParameters->pl_mb.'&loop='.$playerParameters->pl_lo.'&fs='.$playerParameters->pl_fs.'&origin='.JUri::getInstance();


		$placeholder = '<div class="nx-video-placeholder" data-container-id="'.$playerParameters->nxytid.'"><div class="placeholder_inner">'.$playerParameters->block_message.'</div></div>';
		$iframe = '<iframe width="1920" height="1080" src="https://www.youtube-nocookie.com/embed/'.$playerParameters->source.$playersetupstring.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
		$document = JFactory::getDocument();
		$document->addScriptDeclaration('
		jQuery(document).ready(function($){
			console.log(\'nx-youtube plugin replacer for '.$playerParameters->nxytid.' loaded\');
			$(\'.nx-video-container\').on(\'click\',\'.nx-video-placeholder[data-container-id="'.$playerParameters->nxytid.'"]\', function(){
				let containerId = $(this).attr(\'data-container-id\');
				$(\'.nx-video-container[data-container-id="\'+containerId+\'"]\').html(\''.$iframe.'\');
			});
		});
		
		');

		$player = '';
		$player .= '<div class="nx-video-container-outer '.trim($cls).'">';
		$player .= 		'<div class="nx-video-container nx-margin-'.$playerParameters->pl_margin.'" style="background-color:'.$playerParameters->block_message_bg.'" data-container-id="'.$playerParameters->nxytid.'">';
		if(intval($playerParameters->block_loading)){
			$player .= 			$placeholder;
		}else{
			$player .= 			$iframe;
		};
		$player .= 		'</div>';
		$player .= '</div>';

		

		return $player;
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
