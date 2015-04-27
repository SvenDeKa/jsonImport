<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// $TYPO3_CONF_VARS['FE']['eID_include']['fasapi'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('z3_fasapi').'Classes/Utility/apiDispatcher.php';
$TYPO3_CONF_VARS['FE']['eID_include']['fasapi'] = 'EXT:z3_fasapi/Classes/Utility/ApiDispatcher.php';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Z3.' . $_EXTKEY,
	'Api',
	array(
		'Api' => 'test, apiCall, create, edit, delete',
		
	),
	// non-cacheable actions
	array(
		'Api' => 'apiCall, create, edit, delete, test',
		
	)
);

$TYPO3_CONF_VARS['SYS']['debugExceptionHandler'] = '\Z3\Z3Fasapi\Utility\ExceptionHandler';
$TYPO3_CONF_VARS['SYS']['productionExceptionHandler'] = '\Z3\Z3Fasapi\Utility\ExceptionHandler';