<?php
/**
 * @package     Joomla.Site      
 * @subpackage  nxyoutubebutton (Plugin)
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


// no direct access
defined( '_JEXEC' ) or die;

if (!defined('NXYTBTN_PLUGIN_FOLDER')) {
	define('NXYTBTN_PLUGIN_FOLDER', 'nxyoutubebutton');
}

if (!defined('NXYTRNDR_PLUGIN_FOLDER')) {
	define('NXYTRNDR_PLUGIN_FOLDER', 'nxyoutuberender');
}

jimport('joomla.plugin.plugin');
jimport('joomla.form.form');
jimport('joomla.html.parameter');

/**
* Triggered when the nxyoutube content plug-in is unavailable or there is a version mismatch.
*/
class nxYoutubeEditorDependencyException extends Exception {
	/**
	* Creates a new exception instance.
	* @param {string} $key Error message language key.
	*/
	public function __construct() {
		$key = 'PLG_NXYTBTN_EXCEPTION_EXTENSION';
		$message = JText::_($key);  // get localized message text old: '['.$key.'] '.JText::_($key);
		parent::__construct($message);
	}
}

class PlgEditorsXtdNxyoutubebutton extends JPlugin
{
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var    boolean
	 * @since  3.1
	 */
    protected $autoloadLanguage = true;

    private function importTemplateCSS($css_file) {
		$app = JFactory::getApplication();
		$css_base_path = JPATH_BASE.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$app->getTemplate().DIRECTORY_SEPARATOR.'css';
		$css_file_path = $css_base_path.DIRECTORY_SEPARATOR.$css_file;
		$css_min_file = pathinfo($css_file, PATHINFO_FILENAME).'.min.'.pathinfo($css_file, PATHINFO_EXTENSION);
		$css_min_file_path = $css_base_path.DIRECTORY_SEPARATOR.$css_min_file;
		if (file_exists($css_min_file_path) || file_exists($css_file_path)) {
			if (file_exists($css_min_file_path)) {
				$css_imported_file = $css_min_file;
			} else {
				$css_imported_file = $css_file;
			}
			print '<link rel="stylesheet" href="'.JURI::base(true).'/templates/'.$app->getTemplate().'/css/'.$css_imported_file.'" type="text/css" />'.PHP_EOL;
		}
    }

    private function importTemplateJS($js_file) {
		$app = JFactory::getApplication();
		$js_base_path = JPATH_BASE.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$app->getTemplate().DIRECTORY_SEPARATOR.'js';
		$js_file_path = $js_base_path.DIRECTORY_SEPARATOR.$js_file;
		$js_min_file = pathinfo($js_file, PATHINFO_FILENAME).'.min.'.pathinfo($js_file, PATHINFO_EXTENSION);
		$js_min_file_path = $js_base_path.DIRECTORY_SEPARATOR.$js_min_file;
		if (file_exists($js_min_file_path) || file_exists($js_file_path)) {
			if (file_exists($js_min_file_path)) {
				$js_imported_file = $js_min_file;
			} else {
				$js_imported_file = $js_file;
			}
			print '<script type="text/javascript" src="'.JURI::base(true).'/templates/'.$app->getTemplate().'/js/'.$js_imported_file.'"></script>'.PHP_EOL;
		}
    }
    

    public function onDisplay($editorname, $asset, $author) {

        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $extension = $app->input->get('option');

        $asset = $asset !== '' ? $asset : $extension;

		if (!$user->authorise('core.edit', $asset) &&
			!$user->authorise('core.create', $asset) &&
			!(count($user->getAuthorisedCategories($asset, 'core.create')) > 0) &&
			!($user->authorise('core.edit.own', $asset) && $author === $user->id) &&
			!(count($user->getAuthorisedCategories($extension, 'core.edit')) > 0) &&
			!(count($user->getAuthorisedCategories($extension, 'core.edit.own')) > 0 && $author === $user->id)) {
				return false;
        }
        try{
            // load nxyoutube content plug-in
			if (!JPluginHelper::importPlugin('content', NXYTRNDR_PLUGIN_FOLDER)) {
				throw new nxYoutubeEditorDependencyException();
            }
            // load language file for internationalized labels
            $lang = JFactory::getLanguage();
            $lang->load('plg_content_'.NXYTRNDR_PLUGIN_FOLDER, JPATH_ADMINISTRATOR);

            // load nxyoutube content plug-in parameters
            $plugin = JPluginHelper::getPlugin('content', NXYTRNDR_PLUGIN_FOLDER);
            
            // load configuration XML file
            $xmlfile = JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.NXYTRNDR_PLUGIN_FOLDER.DIRECTORY_SEPARATOR.NXYTRNDR_PLUGIN_FOLDER.'.xml';
            $customxmlfile = JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'editors-xtd'.DIRECTORY_SEPARATOR.NXYTBTN_PLUGIN_FOLDER.DIRECTORY_SEPARATOR.NXYTBTN_PLUGIN_FOLDER.'.xml';

            $htmlfile = JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'editors-xtd'.DIRECTORY_SEPARATOR.NXYTBTN_PLUGIN_FOLDER.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.'button.'.$lang->getTag().'.html';
            $jsdir = JPATH_ROOT.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'editors-xtd'.DIRECTORY_SEPARATOR.NXYTBTN_PLUGIN_FOLDER.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'js';

            $form = new JForm(NXYTRNDR_PLUGIN_FOLDER);
            $form->loadFile($xmlfile, true, '/extension/config/fields');
            $fieldSets = $form->getFieldsets('params');

            $customForm = new JForm(NXYTBTN_PLUGIN_FOLDER);
            $customForm->loadFile($customxmlfile, true, '/extension/config/fields');
            $customFieldSets = $customForm->getFieldsets('params');

            ob_start();
            print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.PHP_EOL;
            print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$lang->getTag().'" lang="'.$lang->getTag().'">'.PHP_EOL;
            print '<head>'.PHP_EOL;
            print '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'.PHP_EOL;

            
            // import administration area template CSS file
            //$this->importTemplateCSS(JURI::base(true) . '/media/jui/css/bootstrap.min.css');
            //$this->importTemplateCSS('bootstrap.css');
            //$this->importTemplateCSS('template.css');
            //print '<link rel="stylesheet" href="'.JURI::root(true) . '/media/jui/css/bootstrap.min.css" type="text/css" />'.PHP_EOL;
            //print '<link rel="stylesheet" href="'.JURI::root(true) . '/media/jui/css/chosen.css" type="text/css" />'.PHP_EOL;

            

            print '<link rel="stylesheet" href="../css/modal.css" type="text/css" />'.PHP_EOL;

            /* --------- Script for the Button ---------------- */
            print '<script type="text/javascript" src="' . JURI::root(true) . '/media/jui/js/jquery.js"></script>';

            if (file_exists($jsdir.DIRECTORY_SEPARATOR.'insert.min.js')) {
                $jsfile = 'insert.min.js';
            } else {
                $jsfile = 'insert.js';
            }
            
            

            
            print '<script type="text/javascript" src="../js/'.$jsfile.'"></script>'.PHP_EOL;
            //print '<script type="text/javascript" src="' . JURI::root(true) . '/media/jui/js/chosen.jquery.js"></script>'.PHP_EOL;
            //print '<script type="text/javascript" src="' . JURI::root(true) . '/media/jui/js/bootstrap.js"></script>'.PHP_EOL;
            //$this->importTemplateJS('template.js');

            print '</head>'.PHP_EOL;
            print '<body>'.PHP_EOL;
            print '<form id="nxyt-settings-form">'.PHP_EOL;
            print '<button class="nxyt-settings-submit btn btn-primary" type="button">'.JText::_('PLG_NXYTBTN_EDITORBUTTON_INSERT').'</button>'.PHP_EOL;
/*
            echo '<pre>' . var_export($xmlfile, true) . '</pre>';
            echo '<pre>' . var_export($fieldSets, true) . '</pre>';
*/

            /* ---------------- Fields Renderer ---------------- */

            /* Additional Fields from Button XML */

            //echo '<pre>' . var_export($customForm, true) . '</pre>'; 

            foreach ($customFieldSets as $name => $fieldSet) {
                $fields = $customForm->getFieldset($name);

                $hasfields = false;
                /*
                foreach ($fields as $field) {
                    var_dump($field->fieldname);
                    if (isset($vars[$field->fieldname])) {
                        print 'true<br>';
                        $hasfields = true;
                        break;
                    }
                }
                if (!$hasfields) {
                    continue;
                }
*/
                // field group title
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_PLUGINS_'.$name.'_FIELDSET_LABEL';
                print '<h3>'.JText::_($label).'</h3>';
                if (isset($fieldSet->description) && trim($fieldSet->description)) {
                    print '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
                }

                // field group elements
                print '<fieldset class="panelform">'.PHP_EOL;
                $hidden_fields = '';
                print '<ul>'.PHP_EOL;
                foreach ($fields as $field) {
                    if (!$field->hidden) {
                        
                        //echo '<pre>' . var_export($field, true) . '</pre>'; 

                        print '<li class="formelm">';
                        print $field->label;
                        print $field->input;
                        print '</li>'.PHP_EOL;
                    } else {
                        $hidden_fields .= $field->input;
                    }
                }
                print '</ul>'.PHP_EOL;
                print $hidden_fields;
                print '</fieldset>'.PHP_EOL;
            };
            


            foreach ($fieldSets as $name => $fieldSet) {
                $fields = $form->getFieldset($name);

                $hasfields = false;
                /*
                foreach ($fields as $field) {
                    var_dump($field->fieldname);
                    if (isset($vars[$field->fieldname])) {
                        print 'true<br>';
                        $hasfields = true;
                        break;
                    }
                }
                if (!$hasfields) {
                    continue;
                }
*/
                // field group title
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_PLUGINS_'.$name.'_FIELDSET_LABEL';
                print '<h3>'.JText::_($label).'</h3>';
                if (isset($fieldSet->description) && trim($fieldSet->description)) {
                    print '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
                }

                // field group elements
                print '<fieldset class="panelform">'.PHP_EOL;
                $hidden_fields = '';
                print '<ul>'.PHP_EOL;
                foreach ($fields as $field) {
                    if (!$field->hidden) {
                        
                        //echo '<pre>' . var_export($field, true) . '</pre>'; 

                        print '<li class="formelm">';
                        print $field->label;
                        print $field->input;
                        print '</li>'.PHP_EOL;
                    } else {
                        $hidden_fields .= $field->input;
                    }
                }
                print '</ul>'.PHP_EOL;
                print $hidden_fields;
                print '</fieldset>'.PHP_EOL;
            };

            /* ---------------- End Fields Render ---------------- */
            print '<button class="nxyt-settings-submit btn btn-primary" type="button">'.JText::_('PLG_NXYTBTN_EDITORBUTTON_INSERT').'</button>'.PHP_EOL;
            print '</form>'.PHP_EOL;
            print '<p>'.JText::_('nx-designs').'</p>'.PHP_EOL;
            print '<div id="debug"></div>'; // DIV Container for JS Debug in Modal
            print '</body>'.PHP_EOL;
            print '</html>'.PHP_EOL;
            $html = ob_get_clean();
            if (file_put_contents($htmlfile, $html) === false) {
                throw new NxYoutubeButtonException($htmlfile);
            }

            $params = json_decode($plugin->params);

            // allow modal window script to access default parameter values
            $doc = JFactory::getDocument();
            $doc->addScriptDeclaration('window.nxyoutube = '.json_encode($params).';');

            
            // add modal window
            JHTML::_('behavior.modal');
            $button = new JObject;
            $button->class = 'btn btn-secondary';
            $button->modal = true;
            $app = JFactory::getApplication();
            if ($app->isAdmin()) {
                $root = '../';  // Joomla expects a relative path, leave site folder "administrator"
            } else {
                $root = '';
            }
            $button->link = $root.'plugins/editors-xtd/'.NXYTBTN_PLUGIN_FOLDER.'/media/html/button.php?lang='.$lang->getTag().'&editor='.urlencode($editorname);
            $button->text = 'nx-YouTube Video';
            $button->name = 'youtube';
            if (version_compare(JVERSION, '4.0') >= 0) {
                $button->options = array(
                    'width' => '700px',
                    'height' => '600px'
                );
            } else {
                $button->options = "{handler: 'iframe', size: {x: 700, y: 600}}";  // must use single quotes in JSON options string
            };

            return $button;

        } catch (Exception $e) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($e->getMessage(), 'error');
		}
		return false;
    }
}
?>
