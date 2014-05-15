<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class ConfigurationStatusValidator extends ComponentValidator
{

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * 
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		if(!$object->hasErrors($attribute))
		{
			$value = $object->$attribute;
			if(!$this->isEmpty($value, $this->trim))
			{
				$confStatModel = new ConfigurationStatusModel($value);
				if(!$confStatModel->validate())
				{
					$object->addErrors(array($attribute => $confStatModel->getErrors()));
				}
			}
		}
	}

}