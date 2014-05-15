<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class DbTableExistsValidator extends CValidator
{

	public $dbSchema;

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * 
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		if($this->dbSchema->getTable($object->$attribute) === null)
		{
			$message = $this->message !== null ? $this->message : TranslateModule::translate('Database table does not exist.');
			$this->addError($object, $attribute, $message);
		}
	}

}