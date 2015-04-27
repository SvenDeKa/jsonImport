<?php
namespace Z3\Z3Fasapi\Controller;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Sven Külpmann <sven.kuelpmann@lenz-wie-fruehling.de>, lwf / z3
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
 * ApiController
 */
class ApiController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * counting the json-node while walking through them
	 * @var int 
	 */
	protected $nodeCount = 0;

	/**
	 * @ToDo: create a Status-Object instead of an array...
	 * @var array 
	 */
	protected $apiStatus;
	
	/**
	 * apiRepository
	 *
	 * @var \Z3\Z3Fasapi\Domain\Repository\ApiRepository
	 * @inject
	 */
	protected $apiRepository;
	
	
	/**
	 * apicall
	 */
	public function apiCallAction(){

		$arguments = $this->request->getArguments();

		$this->apiStatus = array(
			'status' => FALSE,
			'msg' => 'nothing done'
		);

		$this->apiRepository->importData($arguments['data']);

		// success-message
		if($this->nodeCount > 0){
			$this->apiStatus = array(
				'status' => TRUE,
				'msg' => $this->nodeCount. ' valid nodes processed'
			);
		}

		return $this->apiStatus;
		
	}
	
	
	/**
	 * 
	 *	the following stuff was just for testing purposes. will be removed as it is not needed anymore.
	 *  
	 */
	
	
	/**
	 * action new
	 * 
	 * @param \Z3\Z3Fasapi\Domain\Model\Api $newApi
	 * @ignorevalidation $newApi
	 * @return void
	 */
	public function newAction(\Z3\Z3Fasapi\Domain\Model\Api $newApi = NULL) {
		$this->view->assign('newApi', $newApi);
	}

	/**
	 * action create
	 * 
	 * @param \Z3\Z3Fasapi\Domain\Model\Api $newApi
	 * @return void
	 */
	public function createAction(\Z3\Z3Fasapi\Domain\Model\Api $newApi) {
		$this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->apiRepository->add($newApi);
		$this->redirect('list');
	}

	/**
	 * action edit
	 * 
	 * @param \Z3\Z3Fasapi\Domain\Model\Api $api
	 * @ignorevalidation $api
	 * @return void
	 */
	public function editAction(\Z3\Z3Fasapi\Domain\Model\Api $api) {
		$this->view->assign('api', $api);
	}

	/**
	 * action update
	 * 
	 * @param \Z3\Z3Fasapi\Domain\Model\Api $api
	 * @return void
	 */
	public function updateAction(\Z3\Z3Fasapi\Domain\Model\Api $api) {
		$this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->apiRepository->update($api);
		$this->redirect('list');
	}

	/**
	 * action delete
	 * 
	 * @param \Z3\Z3Fasapi\Domain\Model\Api $api
	 * @return void
	 */
	public function deleteAction(\Z3\Z3Fasapi\Domain\Model\Api $api) {
		$this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->apiRepository->remove($api);
		$this->redirect('list');
	}

}