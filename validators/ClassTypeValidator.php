<?php

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class ClassTypeValidator extends CValidator
{

	/**
	 * @var mixed Either a string the expected class name of the component or an object of the expected type of the component.
	 */	
	public $type;
	
	/**
	 * @var boolean Whether to allow the attribute to be empty (strictly false or null). Defaults to False.
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
		if(!$this->allowEmpty)
		{
			$reflection = new ReflectionClass($this->type);
			if($this->isEmpty($value, $this->trim) || !$reflection->isInstance($value))
			{
				$this->addError($object, $attribute, $this->message !== null ? $this->message : TranslateModule::translate('Must be an instance of "{type}".', array('{type}' => $this->type)));
			}
		}
	}

}