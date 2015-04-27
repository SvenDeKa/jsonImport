<?php
	/** *************************************************************
	*
	* Extbase Dispatcher for API Calls TYPO3 6.1 namespaces
	*
	* IMPORTANT Use this script only in Extensions with namespaces
	* based on Dispatcher for AjaxCalls by Klaus Heuer <klaus.heuer@t3-developer.com>
	*
	* 2014 Sven Külpmann <sven.kuelpmann@lenz-wie-fruehling.de>, lwf / z3
	* 
	* This script is part of the TYPO3 project. The TYPO3 project is
	* free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* The GNU General Public License can be found at
	* http://www.gnu.org/copyleft/gpl.html.
	*
	* This script is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* This copyright notice MUST APPEAR in all copies of the script!
	* ************************************************************* */


	/**
	 * variables to handling the request
	 */
	$vendorName = 'Z3';
	$extensionName = 'Z3Fasapi';
	$pluginName = 'Api';
	$controllerName = 'Api';
	$controllerActionName = 'apiCall';
	
	/**
	 * Gets the Ajax Call Parameters
	 */
	$api = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP( 'api' );	

	/**
	 *  bootstrap the extension
	 */

	// TSFE
	$TSFE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', $TYPO3_CONF_VARS, 0, 0);
	\TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage(); 

	$TSFE->initFEuser();
	$TSFE->set_no_cache();
//	$TSFE->checkAlternativCoreMethods();
	$TSFE->checkAlternativeIdMethods();
	$TSFE->determineId();
	$TSFE->initTemplate();
	$TSFE->getConfigArray();
	
	$cmsbootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();
	$cmsbootstrap->loadConfigurationAndInitialize();
	$cmsbootstrap->loadExtensionTables();

	$TSFE->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
	$TSFE->settingLanguage();
	$TSFE->settingLocale(); 

	/**
	* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager
	*/
	$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');

	/**
	* Initialize Extbase bootstap
	*/
	$bootstrapConf['extensionName'] = $extensionName;
	$bootstrapConf['pluginName'] = $pluginName;
	$bootstrapConf['vendorName'] = $vendorName;
	$bootstrap = new TYPO3\CMS\Extbase\Core\Bootstrap();
	$bootstrap->initialize($bootstrapConf);
	$bootstrap->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');

	/**
	* Build the request
	*/
	$request = $objectManager->get( 'TYPO3\CMS\Extbase\Mvc\Request' );
	
	$request->setControllerVendorName( $vendorName );
	$request->setcontrollerExtensionName( $extensionName );
	$request->setPluginName( $pluginName );
	$request->setControllerName( $controllerName );
	$request->setControllerActionName( $controllerActionName );
	$request->setArguments( $api );

	
	/**
	 * Authentification
	 */
	$authResult = \Z3\Z3Fasapi\Utility\Auth::authentificate($api);
	if( $authResult === TRUE){
		
		if( !array_key_exists('data', $api) ){
			die( $extensionName.'-ERROR. DATA: no data-parameter given' );
		}
		if( $api['data'] === '' ){
			die( $extensionName.'-ERROR. DATA: data-parameter empty' );
		}
		// @ToDo: for Future Configurability make this check depending on plugin.tx_z3fasapi.settings.format=json
		if( !\Z3\Z3Fasapi\Utility\Validator::isJson($api['data']) ){
			die( $extensionName.'-ERROR. DATA: no valid JSON given in Data-array' );
		}
		
		$response = $objectManager->create( 'TYPO3\CMS\Extbase\Mvc\ResponseInterface' );

		$dispatcher = $objectManager->get( 'TYPO3\CMS\Extbase\Mvc\Dispatcher' );
		$dispatcher->dispatch( $request, $response );

		echo $response->getContent();
				
		return;
		
	} else {
		die( $authResult );
	}
	
?>