<?php

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
interface Installable
{

	/**
	 * Status code 0 success. Install successful.
	 *
	 * @name TranslateModule::SUCCESS
	 * @type integer
	 * @const integer
	 */
	const SUCCESS = 0;
	
	/**
	 * Install status code 1 overwrite. Install failed because component is already installed.
	 *
	 * @name TranslateModule::OVERWRITE
	 * @type integer
	 * @const integer
	 */
	const OVERWRITE = 1;
	
	/**
	 * Install status code 2 ERROR. Install failed because of an error.
	 *
	 * @name TranslateModule::ERROR
	 * @type integer
	 * @const integer
	 */
	const ERROR = 2;
	
	public function getIsInstalled();
	
	public function install();
	
	public function installAttributeRules();
	
}