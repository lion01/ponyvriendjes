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
 * @version			$Id: modules.php 21032 2011-03-29 16:38:31Z dextercowley $
 * @copyright		Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license			GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined( '_JEXEC' ) or die();

jimport( 'joomla.application.component.controlleradmin' );

/**
 * Modules list controller class.
 *
 * @package			Joomla.Administrator
 * @subpackage	com_modules
 * @since		1.6
 */
class AdvancedModulesControllerModules extends JControllerAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_MODULES';

	/**
	 * Method to clone an existing module.
	 *
	 * @since	1.6
	 */
	public function duplicate()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( JText::_( 'JINVALID_TOKEN' ) );

		// Initialise variables.
		$pks = JRequest::getVar( 'cid', array(), 'post', 'array' );
		JArrayHelper::toInteger( $pks );

		try {
			if ( empty( $pks ) ) {
				throw new Exception( JText::_( 'COM_MODULES_ERROR_NO_MODULES_SELECTED' ) );
			}
			$model = $this->getModel();
			$model->duplicate( $pks );
			$this->setMessage( JText::plural( 'COM_MODULES_N_MODULES_DUPLICATED', count( $pks ) ) );
		} catch ( Exception $e ) {
			JError::raiseWarning( 500, $e->getMessage() );
		}

		$this->setRedirect( 'index.php?option=com_advancedmodules&view=modules' );
	}

	/**
	 * Proxy for getModel.
	 *
	 * @since	1.6
	 */
	public function &getModel( $name = 'Module', $prefix = 'AdvancedModulesModel', $config = array() )
	{
		$model = parent::getModel( $name, $prefix, array( 'ignore_request' => true ) );
		return $model;
	}
}
