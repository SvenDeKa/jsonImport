<?php
namespace Z3\Z3Fasapi\Utility;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Mapping
 *
 * @author sven.kuelpmann
 */
class Mapping {
	
	public function getNodeName($node){
		//print_r($node);
		if($node->name == ''){
			print_r($node->name);
			return false;	//	keep it simple
//			return array(
//				'status'=>false,
//				'msg'=>'no nodetype given'
//			);
		}
		
		return $node->name;
	}
	
	
	public function getNodeType($node){
		
		if(substr($node->name, -1) == 's'){
			return 'list';
		}else{
			return 'item';
		}
	}
	
}
