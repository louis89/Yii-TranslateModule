<?php

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class ConfigurationStatusModel extends CModel implements ConfigurationStatus
{
	
	const OK = 0;
	
	const HAS_WARNINGS = 1;
	
	const HAS_ERRORS = 2;
	
	public $catchAttributeExceptions = true;

	private $_componentID;
	
	private $_component;
	
	public function __construct($component)
	{
		if(is_string($component))
		{
			$this->_componentID = $component;
			try 
			{
				$this->_component = @Yii::app()->getComponent($component);
			}
			catch(Exception $e)
			{
				$this->_component = null;
				$this->addError('component', TranslateModule::translate('An exception was thrown while attempting to load the component:').'&nbsp;'.$e->getMessage());
			}
		}
		else if(is_object($component))
		{
			//$this->_componentID = ucfirst(implode(' ', preg_split('/(?=[A-Z])/', get_class($component), -1, PREG_SPLIT_NO_EMPTY)));
			$this->_component = $component;
		}
		
		if(!$this->hasErrors('component'))
		{
			if($this->_component === null)
			{
				$this->addError('component', TranslateModule::translate('The component named "{name}" is either disabled or not configured! Please check your application\'s configuration.', array('{name}' => $component)));
			}
			else if(!$this->_component instanceof ConfigurationStatus)
			{
				$this->_component = null;
				$this->addError('component', TranslateModule::translate('Property "component" must be an object or a valid component name that implements the interface "ConfigurationStatus".'));
			}
		}
		
		$this->attachBehaviors($this->behaviors());
	}
	
	public function __get($name)
	{
		if($this->hasAttribute($name))
		{
			return $this->getAttribute($name);
		}
		return parent::__get($name);
	}
	
	public function __set($name, $value)
	{
		if($this->hasAttribute($name) && !$this->canSetProperty($name))
		{
			return $this->setAttribute($name, $value);
		}
		return parent::__set($name);
	}
	
	public function behaviors()
	{
		return array(
			'LDModelAdvancedAttributeBehavior' => 'ext.LDModelAdvancedAttributeBehavior.LDModelAdvancedAttributeBehavior'
		);
	}
	
	public function virtualAttributeNames()
	{
		return array('component', 'componentID', 'componentType', 'isInstallable');
	}
	
	public function attributeTypes()
	{
		return array( 
			'componentID' => 'text', 
			'componentType' => 'text',
			'isInstallable' => 'boolean'
		);
	}
	
	public function getStatus()
	{
		$errors = $this->getErrors();
		if(empty($errors))
		{
			return self::OK;
		}
		foreach($errors as $attribute => $err)
		{
			if($this->isAttributeRequired($attribute))
			{
				return self::HAS_ERRORS;
			}
		}
		return self::HAS_WARNINGS;
	}
	
	public function getComponentType()
	{
		return get_class($this->getComponent());
	}
	
	public function getComponentID()
	{
		return $this->_componentID;
	}
	
	public function hasAttribute($name)
	{
		return in_array($name, $this->attributeNames());
	}
	
	public function getAttribute($name)
	{
		if($this->hasAttribute($name))
		{
			try
			{
				return $this->getComponent()->$name;
			}
			catch(Exception $e)
			{
				if(!$this->catchAttributeExceptions)
				{
					throw $e;
				}
				$this->addError($name, TranslateModule::translate('While attempting to get the attribute "{attribute}" an exception was thrown with the message "{message}".', array('{attribute}' => $name, '{message}' => $e->getMessage())));
			}
		}
	}
	
	public function setAttribute($name, $value)
	{
		if($this->hasAttribute($name))
		{
			try
			{
				$this->getComponent()->$name = $value;
			}
			catch(Exception $e)
			{
				if(!$this->catchAttributeExceptions)
				{
					throw $e;
				}
				$this->addError($name, TranslateModule::translate('While attempting to set the attribute "{attribute}" an exception was thrown with the message "{message}".', array('{attribute}' => $name, '{message}' => $e->getMessage())));
			}
		}
	}
	
	public function getComponent()
	{
		return $this->_component;
	}
	
	public function getIsInstallable()
	{
		return $this->getComponent() instanceof Installable;
	}
	
	public function attributeNames()
	{
		return $this->_component === null ? array() : $this->_component->attributeNames();
	}
	
	public function attributeRules()
	{
		return $this->_component === null ? array(array('component', 'required')) : $this->_component->attributeRules();
	}
	
	public function attributeLabels()
	{
		return $this->_component === null ? array() : $this->_component->attributeLabels();
	}
	
	public function attributeDescriptions()
	{
		return $this->_component === null ? array() : $this->_component->attributeDescriptions();
	}
	
	public function getDescription()
	{
		return $this->_component === null ? false : $this->_component->getDescription();
	}
	
	public function rules()
	{
		return $this->attributeRules();
	}
	
	public function isAttributeConfigurationStatus($name)
	{
		$value = $this->getAttribute($name);
		if($value instanceof ConfigurationStatus)
		{
			return true;
		}

		foreach($this->getValidators($name) as $validator)
		{
			if($validator instanceof ConfigurationStatusValidator)
			{
				return true;
			}
		}
		return false;
	}
	
	public function hasDescription($name)
	{
		return array_key_exists($name, $this->attributeDescriptions()) || $this->isAttributeConfigurationStatus($name);
	}
	
	public function getAttributeDescription($attribute)
	{
		$descriptions = $this->attributeDescriptions();
		if(isset($descriptions[$attribute]))
		{
			return $descriptions[$attribute]; // If a description is defined for the attribute
		}
		
		if($this->isAttributeConfigurationStatus($attribute))
		{
			$value = $this->getAttribute($attribute);
			if(!$value instanceof ConfigurationStatus)
			{
				$value = Yii::app()->getComponent($value);
			}
			return $value->getDescription();
		}

		return false; // Default is to hide description
	}
	
}