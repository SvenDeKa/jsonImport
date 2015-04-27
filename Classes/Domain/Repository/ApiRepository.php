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
class ApiRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {
	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
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
	 * @var string 
	 */
	protected $useRepository;
	
	/**
	 * @var array 
	 */
	protected $processedChildren;
	
	
	/**
	 * @var object 
	 */
	protected $settings;
	
	/**
	 * a datetime... useful for crdate, etc.
	 */
	protected $currentRunDatetime;
	
	
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
	
	/**
	 * @ToDo: when we have a status-object, then return this instead of a string.
	 */
	public function importData($data){
		$this->currentRunDatetime = new \DateTime();
		
		$importData = $this->decodeData($data, $this->settings['format']);
		
		if( is_object($importData) ){
			$processed[] = $this->processNode($importData);
		}else{
			foreach($importData as $node){
				if( is_array( $node ) || is_object($node) ){
					$processed[] = $this->processNode($node);	
				}else{
					throw new \Exception('JSON Node '.$node->name.' was of unrecognized TYPE ('.gettype($node).')');
				}
			}
		}
		// ugly as hell, but this is was the best way to examine the old vs new data in the case of the initial project
		$this->deleteOldEntries($data);
		
		return 'SUCCESS';
	}
	
	/**
	 * processing a single node
	 */
	public function processNode($node, $parentnode = NULL){		
		
		if( is_array($node->children) && !$this->settings['objects'][rtrim($node->name,'s')] && $this->settings['objects'][$node->name]['passthrough'] != 1){
			throw new \Exception('Nodes Children were not configured via Typoscript (pls configure \'plugin.plugin.tx_z3fasapi.'.rtrim ($node->name,'s').'\')');
		}
		
		if( is_array( $node->children)){
			foreach($node->children as $childnode){
				if( $this->settings['objects'][$childnode->name]['holding'] !== NULL){
					foreach ($childnode->children as $innerchildnode){
						$processedChildren[$this->settings['objects'][$childnode->name]['holding']][] = $this->processNode($innerchildnode, $node);
					}
				}else{
					$processedChildren[] = $this->processNode($childnode, $node);					
				}
			}
		}
		
		if(is_object($node) && $this->settings['objects'][$node->name]['passthrough'] != 1){
			$class = $this->settings['objects'][$node->name]['class'];
			if($class !== '' && $class !== NULL){

				$objectRepository = $this->getRepositoryByModel($class);
				$identifier = $this->settings['attributes']['pre'].$this->settings['objects'][$node->name]['sourceIdentifier'];
				$findByIdentifier = 'findBy'.ucFirst($this->settings['objects'][$node->name]['targetIdentifier']);

				$exists = $objectRepository->$findByIdentifier( $node->$identifier );

				if($exists[0] === NULL) {
					$currentObject = $this->setObjectProperties($node, NULL, $processedChildren);
				}else{
					$currentObject = $this->setObjectProperties($node, $exists[0], $processedChildren);
				}
				// build relatedObjects from same node
//				if( is_array($this->settings['objects'][$node->name]['parallelObjects'])) {
				foreach( $this->settings['objects'][$node->name]['parallelObjects'] as $property => $parallelObjectSettings ) {
					$currentObject = $this->buildParallelObject($node, $currentObject, $this->settings['objects'][$node->name], $property);
				}
				
				// object already existing?.
				if( $currentObject->_isNew() ){
					$objectRepository->add($currentObject);
//				we need to force the object to be written, so the timestamp gets updated. @ToDo: slicker way of deleting needed, so this can be deprecated
//				}else if( $currentNode->_isDirty() ){
//					$objectRepository->update($currentNode);
				} else {
					$objectRepository->update($currentObject);
				}
				

				$this->persistenceManager->persistAll();
//
				if($processedChildren !== NULL){
					$currentObject = $this->setObjectRelations($node, $currentObject, $processedChildren);
				\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump( $currentObject, 'after setting relations line 172' );
//					$objectRepository->update($currentObject);
//					$this->persistenceManager->persistAll();
				}
//				
//				\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump( $currentObject, $class.'Object persistence' );
				
			}
			
			$this->nodeCount++ ;
		}
		
		
		return $currentObject;
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
	
 
	/**
	 * 
	 * @param type $node
	 * @param type $object
	 * @param type $nodeName
	 * @return type
	 */
	private function setObjectProperties($node, $object=NULL){
		// @ToDo: CHECK noEditIfIdentifierOnly. if so -> passthrough
		$ident = $this->settings['objects'][$node->name]['sourceIdentifier'];
		if( $object !== NULL/* && $this->settings['objects'][$node->name]['noEditIfIdentifierOnly'] == 1 */ && count((array)$node) == 2 /*&& $node->$ident != '' && $node->name != ''*/){
			return $object;
		}
		
		$class = $this->settings['objects'][$node->name]['class'];
		if($object===NULL && $class !== '' && $class !== NULL){
			$object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class); // why did i rebuild this?? @ToDo find the reason!
			$object->setPid($this->settings['objects'][$node->name]['storagePid'] );
		}
		
		foreach( $this->settings['objects'][$node->name]['attributes'] as $targetAttr => $sourceAttr){

			$attr = ucFirst($targetAttr);
			
			if(!is_array( $sourceAttr) ) {
				$nodeAttr = $this->settings['attributes']['pre'].$sourceAttr;
				if(\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($object, $attr ) ) {
					$setter = 'set'.$attr;
					$object->$setter( $node->$nodeAttr );
				}
				
			}
			
		}
		return $object;
	}
	
	/**
	 * building the relaytion on a non persisted Object is not health. That's why I moved this out of the preceeding function... 
	 */
	private function setObjectRelations($node, $object=NULL, $children=NULL){
		
		foreach( $this->settings['objects'][$node->name]['attributes'] as $targetAttr => $sourceAttr){

			
			if(is_array( $sourceAttr) ) {
				
				$relatedObject = NULL;
				/**
				 *  Relations: 
				 */
				// intermetiate
				if($sourceAttr['type'] === 'intermediate' && is_array( $children[ $sourceAttr['relatedObject'] ]) ){
					
//					if($object !==  NULL){
					if($object->getUid() > 0){
						$relatedObject = $this->buildIntermediateRelation($sourceAttr, $children[$sourceAttr['relatedObject']], $object->getUid());
					}else{
//						$uid = NULL;
						throw new \Exception('ERROR: ' . $object . ' is not persisted yet');
					}
				}
				// n1
				if($sourceAttr['type'] === 'n1'){
					$relatedObject = $this->buildManyToOneRelation($node, $sourceAttr);
				}
				// 1n
				if( is_array( $children[ $sourceAttr['relatedObject'] ]) && $sourceAttr['type'] === '1n' ){
					$relatedObject = $children[$sourceAttr['relatedObject']];
				}
				// nm	-	not yet implemented
//				if($sourceAttr['type'] = 'nm'){
//					$relatedObject = $this->buildManyToManyRelation($node, $sourceAttr);
//				}
				
				//add the related object
				if($relatedObject !== NULL && is_object($relatedObject) ) {
					$add = 'add'.ucfirst( rtrim( $targetAttr, 's' ) );
					$object->$add($relatedObject);
				}
				else if(is_array($relatedObject)){
					\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($object,'object to insert the relations into');
					foreach($relatedObject as $index => $childObject){
						if($childObject !== NULL){
							$add = 'add'.ucfirst( rtrim( $targetAttr, 's' ) );
							\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($childObject->_isDirty(), 'child is dirty/modified?');
							\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($childObject->_isNew(), 'child is new?');
							\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($childObject, 'childObject index: '.$index.' - to be added  in: '.rtrim( $targetAttr, 's' ));
							$object->$add($childObject);
						}
					}
				}
				
			}	
		}
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($object, 'OBJECT AFTER being processed');
		return $object;
	}
	
	/**
	 * for that funky case, when we need to write properties from one Object into a different one aside from the original. -with proper data incoming this should be a rare case.
	 */
	private function buildParallelObject($node,$parentObject, $parentObjectSettings, $currentProperty){
		
		$currentObjectSettings = $parentObjectSettings['parallelObjects'][$currentProperty];

		$class = $currentObjectSettings['class'];
		if($class !== '' && $class !== NULL){

			$objectRepository = $this->getRepositoryByModel($class);
			$identifier = $this->settings['attributes']['pre'].$currentObjectSettings['sourceIdentifier'];
			$findByIdentifier = 'findBy'.ucFirst($currentObjectSettings['targetIdentifier']);

			$exists = $objectRepository->$findByIdentifier( $node->$identifier );

			if($exists[0] === NULL) {
//				$currentObject = $this->objectManager->get($class);
				$currentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class);
			}else{
				$currentObject = $exists[0];
			}
			
			foreach($currentObjectSettings['attributes'] as $attribute => $attributeSetting){
				$setter = 'set'.ucfirst($attribute);
				$currentObject->$setter($this->propertyValue($node, $attributeSetting));
			}
			// build relatedObjects from same node
			foreach($currentObjectSettings['parallelObjects'] as $property => $parallelObjectSettings ) {
				$currentObject = $this->buildParallelObject($node,$currentObject, $currentObjectSettings, $property);
			}
		}
		$objectRepository->add($currentObject);
//		$this->persistenceManager->persistAll();
		$add = 'add'.$currentProperty;
//		if($currentObjectSettings['type'] == '1n' || $currentObjectSettings['type'] == 'nm'){
//			$adder .= 's';
//		}
		$parentObject->$add($currentObject);

		return $parentObject;
	}
	
	/**
	 * build  the intermediate-Relation. works for now. @ToDo: maybe simpler than this?
	 * @param type $attr
	 * @param type $sourceAttr
	 * @return null
	 */
	private function buildIntermediateRelation($sourceAttr, $children, $localUid){
		
		$sourceIdentifier = $this->settings['attributes']['pre'].$sourceAttr['sourceField'];
		
		foreach ( $children as $child ) {
			// add the intermediate Object if there is one
			if($child !== NULL && !$this->intermediateExists($child, $sourceAttr, $localUid )){
			
				$intermediateRepository = $this->getRepositoryByModel($this->settings['objects'][ $sourceAttr['intermediateObject'] ]['class'] );

				$intermediateObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( $this->settings['objects'][ $sourceAttr['intermediateObject'] ]['class'] ); // why did I rebuild this? @ToDo: find out why?
//				$intermediateObject = $this->objectManager->get( $this->settings['objects'][ $sourceAttr['intermediateObject'] ]['class'] );
				$intermediateObject->setPid( $this->settings['objects'][ $sourceAttr['intermediateObject'] ]['storagePid']);
				
				$add = 'add'.ucfirst($sourceAttr['foreignField']);
				$intermediateObject->$add($child);
				$intermediateRepository->add($intermediateObject);
				$this->persistenceManager->persistAll();
			}
		}
		return $intermediateObject;
	}
	
	/**
	 * check if there already is an intermediate-relation between the objects. this should become nicer -a query per relation ist not very performant but the intermediate-object is not a solid core function so what else to do...
	 * 
	 * @param type $child
	 * @param type $sourceAttr
	 * @param type $localUid
	 * @return boolean
	 */
	
	private function intermediateExists($child, $sourceAttr, $localUid){
		
		$sourceIdentifier = $this->settings['attributes']['pre'].$sourceAttr['sourceField'];
		
		if( $localUid >0 ){
			
			$intermediatetable =$this->settings['objects'][ $sourceAttr['intermediateObject'] ]['table'];
			$relatedTable = $this->settings['objects'][ $sourceAttr['relatedObject'] ]['table'];

			$repositoryName = str_replace('Model','Repository',$this->settings['objects'][ $sourceAttr['intermediateObject'] ]['class']).'Repository';
			
			if (class_exists($repositoryName)) {
				
				$getIdentifier = 'get'.ucfirst($this->settings['objects'][ $sourceAttr['relatedObject'] ]['targetIdentifier']);
				
				$query = $this->persistenceManager->createQueryForType($repositoryName);
				$query->getQuerySettings()->setReturnRawQueryResult(TRUE);

				$query->statement(
					'SELECT *' .
					' FROM '.$intermediatetable .
					' JOIN '.$relatedTable .
					' ON '.$intermediatetable.'.'.$sourceAttr['foreignField'].'='.$relatedTable.'.uid' .
					' WHERE ' .
						$relatedTable.'.'.$this->settings['objects'][ $sourceAttr['relatedObject'] ]['targetIdentifier'].'=\''.$child->$getIdentifier().'\'' .
					' AND '.$intermediatetable.'.'.$sourceAttr['localField']. '='. $localUid
					.' AND '.$intermediatetable.'.deleted=0'
					.' AND '.$relatedTable.'.deleted=0'
				); 
				
				$res = $query->execute();
				if(empty($res)){
					return FALSE;
				}else{
					return TRUE;
				}
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * ToDo: detach the intermediate relation
	 * 
	 * @param type $node
	 * @param type $sourceAttr
	 */
	public function destroyIntermediateRelation($node, $sourceAttr){
	}
	
	
	/**
	 * build a relation: n1
	 * @param type $node
	 * @param type $sourceAttr
	 */
	function buildManyToOneRelation($node, $sourceAttr){
		$relatedObject = NULL;
		$sourceIdentifier = $this->settings['attributes']['pre'].$sourceAttr['sourceField'];
		
		if ($node->$sourceIdentifier) {
			// find the Object to relate to
			$relatedRespository = $this->getRepositoryByModel( $this->settings['objects'][ $sourceAttr['relatedObject'] ]['class'] );
			$find = 'findBy'.ucfirst($this->settings['objects'][ $sourceAttr['relatedObject'] ]['targetIdentifier']);
			$relatedObjects = $relatedRespository->$find($node->$sourceIdentifier);
			$relatedObject = $relatedObjects[0];
		}
		return $relatedObject;
	}
	/**
	 * ToDo: destroy the n1 relation
	 */
	function destroyManyToOneRelation($node, $sourceAttr){
		
	}
	
	
	/**
	 * build a relation: 1n
	 * @param type $node
	 * @param type $sourceAttr
	 */
	function buildOneToManyRelation($node, $sourceAttr){
		
	}
	/**
	 * ToDo: destroy the 1n relation
	 */
	function destroyOneToManyRelation($node, $sourceAttr){
		
	}

	/**
	* make Instance of the Repository by the given Model 
	* @param int $uid The uid
	* @param string $object The objectName
	* @return mixed Object from class of $object | NULL if not found
	*/
	private function getRepositoryByModel($object) {
		if (class_exists($object)) {
			$repositoryName = str_replace('Model','Repository',$object).'Repository';
			if (class_exists($repositoryName)) {
				$repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($repositoryName);
				return $repository;
			}
		}else{
			throw new \Exception('Given Class doesn\'t exist: ' . $object.' - therefore a matching Respoistory kann not be instanciated');
		}
		return NULL;
	}

	/**
	 * yeah. dont say anithing. maybe this could still be an interesting feature... deletes everthing that is to old...
	 * @param type $data
	 * @return boolean
	 * @throws \Exception
	 */
	private function deleteOldEntries($data){
		
		$this->currentRunDatetime->sub( new \DateInterval('PT'.$this->settings['importWhiteTime'].'S') );
		
		foreach($this->settings['objects'] as $object => $setting){
			if($setting['removeNonOldEntries']==1){
				if($setting['class'] !== ''){
					
					$modelClass = $setting['parentClass']!=='' ? $setting['parentClass'] : $setting['class'];
					
					if (!class_exists($setting['class'])) {
						throw new \Exception('Model-Class '.$setting['class'].' for removing ' . $object . ' doesnt exists');
					}
					if ($setting['table'] === '' || $setting['table'] === NULL ) {
						throw new \Exception('no table for removing ' . $object);
					}
					
					// find old entries
					$query = $this->persistenceManager->createQueryForType($setting['class']);
					$statement = 'SELECT * FROM '.$setting['table'].' WHERE deleted=0 AND tstamp < '.$this->currentRunDatetime->format('U');
					$query->statement( $statement );
					$oldEntries = $query->execute();
//					
					$repository = $this->getRepositoryByModel($setting['class']);

					foreach($oldEntries as $oldEntry){
						$repository->remove($oldEntry);
					}
					
					$this->persistenceManager->persistAll();
							
				}else{
					throw new \Exception('no class given for removing '.$object);
				}
			}
		}
		return true;
	}
	
	private function propertyValue($node, $attrSetting){
		if(array_key_exists('value',$attrSetting)){
			return $attrSetting['value'];
		}else{
			$nodeProperty = $this->settings['attributes']['pre'].$attrSetting;
			return $node->$nodeProperty;
		}
	}
}

?>