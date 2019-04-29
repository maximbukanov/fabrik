<?php
/**
 * Renders a list of tables, either fabrik lists, or db tables
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');


use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Fabrik\Component\Fabrik\Administrator\Helper\ElementHelper;

FormHelper::loadFieldClass('list');

/**
 * Renders a list of tables, either fabrik lists, or db tables
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class TablesField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'tables';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Tables';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$connectionDd   = $this->element['observe'];
		$connectionName = 'connection_id';
		$connId         = (int) $this->form->getValue($connectionName);
		$options        = array();
		$db             = Worker::getDbo(true);

		// DB join element observes 'params___join_conn_id'
		if (strstr($connectionDd, 'params_') && $connId === 0)
		{
			$connectionName = str_replace('params_', 'params.', $connectionDd);
			$connId         = (int) $this->form->getValue($connectionName);
		}

		if ($connectionDd == '')
		{
			// We are not monitoring a connection drop down so load in all tables
			$query = "SHOW TABLES";
			$db->setQuery($query);
			$items     = $db->loadColumn();
			$options[] = HTMLHelper::_('select.option', null, null);

			foreach ($items as $l)
			{
				$options[] = HTMLHelper::_('select.option', $l, $l);
			}
		}
		else
		{
			// Delay for the connection to trigger an update via js.
		}

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		$app          = Factory::getApplication();
		$format       = $app->input->get('format', 'html');
		$connectionDd = $this->element['observe'];

		if ((int) $this->form->getValue('id') != 0 && $this->element['readonlyonedit'])
		{
			return '<input type="text" value="' . $this->value . '" class="readonly" name="' . $this->name . '" readonly="true" />';
		}

		$c              = ElementHelper::getRepeatCounter($this);
		$readOnlyOnEdit = $this->element['readonlyonedit'];

		if ($connectionDd != '')
		{
			$connectionDd   = ($c === false) ? $connectionDd : $connectionDd . '-' . $c;
			$opts           = new \stdClass;
			$opts->livesite = COM_FABRIK_LIVESITE;
			$opts->conn     = 'jform_' . $connectionDd;
			$opts->value    = $this->value;
			$opts           = json_encode($opts);
			$script[]       = "FabrikAdmin.model.fields.fabriktable['$this->id'] = new tablesElement('$this->id', $opts);\n";
			$src            = array(
				'Fabrik'    => 'media/com_fabrik/js/fabrik.js',
				'Namespace' => 'administrator/components/com_fabrik/tmpl/namespace.js',
				'Tables'    => 'administrator/components/com_fabrik/src/Field/tables.js'
			);
			Html::script($src, implode("\n", $script));

			$this->value = '';
		}

		$html = parent::getInput();
		$html .= "<img style='margin-left:10px;display:none' id='" . $this->id . "_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='"
			. Text::_('LOADING') . "' />";
		Html::framework();
		Html::iniRequireJS();

		return $html;
	}
}