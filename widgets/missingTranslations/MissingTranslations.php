<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class MissingTranslations extends CWidget
{
	
	public $translateModuleID = TranslateModule::ID;
	
	public $message = 'There are {count} translations missing on this page. Click {link} to translate them now.';
	
	public $linkLabel = 'here';

	public $htmlOptions = array();
	
	public $varName = 'MissingMessages';
	
	public $route;
	
	public $translateModule;
	
	public $tCategory;
	
	public function init()
	{
		if($this->translateModule === null)
		{
			$this->translateModule = TranslateModule::findModule($this->translateModuleID);
		}
		if($this->route === null)
		{
			$this->route = $this->translateModuleID.'/missingOnPage';
		}
	}

	public function run()
	{
		$form = CHtml::form($this->getController()->createUrl($this->route), 'post', array('id' => $this->getId()));
		$i = 0;
		foreach($this->translateModule->getTranslator()->getMissingTranslations() as $config)
		{
			foreach($config as $name => $value)
			{
				$form .= CHtml::hiddenField($this->varName.'['.$i.']['.$name.']', $value);
			}
			$i++;
		}
		$tCategory = $this->tCategory === null ? $this->translateModule->tCategory : $this->tCategory;
		$form .= Yii::t($tCategory, $this->message, array('{count}' => count($this->translateModule->getTranslator()->getMissingTranslations()), '{link}' => CHtml::linkButton(Yii::t($tCategory, $this->linkLabel), $this->htmlOptions)));
		$form .= CHtml::endForm();
		echo $form;
	}

}
?>