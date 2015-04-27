<?php
namespace Z3\Z3Fasapi\Tests\Unit\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sven Külpmann <sven.kuelpmann@lenz-wie-fruehling.de>, lwf / z3
 *  			
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

/**
 * Test case for class Z3\Z3Fasapi\Controller\ApiController.
 *
 * @author Sven Külpmann <sven.kuelpmann@lenz-wie-fruehling.de>
 */
class ApiControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \Z3\Z3Fasapi\Controller\ApiController
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = $this->getMock('Z3\\Z3Fasapi\\Controller\\ApiController', array('redirect', 'forward', 'addFlashMessage'), array(), '', FALSE);
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function newActionAssignsTheGivenApiToView() {
		$api = new \Z3\Z3Fasapi\Domain\Model\Api();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('newApi', $api);
		$this->inject($this->subject, 'view', $view);

		$this->subject->newAction($api);
	}

	/**
	 * @test
	 */
	public function createActionAddsTheGivenApiToApiRepository() {
		$api = new \Z3\Z3Fasapi\Domain\Model\Api();

		$apiRepository = $this->getMock('', array('add'), array(), '', FALSE);
		$apiRepository->expects($this->once())->method('add')->with($api);
		$this->inject($this->subject, 'apiRepository', $apiRepository);

		$this->subject->createAction($api);
	}

	/**
	 * @test
	 */
	public function editActionAssignsTheGivenApiToView() {
		$api = new \Z3\Z3Fasapi\Domain\Model\Api();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('api', $api);

		$this->subject->editAction($api);
	}

	/**
	 * @test
	 */
	public function updateActionUpdatesTheGivenApiInApiRepository() {
		$api = new \Z3\Z3Fasapi\Domain\Model\Api();

		$apiRepository = $this->getMock('', array('update'), array(), '', FALSE);
		$apiRepository->expects($this->once())->method('update')->with($api);
		$this->inject($this->subject, 'apiRepository', $apiRepository);

		$this->subject->updateAction($api);
	}

	/**
	 * @test
	 */
	public function deleteActionRemovesTheGivenApiFromApiRepository() {
		$api = new \Z3\Z3Fasapi\Domain\Model\Api();

		$apiRepository = $this->getMock('', array('remove'), array(), '', FALSE);
		$apiRepository->expects($this->once())->method('remove')->with($api);
		$this->inject($this->subject, 'apiRepository', $apiRepository);

		$this->subject->deleteAction($api);
	}
}
