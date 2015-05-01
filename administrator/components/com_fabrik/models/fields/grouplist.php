<?php
/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('groupedlist');

/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldGroupList extends JFormFieldGroupedList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Grouplist';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	protected function getGroups()
	{
		$this->app = JFactory::getApplication();
		if ($this->value == '')
		{
			$this->value = $this->app->getUserStateFromRequest('com_fabrik.elements.filter.group', 'filter_groupId', $this->value);
		}

		// Initialize variables.
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);

		$query->select('g.id AS value, g.name AS text, f.label AS form');
		$query->from('#__fabrik_groups AS g');
		$query->where('g.published <> -2')
		->join('INNER', '#__fabrik_formgroup AS fg ON fg.group_id = g.id')
		->join('INNER', '#__fabrik_forms AS f on fg.form_id = f.id');
		$query->order('f.label, g.name');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();
		$groups = array();

		// Add please select
		$sel = new stdClass;
		$sel->value = '';
		$sel->form = '';
		$sel->text = FText::_('COM_FABRIK_PLEASE_SELECT');
		array_unshift($options, $sel);

		foreach ($options as $option)
		{
			if (!array_key_exists($option->form, $groups))
			{
				$groups[$option->form] = array();
			}

			$groups[$option->form][] = $option;
		}

		return $groups;
	}
}
