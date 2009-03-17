<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// TODO these statements become obsolete with the new autoloader -> remove them

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Configuration/TX_EXTMVC_Configuration_Manager.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_AbstractController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_ActionController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Arguments.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Argument.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/View/TX_EXTMVC_View_AbstractView.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_Session.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper.php');

/**
 * Creates a request an dispatches it to the controller which was specified by TS Setup, Flexform,
 * or Extension Configuration (ExtConf), and returns the content to the v4 framework.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Dispatcher {

	/**
	 * @var TX_EXTMVC_Configuration_Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * @var TX_EXTMVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var ArrayObject The raw GET parameters
	 */
	protected $getParameters;

	/**
	 * @var ArrayObject The raw POST parameters
	 */
	protected $postParameters;

	/**
	 * @var array An array of registered classes (class files with path)
	 */
	protected $registeredClassNames;

	/**
	 * Constructs this dispatcher
	 *
	 */
	public function __construct() {
		spl_autoload_register(array($this, 'autoLoadClasses'));
	}

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param String $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @uses t3lib_div::_GET()
	 * @uses t3lib_div::makeInstance()
	 * @uses t3lib_div::GParrayMerged()
	 * @uses t3lib_div::getIndpEnv()
	 * @return String $content The processed content
	 */
	public function dispatch($content, $configuration) {

		// TODO Remove debug statement
		// $start_time = microtime(TRUE);

		$parameters = t3lib_div::_GET();
		$extensionKey = isset($parameters['extension']) ? stripslashes($parameters['extension']) : $configuration['extension'];
		$controllerName = isset($parameters['controller']) ? stripslashes($parameters['controller']) : $configuration['controller'];
		$actionName = isset($parameters['action']) ? stripslashes($parameters['action']) : $configuration['action'];

		$request = t3lib_div::makeInstance('TX_EXTMVC_Web_Request');
		$request->setControllerExtensionKey($extensionKey);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);

		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);

		if (!$controller instanceof TX_EXTMVC_Controller_AbstractController) throw new TX_EXTMVC_Exception_InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);

		if (!$controller->isCachableAction($actionName) && $this->cObj->getUserObjectType() === tslib_cObj::OBJECTTYPE_USER) {
			// FIXME Caching does nort work because it's by default a USER object, so the dispatcher is never called
			// SK: does caching already work?
			$this->cObj->convertToUserIntObject();
			return $content;
		}

		$arguments = t3lib_div::makeInstance('TX_EXTMVC_Controller_Arguments');
		// SK: strtolower($extensionKey) is wrong I think, as all underscores need to be removed as well.
		// SK: Example: tt_news -> tx_ttnews
		foreach (t3lib_div::GParrayMerged('tx_' . strtolower($extensionKey)) as $key => $value) {
			// SK: This argument is passed attached to the Request - so do NOT use  TX_EXTMVC_Controller_Argument or TX_EXTMVC_Controller_Arguments here.
			// SK: Instead, just add an argument by using $request->addArgument('key', 'value');
			// SK: The real argument mapping and validation then happens in the AbstractController.
			$argument = new TX_EXTMVC_Controller_Argument($key, 'Raw');
			$argument->setValue($value);
			$arguments->addArgument($argument);
		}
		// SK: as shown above, this needs to be changed
		$request->setArguments($arguments);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$response = t3lib_div::makeInstance('TX_EXTMVC_Web_Response');

		$configurationSources = array();
		$configurationSources[] = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Source_TS');
		if (!empty($this->cObj->data['pi_flexform'])) {
			$configurationSource = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Source_FlexForm');
			$configurationSource->setFlexFormContent($this->cObj->data['pi_flexform']);
			$configurationSources[] = $configurationSource;
		}
		$configurationManager = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Manager', $configurationSources);
		$configurationManager->loadGlobalSettings($extensionKey);
		$configurationManager = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Manager');
		$settings = $configurationManager->getSettings($extensionKey);
		$controller->injectSettings($settings);

		$session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		try {
			$controller->processRequest($request, $response);
		} catch (TX_EXTMVC_Exception_StopAction $ignoredException) {
		}
		$session->commit();
		$session->clear();

		$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionKey()] = implode("\n", $response->getAdditionalHeaderTags());

		// TODO Remove debug statements
		// $end_time = microtime(TRUE);
		// debug($end_time - $start_time, -1);

		// SK: Handle $response->getStatus()
		// SK: Call sendHeaders() on the response
		return $response->getContent();
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * an extension.
	 *
	 * @param string $className: Name of the class/interface to load
	 * @uses t3lib_extMgm::extPath()
	 * @return void
	 */
	// TODO Remove autoloader as soon as we do not need it anymore
	protected function autoLoadClasses($className) {
		if (empty($this->registeredClassNames[$className])) {
			$classNameParts = explode('_', $className);
			if ($classNameParts[0] === 'ux') {
				array_shift($classNameParts);
			}
			if (count($classNameParts) > 2 && $classNameParts[0] === 'TX') {
				$classFilePathAndName = t3lib_extMgm::extPath(strtolower($classNameParts[1])) . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= implode('_', $classNameParts) . '.php';
			}
			if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
				require_once($classFilePathAndName);
				$this->registeredClassNames[$className] = $classFilePathAndName;
			}
		}
	}

}
?>