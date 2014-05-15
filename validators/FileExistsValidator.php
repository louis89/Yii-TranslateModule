<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class FileExistsValidator extends CValidator
{

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if(!file_exists($value))
		{
			$message = $this->message !== null ? $this->message : TranslateModule::translate('File does not exist.');
			$this->addError($object,$attribute,$message);
		}
	}

}