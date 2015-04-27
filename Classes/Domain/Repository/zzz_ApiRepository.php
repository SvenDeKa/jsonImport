<?php
namespace Z3\Z3Fasapi\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sven Külpmann <sven.kuelpmann@lenz-wie-fruehling.de>, Ziegelei3
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

/**
 *
 *
 * @package z3_event
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class ApiRepositoryOld extends \TYPO3\CMS\Extbase\Persistence\Repository {
	
	/**
	 * @var string 
	 */
	protected $useRepository;
	
	
	/**
	 * @var object 
	 */
	protected $objectManager;
	
	/**
	 * @var object 
	 */
	protected $configurationManager;
	
	/**
	 * persistenceManager
	 */
	protected $persistenceManager;
	/**
	 * @var object 
	 */
	protected $settings;
	
	
	/**
	 * __construct
	 *
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		
		$this->configurationManager = $this->objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
		
		$this->settings = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Z3Fasapi', 'Api'
		);	
		
		$this->persistenceManager = $this->objectManager->get('Tx_Extbase_Persistence_Manager');
	}
	
	
	public function importData($data){
		$importData = $this->decodeData($data, $this->settings['format']);
		
		foreach($importData as $node){
			$node = $this->processNode($node, $this->settings);	
		}
	}
	
	
	public function processNode($node){
		
		
		if(is_object($node)){
			$class = $this->settings['objects'][$node->name]['class'];
			if($class !== '' && $class !== NULL){
				$object = $this->objectMapping($node);
				if( $object !== NULL ){ 
					
					$objectRepository = $this->repositoryByModel($class);
					
					$findByIdentifier = 'findBy'.ucFirst($this->settings['objects'][$node->name]['sourceIdentifier']);
					$exists = $objectRepository->$findByIdentifier( $object->getUuid() );

					if($exists[0] === NULL) {
						$object = $this->setProperties($object, NULL, $node->name);
						$objectRepository->add($object);
					} else {
						
						$exists[0] = $this->setProperties($object, $exists[0], $node->name);
						$objectRepository->update($exists[0]);
					}
					
					$this->persistenceManager->persistAll();
				}
//				else{
//					\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($node->name, $class.' --> node');
//				}
			}
//			else{
//				print_r($node->name);
//			}
		}
//		else{
//			print_r($node->name.'<br>');
//		}
		
		
		if( is_array( $node->children)){
//			if($mapping->getNodeType($node) !== 'list'){
//				print_r('another check: containing arrays, this has to be a list');
//			}
			foreach($node->children as $childnode){
				$this->processNode($childnode);
			}
			
		}else if(is_object($node->children)){
//			if($mapping->getNodeType($node) !== 'item'){
//				print_r('another check: containing an object , this has to be an item');
//			}
			$this->processNode($node->children);
		}
		$this->nodeCount++ ;
		
	}
	
	/**
	 * Mapp the std_class-Object from import to the proper Model-Object as it is configured in settings.objects.[$node->name].class 
	 * and return the object mit the Values set via the Models  setters
	 * 
	 * @param type $node
	 * @return null
	 */
	private function objectMapping($node) {
		
		$class = $this->settings['objects'][$node->name]['class'];
		if($class !== '' && $class !== NULL){
			$object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class);

			foreach( $node as $attr => $val){
				
				if( strpos($attr, $this->settings['attributes']['pre']) !== FALSE ){
					$setter = 'set'. ucFirst( ltrim($attr, $this->settings['attributes']['pre'] ) );
					if ( method_exists( $object , $setter ) ){
//						\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($setter, 'setter exists');
						$object->$setter($val);
					}
//					else{
//						\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($setter, 'setter DOESNT exist');	
//					}
				}
			}

			return $object;
		}else{
			return NULL;
		}
		
	}
	
	
	
	/**
	 * Decode the data (json only for now)
	 * 
	 * @param mixed $data
	 * @param string $format
	 * @return type
	 */
	private function decodeData($data){
		if( $this->settings['format'] === 'json' ){
			$decodedData = json_decode($data);
			return $decodedData;
		}else{
			die('ERROR: NO SUPPORTED FORMAT CONFIGURED.');
		}
	}
	

	private function setProperties($newObject, $oldObject=NULL, $nodeName){
		
		if($oldObject !==NULL ){
			foreach( $this->settings['objects'][$nodeName]['attributes'] as $sourceAttr => $targetAttr ){
				$attr = ucFirst($targetAttr);
 				
				if(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($oldObject, $attr ) ) {
					$setter = 'set'.$attr;
					$getter = 'get'.$attr;
					$oldObject->$setter( $newObject->$getter() );
				}
			}
			return $oldObject;
		}else{
			$newObject->setPid($this->settings['objects'][$nodeName]['storagePid'] );
			return $newObject;
		}
		
		
	}

	/**
	* make Instance of the Repository by the given Model 
	* @param int $uid The uid
	* @param string $object The objectName
	* @return mixed Object from class of $object | NULL if not found
	*/
	private function repositoryByModel($object) {
		if (class_exists($object)) {
			$repositoryName = str_replace('Model','Repository',$object).'Repository';
			if (class_exists($repositoryName)) {
			//	$repository = $this->objectManager->get($repositoryName );
				$repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($repositoryName);
				return $repository;
			}
		}
		return NULL;
	}

}
//$repository->add($object)
//$repository->remove($object)
//$repository->replace($existingObject, $newObject)
//$repository->update($modifiedObject)
//$repository->findAll()
//$repository->countAll() // Since Extbase 1.1
//$repository->removeAll()
//$repository->createQuery()
//$repository->countByProperty($value) // Since Extbase 1.1
//$repository->findByProperty($value)
//$repository->findOneByProperty($value)

?>