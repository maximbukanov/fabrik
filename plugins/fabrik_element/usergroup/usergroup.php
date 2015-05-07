<?php
/**
 * Plugin element to render multi select user group list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use Fabrik\Plugins\ElementList as ElementList;

/**
 * Plugin element to render multi select user group list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.usergroup
 * @since       3.5
 */
class PlgFabrik_ElementUsergroup extends ElementList
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Array of id, label's queried from #__usergroups
	 *
	 * @var array
	 */
	protected $allOpts = null;

	/**
	 * Does the element contain sub elements e.g checkboxes radiobuttons
	 *
	 * @var bool
	 */
	public $hasSubElements = false;

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$params = $this->getParams();
		$formModel = $this->getFormModel();
		$userEl = $formModel->getElement($params->get('user_element'), true);
		$thisUser = false;
		
		if ($userEl)
		{
			$data = $formModel->getData();
			$userId = ArrayHelper::getValue($data, $userEl->getFullName(true, false) . '_raw', 0);

			// Failed validation
			if (is_array($userId))
			{
				$userId = ArrayHelper::getValue($userId, 0);
			}

			$thisUser = !empty($userId) ? JFactory::getUser($userId) : false;
		}

		$selected = $this->getValue($data, $repeatCounter);

		if (is_string($selected))
		{
			$selected = json_decode($selected);
		}

		if (!$this->isEditable())
		{
			if (!empty($thisUser))
			{
				$selected = $thisUser->groups;
			}
			// Get the titles for the user groups.
			if (count($selected) > 0)
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('title'));
				$query->from($db->quoteName('#__usergroups'));
				$query->where($db->quoteName('id') . ' IN ( ' . implode(' , ', $selected) . ')');
				$db->setQuery($query);
				$selected = $db->loadColumn();
			}
		}

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->isEditable = $this->isEditable();
		$layoutData->input = JHtml::_('access.usergroups', $name, $selected, true);
		$layoutData->selected = is_array($selected) ? implode(', ', $selected) : '';

		return $layout->render($layoutData);
	}

	/**
	 * Get sub option values
	 *
	 * @param   array  $data  Form data. If submitting a form, we want to use that form's data and not
	 *                        re-query the form Model for its data as with multiple plugins of the same type
	 *                        this was getting the plugin params out of sync.
	 *
	 * @return  array
	 */
	protected function getSubOptionValues($data = array())
	{
		$opts = $this->allOpts();
		$return = array();

		foreach ($opts as $opt)
		{
			$return[] = $opt->id;
		}

		return $return;
	}

	/**
	 * Get sub option labels
	 *
	 * @param   array  $data  Form data. If submitting a form, we want to use that form's data and not
	 *                        re-query the form Model for its data as with multiple plugins of the same type
	 *                        this was getting the plugin params out of sync.
	 *
	 * @return  array
	 */
	protected function getSubOptionLabels($data = array())
	{
		$opts = $this->allOpts();
		$return = array();

		foreach ($opts as $opt)
		{
			$return[] = $opt->title;
		}

		return $return;
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns only data found in the table you are filtering on
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array	Filter value and labels
	 */

	protected function filterValueList_Exact($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$listModel = $this->getListModel();
		$elName2 = $this->getFullName(false, false, false);
		$tmpIds = $listModel->getColumnData($elName2);
		$ids = array();

		foreach ($tmpIds as $tmpId)
		{
			$tmpId = Worker::JSONtoData($tmpId, true);
			$ids = array_merge($ids, $tmpId);
		}

		$ids = array_unique($ids);
		$opts = $this->allOpts();
		$return = array();

		foreach ($ids as $id)
		{
			if (array_key_exists($id, $opts))
			{
				// 3.0 its an array - 3.1 its an object
				$opt = new stdClass;
				$opt->value = $id;
				$opt->text = $opts[$id]->title;
				$return[] = $opt;
			}
		}

		return $return;
	}

	/**
	 * Get all user groups (id/title)
	 *
	 * @return  array
	 */
	private function allOpts()
	{
		if (!isset($this->allOpts))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, title');
			$query->from($db->quoteName('#__usergroups'));
			$db->setQuery($query);
			$this->allOpts = $db->loadObjectList('id');
		}

		return $this->allOpts;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$opts = parent::getElementJSOptions($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);

		return array('FbUsergroup', $id, $opts);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = parent::getValue($data, $repeatCounter, $opts);
		$value = Worker::JSONtoData($value);

		if (is_string($value))
		{
			// New record or failed validation
			$value = trim($value);
			$value = $value === '' ? array() : explode(',', $value);
		}

		return $value;
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  Form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		if (!isset($this->_default))
		{
			$params = $this->getParams();

			if ($params->get('default_to_current_user_group', 1))
			{
				$this->_default = $this->user->get('groups');
				$this->_default = array_values($this->_default);
				$this->_default = json_encode($this->_default);
			}
			else
			{
				$this->_default = json_encode(array());
			}
		}

		return $this->_default;
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns all possible options
	 *
	 * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  Table name to use - defaults to element's current table
	 * @param   string  $label      Field to use, defaults to element name
	 * @param   string  $id         Field to use, defaults to element name
	 * @param   bool    $incjoin    Include join
	 *
	 * @return  array	Filter value and labels
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, title');
		$query->from($db->quoteName('#__usergroups'));
		$db->setQuery($query);
		$selected = $db->loadObjectList();

		for ($i = 0; $i < count($selected); $i++)
		{
			$return[] = JHTML::_('select.option', $selected[$i]->id, $selected[$i]->title);
		}

		return $return;
	}
}
