/**
 * @file
 * @brief    nxYoutubeButton Modal Safe Button Script
 * @author   nx-designs
 * @version  1.0
 * @remarks  Copyright (C) 2019 nx-designs
 * @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
 * @see      http://nx-designs.ch
 */

'use strict';

let paramsobj = {};
let paramsArray = [];

if (!Element.prototype.matches) {
    Element.prototype.matches =
        Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector;
}

function getJoomlaEditorInstance(editor) {
    let joomla = window.parent['Joomla'];
    if (joomla) {
        let editors = joomla['editors'];
        if (editors) {
            let instances = editors['instances'];
            if (instances && instances.hasOwnProperty(editor)) {
                return instances[editor];
            }
        }
    }
};

/**
 * Inserts a nxyoutube activation tag into the Joomla content editor.
 * @param {string} editor The identifier of the Joomla editor.
 * @param {string} tag The activation tag (as a string).
 * @param {string} parameters The list of parameters as key/value pairs.
 */
function insertTag(editor, tag, parametersstring) {
    if (paramsobj.source) {
        //console.log(paramsobj);
        let text = '{' + tag + parametersstring + '}' + paramsobj.source + '{/' + tag + '}';
        let parent = window.parent;

        // use new API if editor supports it
        let instance = getJoomlaEditorInstance(editor);
        if (instance) {
            instance['replaceSelection'](text);
        } else {
            let insertEditorText = /** @type {function(string,string)} */ (parent['jInsertEditorText']);
            insertEditorText(text, editor);
        }

        let closeModalDialog = /** @type {function()} */ (parent['jModalClose']);
        closeModalDialog();
    } else {
        alert('Nope - there is no Video source given in your config. Please tell us a video url or id');
    }


};

/**
 * Parses a query string into name/value pairs.
 * @param {string} querystring A string of "name=value" pairs, separated by "&".
 * @return {!Object<string>} An object where keys are parameter names, and value are parameter values.
 */
function fromQueryString(querystring) {
    let parameters = {};
    
    if (querystring.length > 1) {
        querystring.substr(1).split('&').forEach(function(keyvalue) {
            let index = keyvalue.indexOf('=');
            let key = index >= 0 ? keyvalue.substr(0, index) : keyvalue;
            let value = index >= 0 ? keyvalue.substr(index + 1) : '';
            parameters[decodeURIComponent(key)] = decodeURIComponent(value);
        });
    }

    return parameters;
}

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Extracts the Joomla parameter name from a control.
     * @param {!HTMLInputElement|!HTMLSelectElement} ctrl
     * @return {string}
     */
    function get_param_name(ctrl) {
        let name = ctrl.getAttribute('name');
        let matches = name.match(/^params\[(.*)\]$/);
        return matches ? matches[1] : name;
    }

    let form = document.getElementById('nxyt-settings-form'); // configuration settings form
    let listitems = [].slice.call(form.querySelectorAll('li'));

    // selectors to match all user controls (only controls with the "name" attribute correspond to real plug-in settings, others are auxiliary controls)
    let checkboxselector = 'input[name][type=checkbox]';
    let radioselector = 'input[name][type=radio]';
    let textselector = 'input[name][type=text]';
    let mediaselector = 'input[name][type=media]';
    let listselector = 'select[name]';
    let ctrlselector = [checkboxselector, radioselector, textselector, listselector, mediaselector].join();

    // initialize parameter values to those set on content plug-in configuration page
    let options = window.parent['nxyoutube'];

    //console.log(options);
    if (options) { // variable that holds configuration settings as JSON object with parameter names as keys
        [].forEach.call(form.querySelectorAll(ctrlselector), function(elem) { // enumerate form controls in order of appearance
            let ctrl = /** @type {!HTMLInputElement|!HTMLSelectElement} */ (elem);
            let name = get_param_name(ctrl);
            let value = options[name];
            if (value) { // has a default value
                if (ctrl.matches(checkboxselector)) { // checkbox control
                    ctrl.checked = !!value;
                } else if (ctrl.matches(radioselector) && ctrl.value === '' + value) { // related radio button (with value to assign matching button value)
                    ctrl.checked = true;
                } else if (ctrl.matches([textselector, listselector].join())) { // text and list controls
                    ctrl.value = value;
                }
            }
        });
    }

    // bind event to make parameter value appear in generated activation code
    /*
    [].forEach.call(listitems, function(item) {
        // create marker control
        let updatebox = document.createElement('input');
        updatebox.setAttribute('type', 'checkbox');

        // check marker control when parameter value is to be edited
        [].forEach.call(item.querySelectorAll(ctrlselector), function(elem) {
            elem.addEventListener('focus', function() {
                updatebox.checked = true;
            });
        });

        // insert marker control before parameter name label
        item.insertBefore(updatebox, item.firstChild); // inject as first element
    });

    */

    // selects all user controls but omits checkboxes and radio buttons that are not checked
    let checkedselector = ':checked';
    let activectrlselector = [checkboxselector + checkedselector, radioselector + checkedselector, textselector, listselector].join();

    // Remove Checkboxes for Spacers on Ready
    jQuery(document).ready(function($) {

        // Remove Checkbox for Spacer Entries
        jQuery('.spacer').each(function() {
            $(this).addClass('nx-h4');
            let parent = $(this).closest('.formelm');
            //console.log(parent);
            parent.children('input').hide();
        });

        // Set active on click
        jQuery('input, select').not(".diff_selector").change(function() {
            let parent = $(this).closest('div.group');
            let titlerow = parent.find('div.checkbox');
            let checkbox = titlerow.find('input');
            checkbox.prop('checked', true);
            //console.log(checkbox);
        });

        // remove error if added on change for source
        jQuery('#params_source').change(function() {
            if (jQuery(this).parent('div').hasClass('error')) {
                jQuery(this).parent('div').removeClass('error');
            };
        });

    });

    // process parameters when form is submitted
    jQuery(document).on('click', '.nxyt-settings-submit', function() {
        //console.log('clicked');
        let params = $("#nxyt-settings-form").serializeArray().map(function(v) { return [v.name, v.value]; });

        //console.log(params);

        for (let i = 0; i < params.length; i++) {
            let key = params[i][0];
            let value = params[i][1].replace(/ /g, '%20');
            key = key.replace('params[', '');
            key = key.replace(']', '');
            //console.log(key);

            if (((key in options) && options[key] !== value) || key === 'source') {
                paramsobj[key] = value;
                paramsArray.push(key + '=' + value);
            };
        };

        //console.log(paramsobj);
        if (paramsobj.source === '') {
            //console.log('empty');
            $('#params_source').parent('div').addClass('error');
            $('html,body').animate({
                scrollTop: 0
            }, 400);

        } else {
            //console.log(paramsobj);
            let paramtext = paramsArray.length > 0 ? ' ' + paramsArray.join(' ') : '';

            // trigger event to request the activation tag to be inserted
            insertTag(fromQueryString(window.location.search)['editor'], 'nxyt', paramtext);
        };
    });
});