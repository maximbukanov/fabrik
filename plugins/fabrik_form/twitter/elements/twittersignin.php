<?php
/**
 * Post content to twitter: JForm Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.twitter
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 *
 * NOTE - as we can only have one addpath file specified for the params group, this file has to be located
 * in the main ./administrator/components/com_fabrik/models/fields folder.  So until we work out how to do the install
 * XML magic to relocate this file on install, we have simply made a copy of it in the admin location in SVN.
 * If you edit the copy in the plugin folder, please be sure to also modify the copy in the admin folder.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;

/**
 * Renders a twitter sign in button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.twitter
 * @since       3.0
 */

class JFormFieldTwittersignin extends JFormField
{
	/**
	 * Element name
	 *
	 * @var	string
	 */
	protected $name = 'Twittersignin';

	/**
	 * Get the input
	 *
	 * @return string
	 */

	protected function getInput()
	{
		$iframeid = $this->id . '_iframe';
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('id', array(0), 'array');
		$cid = ArrayHelper::getValue($cid, 0);

		// $$$ hugh - when creating a new form, no 'cid' ... not sure what to do, so just set it to 0.  Should
		// prolly just return something like 'available after save' ?

		$c = isset($this->form->repeatCounter) ? (int) $this->form->repeatCounter : 0;


		$href = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&view=pluginAjax&plugin=twitter';
		$href .= '&g=form&method=authenticateAdmin&tmpl=component&formid=' . $cid . '&repeatCounter=' . $c;

		$clearjs = '$(\'jform_params_twitter_oauth_token-' . $c . '\').value = \'\';';
		$clearjs .= '$(\'jform_params_twitter_oauth_token_secret-' . $c . '\').value = \'\';';
		$clearjs .= '$(\'jform_params_twitter_oauth_user-' . $c . '\').value = \'\';';
		$clearjs .= "return false;";

		$src = COM_FABRIK_LIVESITE . 'components/com_fabrik/libs/abraham-twitteroauth/images/lighter.png';
		$winOpts = 'width=800,height=460,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes';
		$js = "window.open('$href', 'twitterwins', '" . $winOpts . "');return false;";
		$str = '<a href="#" onclick="' . $js . '">';
		$str .= '<img src="' . $src . '" alt="Sign in with Twitter"/></a>';
		$str .= " | <button class=\"button btn\" href=\"#\" onclick=\"$clearjs\">";
		$str .= FText::_('PLG_FORM_TWITTER_CLEAR_CREDENTIALS') . "</button><br/>";
		$str .= "<br /><input type=\"hidden\" readonly=\"readonly\" name=\""
			. $this->name . "\" id=\"" . $this->id . "\" value=\"" . $this->value . "\" />";

		return $str;
	}
}
