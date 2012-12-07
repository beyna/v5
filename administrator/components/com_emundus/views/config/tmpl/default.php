<?php
/**
 * @package   	eMundus
 * @copyright 	Copyright � 2009-2012 Benjamin Rivalland. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * eMundus is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('RESTRICTED');
?>

<form action="index.php" method="post" name="adminForm">
    <div id="emundus">
            <div>
                <?php foreach($this->params->getGroups() as $group => $num): ?>
                	<fieldset class="adminform panelform">
						<legend><?php echo JText::_('CONFIG_'.strtoupper($group)); ?></legend>
						<?php 
							echo $this->params->render('params', $group)
	        			?>
                	</fieldset>
				<?php endforeach;?>
            </div>
    </div>
    <input type="hidden" name="option" value="com_emundus" />
    <input type="hidden" name="view" value="config" />
    <input type="hidden" name="task" value="" />
    <?php echo JHTML::_( 'form.token' ); ?>
</form>