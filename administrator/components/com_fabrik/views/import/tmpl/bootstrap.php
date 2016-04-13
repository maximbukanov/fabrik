<?php
/**
 * Admin Import Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\Text;

JHtml::_('behavior.tooltip');
Html::formvalidation();
$app = JFactory::getApplication();
$input = $app->input;

?>
<script type="text/javascript">
window.addEvent('domready', function () {
	(function($){

	    (['jform_inPutFormat1', 'jform_inPutFormat2']).each(function (l) {
	    	var options = {title: 'CSV', 'trigger': 'hover'};
		    var opt = document.getElement('label[for=' + l + ']');
		    if (typeOf(opt) !== 'null') {
			    var s = opt.getElement('small');
			    options.content = s.get('text');
			    s.destroy();
				$('label[for=' + l + ']').popover(options);
		    }
	    });

    })(jQuery);
});

</script>
<form enctype="multipart/form-data" action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

<div class="width-100 fltlft">
	<?php
	$id	= $input->getInt('listid', 0); // from list data view in admin
	$cid = $input->getVar('cid', array(0), 'array');// from list of lists checkbox selection
	$cid = ArrayHelper::toInteger($cid);
	if ($id === 0) :
		$id = $cid[0];
	endif;
	if (($id !== 0)) :
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('label')->from('#__{package}_lists')->where('id = ' . $id);
		$db->setQuery($query);
		$list = $db->loadResult();
	endif;
	$fieldsets = array('details');
	$fieldsets[] = $id === 0 ? 'creation' : 'append';
	$fieldsets[] = 'format';
	?>
		<input type="hidden" name="listid" value="<?php echo $id ;?>" />

	<?php
	foreach ($fieldsets as $n => $fieldset) :?>
	<fieldset class="form-horizontal">
		<?php
		if ($n == 0) :
			echo '<legend>' . Text::_('COM_FABRIK_IMPORT_CSV') . '</legend>';
		endif;
		foreach ($this->form->getFieldset($fieldset) as $this->field) :
			echo $this->loadTemplate('control_group');
		endforeach;
		?>
	</fieldset>
	<?php endforeach;?>
	<input type="hidden" name="drop_data" value="0" />
	<input type="hidden" name="overwrite" value="0" />
 	<input type="hidden" name="task" value="" />
  	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
	</div>
</form>
