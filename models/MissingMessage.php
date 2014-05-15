<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class MissingMessage extends CModel
{
	
	public $category;
	
	public $message;
	
	public $sourceLanguage;
	
	public $targetLanguage;
	
	public $translation;
	
	public function __construct($scenario = '')
	{
		$this->setScenario($scenario);
		$this->attachBehaviors($this->behaviors());
	}

	public function behaviors()
	{
		return array(
			'LDModelFilterBehavior' => 'ext.LDModelFilterBehavior.LDModelFilterBehavior',
			'LDModelAdvancedAttributeBehavior' => 'ext.LDModelAdvancedAttributeBehavior.LDModelAdvancedAttributeBehavior'
		);
	}

	public function rules()
	{
		return array(
			array('category, message, sourceLanguage, targetLanguage', 'required', 'safe' => true),
			array('category, message', 'length', 'allowEmpty' => false, 'safe' => true),
			array('sourceLanguage, targetLanguage', 'length', 'max' => 16, 'allowEmpty' => false, 'safe' => true),
		);
	}

	public function attributeLabels()
	{
		return array(
			'category' => TranslateModule::translate('Category'),
			'message' => TranslateModule::translate('Message'),
			'sourceLanguage' => TranslateModule::translate('Source Language'),
			'targetLanguage' => TranslateModule::translate('Target Language'),
			'translation' => TranslateModule::translate('Translation'),
		);
	}
	
	public function attributeNames()
	{
		return array(
			'category',
			'message',
			'sourceLanguage',
			'targetLanguage',
			'translation'
		);
	}
	
	public function translate()
	{
		$this->addError('translation', 'translate() Not yet implemented.');
	}
	
	public function getId()
	{
		return md5(((string)$this->category).((string)$this->message).((string)$this->sourceLanguage).((string)$this->targetLanguage));
	}

	public function __toString()
	{
		return (string)$this->message;
	}

}