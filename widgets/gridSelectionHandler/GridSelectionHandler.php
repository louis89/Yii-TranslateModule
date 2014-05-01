<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class GridSelectionHandler extends CWidget
{
	
	public $gridId;
	
	public $url = '';
	
	public $useAjax = true;
	
	public $activeRecordClass = '';
	
	public $keys = array('id');
	
	public $buttonText = 'Handle Selection';
	
	public $loadingText = 'Loading...';
	
	public $statusCssClass = 'status';
	
	public $buttonHtmlOptions = array();
	
	public $dialogTitle = 'Selection Handler Dialog';
	
	public $dialogOptions = array(
			'autoOpen' => false,
			'modal' => true,
			'width' => 'auto',
			'height' => 'auto'
	);
	
	public $selectionHandlerOptions = array();
	
	public $progressBarOptions = array();
	
	public $relatedGrids = array();
	
	public function init()
	{
		$this->attachBehavior('LDPublishAssetsBehavior', array('class' => 'ext.LDPublishAssetsBehavior.LDPublishAssetsBehavior', 'assetsDir' => dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'));
	}

	public function run()
	{
		$id = $this->getId();
		
		// Set all default values now instead of in view file just in case we need a generated value after running this widget.
		$this->selectionHandlerOptions['gridId'] = $this->gridId;
		$this->selectionHandlerOptions['url'] = $this->url;
		$this->selectionHandlerOptions['useAjax'] = $this->useAjax;
		$this->selectionHandlerOptions['activeRecordClass'] = $this->activeRecordClass;
		$this->selectionHandlerOptions['keys'] = $this->keys;
		$this->selectionHandlerOptions['loadingText'] = $this->loadingText;
		$this->selectionHandlerOptions['relatedGrids'] = $this->relatedGrids;
		$this->selectionHandlerOptions['confirmButtons'] = array(
			TranslateModule::translate('Cancel') => 'js:function() {'.
				'$(this).dialog("close");'.
			'}',
			$this->buttonText => 'js:function() {'.
				'jQuery("#'.$id.'").tSelectionHandler("handleSelection");'.
			'}'
		);
		$this->selectionHandlerOptions['completeButtons'] = array(
			TranslateModule::translate('Close') => 'js:function() {'.
				'$(this).dialog("close");'.
				'jQuery("#'.$id.'").tSelectionHandler("status");'.
			'}'
		);
		
		if(!isset($this->selectionHandlerOptions['statusId']))
		{
			$this->selectionHandlerOptions['statusId'] = 'status_'.$id;
		}
		
		$this->dialogOptions['title'] = $this->dialogTitle;
		
		$this->buttonHtmlOptions['onClick'] = 'jQuery("#'.$id.'").tSelectionHandler("open");';

		if(!isset($this->progressBarOptions['id']))
		{
			$this->progressBarOptions['id'] = 'progress_'.$id;
		}
		$this->progressBarOptions['value'] = 100;
		$this->selectionHandlerOptions['progressBarSelector'] = 'div#'.$this->progressBarOptions['id'];
		
		$this->render('dialog',
			array(
				'id' => $id,
				'buttonText' => $this->buttonText,
				'statusCssClass' => $this->statusCssClass,
				'selectionHandlerOptions' => $this->selectionHandlerOptions,
				'buttonHtmlOptions' => $this->buttonHtmlOptions,
				'dialogOptions' => $this->dialogOptions,
				'progressBarOptions' => $this->progressBarOptions,
				'assetsUrl' => $this->getAssetsUrl()
			)
		);
	}
	
	public function getId($autoGenerate = true)
	{
		if(!isset($this->gridId))
		{
			throw new CException(TranslateModule::translate('A grid ID has not been specifed for GridSelectionHandler.'));
		}
		return parent::getId($autoGenerate).'_selectionHandler_'.$this->gridId;
	}

}
?>