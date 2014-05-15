<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class InstallableModel extends ConfigurationStatusModel implements Installable
{
	
	public function installAttributeRules()
	{
		return ($component = $this->getComponent()) === null ? array() : $component->installAttributeRules();
	}
	
	public function attributeRules()
	{
		return $this->installAttributeRules();
	}
	
	public function getIsInstalled()
	{
		return ($component = $this->getComponent()) !== null && $component->getIsInstalled();
	}
	
	public function install()
	{
		return ($component = $this->getComponent()) === null ? Installable::ERROR : $component->install();
	}
	
}