<?php
namespace Z3\Z3Fasapi\Utility;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Validator
 *
 * @author sven.kuelpmann
 */
class Validator {
	
	public function isJson($string){
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}


}
