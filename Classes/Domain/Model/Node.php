<?php
namespace Z3\Z3Fasapi\Domain\Model;


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
 * Api
 */
class Node extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Name  
	 *
	 * @var \string
	 */
	protected $name;
	
	/**
	 * Typ
	 *
	 * @var \string
	 */
	protected $type;
	
	/**
	 * Kindelemente
	 *
	 * @var \string
	 */
	protected $children;
	
	/**
	 * attribute 
	 *
	 * @var \string
	 */
	protected $attributes;
	
	
	
	
	
	
	/**
	 * getter
	 */
	
	
	
	public function getName($node){
		
		if($name->name == ''){
			return false;
		}
		
		return $node->name;
	}
	
	
	public function getType($node){
		
		if(substr($node->name, -1) == 's'){
			return 'list';
		}else{
			return 'item';
		}
		
	}
	public function getChildren($node){
		
		
		return $node->children;
//		if($node->children !== null){
//			return 'list';
//		}else{
//			return 'item';
//		}
		
	}
	
	
	
}