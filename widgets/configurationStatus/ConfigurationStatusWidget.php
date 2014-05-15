<?php

Yii::import('translate.widgets.configurationStatus.*');

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class ConfigurationStatusWidget extends CWidget
{
	
	public $showKey = true;
	
	public $formatter;
	
	public $component;
	
	private $_confStatModel;
	
	public static function actions()
	{
		return array(
			'install' => 'translate.widgets.configurationStatus.actions.InstallAction',
		);
	}

	public function init()
	{
		$this->_confStatModel = new ConfigurationStatusModel($this->component);
		
		if($this->formatter === null)
		{
			$this->formatter = Yii::app()->format;
		}
		
		$this->attachBehavior('LDPublishAssetsBehavior', array('class' => 'ext.LDPublishAssetsBehavior.LDPublishAssetsBehavior', 'assetsDir' => dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'));

		$this->_confStatModel->validate(null, false);
	}
	
	public function run($return = false)
	{
		Yii::app()->getClientScript()->registerCssFile($this->getAssetsUrl().'/configurationStatusGrid.css');
		return $this->render('grid', array('id' => $this->getId(), 'confStatModel' => $this->_confStatModel, 'formatter' => $this->formatter, 'showKey' => $this->showKey, 'actionPrefix' => $this->actionPrefix), $return);
	}
	
	public function getModel()
	{
		return $this->_confStatModel;
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
