<?php

Yii::import('translate.widgets.configurationStatus.*');

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class InstallableWidget extends CWidget
{
	
	public $component;
	
	public $url = 'install';
	
	public $buttonText = 'Install';
	
	public $loadingText = 'Installing...';
	
	public $cancelText = 'Cancel';
	
	public $confirmText = 'Confirm';
	
	public $closeText = 'Close';
	
	public $statusCssClass = 'status';
	
	public $buttonHtmlOptions = array();
	
	public $dialogTitle = 'Installer Dialog';
	
	public $dialogOptions = array(
		'autoOpen' => false,
		'modal' => true,
		'width' => 'auto',
		'height' => 'auto'
	);
	
	public $installerOptions = array();
	
	public $progressBarOptions = array();
	
	private $_installableModel;
	
	public static function actions()
	{
		return array(
			'install' => 'translate.widgets.configurationStatus.actions.InstallAction',
		);
	}

	public function init()
	{
		$this->_installableModel = new InstallableModel($this->component);
		
		$this->attachBehavior('LDPublishAssetsBehavior', array('class' => 'ext.LDPublishAssetsBehavior.LDPublishAssetsBehavior', 'assetsDir' => dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'));

		$this->_installableModel->validate(null, false);
	}
	
	public function run($return = false)
	{
		// Set all default values now instead of in view file just in case we need a generated value after running this widget.
		$id = $this->getId();
		$this->installerOptions['component'] = $this->getModel()->getComponentID();
		$this->installerOptions['url'] = $this->url;
		$this->installerOptions['loadingText'] = $this->loadingText;
		$this->installerOptions['cancelText'] = $this->cancelText;
		$this->installerOptions['confirmText'] = $this->confirmText;
		$this->installerOptions['closeText'] = $this->closeText;
		
		if(!isset($this->installerOptions['statusId']))
		{
			$this->installerOptions['statusId'] = 'status_'.$id;
		}
		
		if(!isset($this->installerOptions['dialogId']))
		{
			$this->installerOptions['dialogId'] = 'dialog_'.$id;
		}
		
		if(!isset($this->installerOptions['progressBarId']))
		{
			$this->installerOptions['progressBarId'] = isset($this->progressBarOptions['id']) ? $this->progressBarOptions['id'] : 'progress_'.$id;
		}
		
		$this->dialogOptions['title'] = $this->dialogTitle;
		
		$this->buttonHtmlOptions['onClick'] = 'jQuery("#'.$id.'").tInstaller("install");';
		
		if(!isset($this->progressBarOptions['id']))
		{
			$this->progressBarOptions['id'] = $this->installerOptions['progressBarId'];
		}
		$this->progressBarOptions['value'] = 100;
		$this->installerOptions['progressBarSelector'] = 'div#'.$this->progressBarOptions['id'];
		
		return $this->render('installer',
				array(
					'id' => $id,
					'buttonText' => $this->buttonText,
					'statusCssClass' => $this->statusCssClass,
					'installerOptions' => $this->installerOptions,
					'buttonHtmlOptions' => $this->buttonHtmlOptions,
					'dialogOptions' => $this->dialogOptions,
					'progressBarOptions' => $this->progressBarOptions,
					'assetsUrl' => $this->getAssetsUrl()
				),
				$return
		);
	}
	
	public function getModel()
	{
		return $this->_installableModel;
	}
	
	public static function getStatusMessage($status)
	{
		switch($status)
		{
			case ConfigurationStatusModel::OK;
				return TranslateModule::translate('OK');
			case ConfigurationStatusModel::HAS_WARNINGS;
				return TranslateModule::translate('Has Warnings');
			case ConfigurationStatusModel::HAS_ERRORS;
				return TranslateModule::translate('Has Errors');
			default:
				return TranslateModule::translate('Unknown');
		}
	}
	
	public static function getStatusClass($status)
	{
		switch($status)
		{
			case ConfigurationStatusModel::OK;
				return 'attributeConfigNoError';
			case ConfigurationStatusModel::HAS_WARNINGS;
				return 'attributeConfigWarning';
			case ConfigurationStatusModel::HAS_ERRORS;
				return 'attributeConfigError';
			default:
				return 'attributeConfigError';
		}
	}
	
	/**
	 * Displays a summary of validation errors for one or several models.
	 * @param mixed $model the models whose input errors are to be displayed. This can be either
	 * a single model or an array of models.
	 * @param string $header a piece of HTML code that appears in front of the errors
	 * @param string $footer a piece of HTML code that appears at the end of the errors
	 * @param array $htmlOptions additional HTML attributes to be rendered in the container div tag.
	 * A special option named 'firstError' is recognized, which when set true, will
	 * make the error summary to show only the first error message of each attribute.
	 * If this is not set or is false, all error messages will be displayed.
	 * This option has been available since version 1.1.3.
	 * @return string the error summary. Empty if no errors are found.
	 * @see CModel::getErrors
	 * @see errorSummaryCss
	 */
	public static function errorSummary($model, $attribute = null, $header = null, $footer = null, $htmlOptions = array())
	{
		$content = '';
		if(!is_array($model))
		{
			$model = array($model);
		}
		if(isset($htmlOptions['firstError']))
		{
			$firstError = $htmlOptions['firstError'];
			unset($htmlOptions['firstError']);
		}
		else
		{
			$firstError = false;
		}
		foreach($model as $m)
		{
			foreach($m->getErrors($attribute) as $errors)
			{
				foreach((array)$errors as $error)
				{
					if($error != '')
					{
						$content .= "<li>$error</li>\n";
					}
					if($firstError)
					{
						break;
					}
				}
			}
		}
		if($content !== '')
		{
			if($header === null)
			{
				$header = '<p>'.TranslateModule::translate('Please fix the following input errors:').'</p>';
			}
			if(!isset($htmlOptions['class']))
			{
				$htmlOptions['class'] = CHtml::$errorSummaryCss;
			}
			return CHtml::tag('div', $htmlOptions, $header."\n<ul>\n$content</ul>".$footer);
		}
		return '';
	}
	
}
