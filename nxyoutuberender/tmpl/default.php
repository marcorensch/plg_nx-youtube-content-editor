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

    public function onContentPrepare() {
        echo '<script>console.log("started");</script>';
    }
}
?>
