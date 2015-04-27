<?php
namespace Z3\Z3Fasapi\Utility;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Auth
 *
 * @author sven.kuelpmann
 */
class Auth {
	
	
	
	public function authentificate($gp) {
		
		
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
		
		$settings = $configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Z3Fasapi', 'Api'
		);	
		
		if( $settings['key'] === ''){
			return 'FAS-API-ERROR. AUTH: no api-key configured. please configure plugin.tx_z3fasapi.settings.key properly' ;
		}
		if( !array_key_exists('key', $gp) ){
			return 'FAS-API-ERROR. AUTH: no api-key given';
		}
		if( $gp['key'] === '' ){
			return 'FAS-API-ERROR. AUTH: api-key empty' ;
		}
		if( $gp['key'] !== $settings['key']){
			return 'FAS-API-ERROR. AUTH: incorrect api-key' ;
		}
		
		return TRUE;
	}
	
	
}
