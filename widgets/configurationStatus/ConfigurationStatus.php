<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
interface ConfigurationStatus
{

	/**
	 * @return array list of configuration attribute names
	 * @see CModel::attributeNames()
	 */
	public function attributeNames();
	
	/**
	 * @return array list of validator rules for this configuration's attributes
	 * @see CModel::rules()
	 */
	public function attributeRules();
	
	/**
	 * @return array list of labels for this configuration's attributes
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels();
	
	/**
	 * @return array list of descriptions for each attribute of this configuration
	 */
	public function attributeDescriptions();
	
	/**
	 * @return string A description of this configuration
	 */
	public function getDescription();
	
}