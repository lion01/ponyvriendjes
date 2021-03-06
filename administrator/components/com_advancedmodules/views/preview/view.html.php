<?php
/**
 * @package			Advanced Module Manager
 * @version			2.2.16
 *
 * @author			Peter van Westen <peter@nonumber.nl>
 * @link			http://www.nonumber.nl
 * @copyright		Copyright © 2011 NoNumber! All Rights Reserved
 * @license			http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**
 * @version			$Id: view.html.php 21097 2011-04-07 15:38:03Z dextercowley $
 * @package			Joomla.Administrator
 * @subpackage	com_modules
 * @copyright		Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license			GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();

jimport( 'joomla.application.component.view' );

/**
 * HTML View class for the Modules component
 *
 * @static
 * @package			Joomla.Administrator
 * @subpackage	com_modules
 * @since 1.6
 */
class AdvancedModulesViewPreview extends JView
{
	function display( $tpl = null )
	{
		$editor = JFactory::getEditor();

		$this->assignRef( 'editor', $editor );

		parent::display( $tpl );
	}
}