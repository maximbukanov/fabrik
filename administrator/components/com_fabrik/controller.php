<?php
/**
 * Main Fabrik administrator controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Controllers;

// No direct access
use Joomla\String\Inflector;
use Joomla\Utilities\ArrayHelper;
use \JText as JText;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik master display controller.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Controller extends \JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   12.1
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		// Get the application
		$app = $this->getApplication();

		// Get the document object.
		$document = \JFactory::getDocument();

		// Route tasks
		$viewFormat = $document->getType();
		list($viewName, $layoutName) = $this->viewLayout();

		$app->input->set('view', $viewName);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		$viewClass  = 'Fabrik\Admin\Views\\' . ucfirst($viewName) . '\\' . ucfirst($viewFormat);
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);

		$model = new $modelClass;
		$view  = new $viewClass($model, $paths);

		$ids      = $app->input->get('cid', array(), 'array');
		$id       = $app->input->getString('id', ArrayHelper::getValue($ids, 0));
		$listUrl  = $this->listUrl($viewName);
		$applyUrl = $this->applyUrl($viewName, $id);

		switch ($layoutName)
		{
			case 'add':
				$layoutName = 'edit';
				break;
			case 'edit':
				$model->set('id', $id);
				$model->checkout();
				break;
			case 'delete':
				$model->delete($ids);
				$this->app->redirect($listUrl);
				break;
			case 'apply':
				$this->save($model);
				$this->app->redirect($applyUrl);
				break;
			case 'save':
				$this->save($model);
				$this->app->redirect($listUrl);
				break;
			case 'save2new':
				$this->save($model);
				$this->app->redirect('index.php?option=com_fabrik&view=' . $viewName . '&layout=edit');
				break;
			case 'cancel':
				$model->storeFormState(array());
				$this->app->redirect($listUrl);
			case 'unpublish':
				$model->unpublish($ids);
				$msg = 'COM_FABRIK_' . strtoupper($viewName) . '_N_ITEMS_UNPUBLISHED';
				$this->app->enqueueMessage(JText::plural($msg, $ids));
				$this->app->redirect($listUrl);
				break;
			case 'publish':
				$model->publish($ids);
				$msg = 'COM_FABRIK_' . strtoupper($viewName) . '_N_ITEMS_PUBLISHED';
				$this->app->enqueueMessage(JText::plural($msg, $ids));
				$this->app->redirect($listUrl);
				break;
			case 'checkin':
				$model->set('id', $id);
				$model->checkin();
				$this->app->redirect($listUrl);
				break;
			case 'checkout':
				$model->set('id', $id);
				$model->checkout();
				$this->app->redirect($listUrl);
				break;
			case 'trash':
				$model->trash($ids);
				$this->app->redirect($listUrl);
				break;
			case 'copy':
				$model->copy($ids);
				$msg = 'COM_FABRIK_' . strtoupper($viewName) . 'N_ITEMS_COPIED';
				$app->enqueueMessage(JText::plural($msg, $ids));
				break;
			default:
				break;
		}
		$view->setLayout($layoutName);

		// Render our view.
		echo $view->render();

		return true;
	}

	/**
	 * Create the list redirect url
	 *
	 * @param   string $viewName View name
	 *
	 * @return string
	 */
	protected function listUrl($viewName)
	{
		if ($viewName === 'lizt')
		{
			$viewName = 'list';
		}

		$inflector = Inflector::getInstance();

		return 'index.php?option=com_fabrik&view=' . $inflector->toPlural($viewName);
	}

	/**
	 * Build apply url
	 *
	 * @param   string  $viewName
	 * @param   string  $id
	 *
	 * @return string
	 */
	protected function applyUrl($viewName, $id)
	{
		$url = 'index.php?option=com_fabrik&view=' . $viewName . '&task=' . $viewName . '.edit&id=' . $id;

		if ($viewName === 'group')
		{
			$data = $this->input->post->get('jform', array(), 'array');
			$id   = ArrayHelper::getValue($data, 'id');

			if (!is_null($id))
			{
				$url .= '&groupid=' . $id;
			}
		}

		return $url;
	}

	/**
	 * Get the view and layout name
	 *
	 * @return array (viewName, layoutName)
	 */
	protected function viewLayout()
	{
		$app  = $this->app;
		$task = $app->input->get('task');

		if (strstr($task, '.'))
		{
			list($viewName, $layoutName) = explode('.', $task);
		}
		else
		{
			$viewName   = $app->input->getWord('view', 'home');
			$layoutName = $app->input->getWord('layout', 'bootstrap');
		}

		return array($viewName, $layoutName);
	}

	/**
	 * Validate and save
	 *
	 * @param $model
	 *
	 * @return bool
	 */
	protected function save($model)
	{
		$data = $this->input->post->get('jform', array(), 'array');

		if ($model->validate($data))
		{
			$model->prepare($data);

			return $model->save($data);
		}
		else
		{
			$errors = $model->get('errors');

			foreach ($errors as $error)
			{
				$this->app->enqueueMessage($error->getMessage(), 'error');
			}
		}

		return false;
	}

}
