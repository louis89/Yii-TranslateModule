<?php

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class EActionBehavior extends CModelBehavior
{
	
	const METHOD_SAVE = 'save';
	
	private $_ownerReflection;

	/**
	 * @return ReflectionClass A reflection of the owner's class
	 */
	public function getOwnerReflection()
	{
		if($this->_ownerReflection === null)
		{
			$this->_ownerReflection = new ReflectionClass($this_>getOwner());
		}
		return $this->_ownerReflection;
	}
	
	public function save($runValidation = true, $attributes = null)
	{
		if($this->getOwnerReflection()->hasMethod(self::METHOD_SAVE))
		{
			$method = $this->getOwnerReflection()->getMethod(self::METHOD_SAVE);
			if($method->isPublic())
			{
				return $method->invoke($this->getOwner(), self::METHOD_SAVE, $runValidation, $attributes);
			}
		}
		return true;
	}
	
	public function delete($runValidation = true, $attributes = null)
	{
		if($this->getOwnerReflection()->hasMethod(self::METHOD_SAVE))
		{
			$method = $this->getOwnerReflection()->getMethod(self::METHOD_SAVE);
			if($method->isPublic())
			{
				return $method->invoke($this->getOwner(), self::METHOD_SAVE, $runValidation, $attributes);
			}
		}
		return true;
	}
	
	public function save($runValidation = true, $attributes = null)
	{
		if($this->getOwnerReflection()->hasMethod(self::METHOD_SAVE))
		{
			$method = $this->getOwnerReflection()->getMethod(self::METHOD_SAVE);
			if($method->isPublic())
			{
				return $method->invoke($this->getOwner(), self::METHOD_SAVE, $runValidation, $attributes);
			}
		}
		return true;
	}
	
	/**
	 * Responds to {@link CModel::onAfterConstruct} event.
	 * Override this method and make it public if you want to handle the corresponding event
	 * of the {@link CBehavior::owner owner}.
	 * @param CEvent $event event parameter
	 */
	protected function afterConstruct($event)
	{
	}

	/**
	 * Responds to {@link CModel::onBeforeValidate} event.
	 * Override this method and make it public if you want to handle the corresponding event
	 * of the {@link owner}.
	 * You may set {@link CModelEvent::isValid} to be false to quit the validation process.
	 * @param CModelEvent $event event parameter
	 */
	protected function beforeValidate($event)
	{
	}

	/**
	 * Responds to {@link CModel::onAfterValidate} event.
	 * Override this method and make it public if you want to handle the corresponding event
	 * of the {@link owner}.
	 * @param CEvent $event event parameter
	 */
	protected function afterValidate($event)
	{
	}
	
}