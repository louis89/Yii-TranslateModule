<?php

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class ComponentValidator extends CValidator
{

	/**
	 * @var mixed Either a string the expected class name of the component or an object of the expected type of the component.
	 */	
	public $type;
	
	/**
	 * @var boolean Whether to allow the attribute to be empty. Defaults to False.
	 */
	public $allowEmpty = false;
	
	public $trim = true;

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * 
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if($this->isEmpty($value, $this->trim))
		{
			if(!$this->allowEmpty)
			{
				$this->addError($object, $attribute, $this->message !== null ? $this->message : TranslateModule::translate('Component is disabled.'));
			}
		}
		else 
		{
			try
			{
				$component = Yii::app()->getComponent($value);
				if($component === null)
				{
					$this->addError($object, $attribute, $this->message !== null ? $this->message : TranslateModule::translate('Component "{attributeValue}" not found. The component is either disabled or has not been configured.', array('{attributeValue}' => $value)));
				}
				elseif($this->type !== null)
				{
					$reflection = new ReflectionClass($this->type);
					if(!$reflection->isInstance($component))
					{
						$this->addError($object, $attribute, $this->message !== null ? $this->message : TranslateModule::translate('Component "{attributeValue}" is of type "{type}", but must be of type "{expectedType}".', array('{type}' => is_object($component) ? get_class($component) : gettype($component), '{expectedType}' => $this->type, '{attributeValue}' => $value)));
					}
				}
			}
			catch(Exception $e)
			{
				$this->addError($object, $attribute, $this->message !== null ? $this->message : TranslateModule::translate('The following exception was thrown while attempting to load component "{attributeValue}". Exception message: "{message}"', array('{message}' => $e->getMessage())));
			}
		}
	}

}