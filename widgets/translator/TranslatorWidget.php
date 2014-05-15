<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TranslatorWidget extends CWidget
{
	
	public $label;
	
	public $selectors = array();

	public $message;
	
	public $targetLanguage;

	public $sourceLanguage;

	public $htmlOptions = array();
	
	public $translateUrl;
	
	public $loadingCssClass = 'translating';
	
	public $hiddenCssClass = 'hide';
	
	public $errorCssClass = 'error';
	
	public $statusCssClass = 'status';

	public function init()
	{
		$this->attachBehavior('LDPublishAssetsBehavior', array('class' => 'ext.LDPublishAssetsBehavior.LDPublishAssetsBehavior', 'assetsDir' => dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'));
		
		if($this->label === null)
		{
			$this->label = TranslateModule::translate('Auto Translate');
		}
		
		if($this->translateUrl === null)
		{
			$this->translateUrl = $this->getController()->createUrl('translate/translate/translate');
		}
		
		if(!isset($this->selectors['message']))
		{
			$this->selectors['message'] = '#'.$this->getId();
		}
		
		if(!isset($this->selectors['target']))
		{
			$this->selectors['target'] = $this->selectors['message'];
		}
	}

	public function run()
	{
		$this->render(
				'translator', 
				array(
					'id' => $this->getId(),
					'sourceLanguage' => $this->sourceLanguage,
					'targetLanguage' => $this->targetLanguage, 
					'assetsUrl' => $this->getAssetsUrl(), 
					'label' => $this->label, 
					'translateUrl' => $this->translateUrl, 
					'htmlOptions' => $this->htmlOptions,
					'errorCssClass' => $this->errorCssClass,
					'statusCssClass' => $this->statusCssClass,
					'translatorOptions' => array(
						'selectors' => $this->selectors,
						'url' => $this->translateUrl,
						'loadingCssClass' => $this->loadingCssClass,
						'hiddenCssClass' => $this->hiddenCssClass,
					)
				)
		);
	}

}
?>