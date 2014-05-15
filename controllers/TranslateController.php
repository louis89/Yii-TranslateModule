<?php

Yii::import('translate.components.TController');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TranslateController extends TController
{
	
	public function actions()
	{
		return array(
			'configurationStatus.' => array(
				'class' => 'translate.widgets.configurationStatus.ConfigurationStatusWidget',
			));
	}

	/**
	 * override needed to check if its ajax, the redirect will be by javascript
	 */
	public function redirect($url, $terminate = true, $statusCode = 302)
	{
		if(Yii::app()->getRequest()->getIsAjaxRequest()) 
		{
			if(is_array($url)) 
			{
				$route = isset($url[0]) ? $url[0] : '';
				$url = $this->createUrl($route, array_slice($url, 1));
			}
			Yii::app()->getClientScript()->registerScript('redirect', "window.top.location='$url'");
			if($terminate)
			{
				Yii::app()->end($statusCode);
			}
		}
		else 
		{
			return parent::redirect($url, $terminate, $statusCode);
		}
	}
	
	public function actionTest()
	{
		
		if(Yii::app()->getRequest()->getIsAjaxRequest())
		{sleep(1);
			echo CJSON::encode(array('status' => 'confirm'));
		}
		else
		{
			$this->render('test');
		}
	}
	
	public function actionMissingOnPage(array $MissingMessages = array(), array $MissingMessage = array())
	{
		foreach($MissingMessage as $row)
		{
			$model = new MissingMessage();
			$model->setAttributes($row);
			$model->validate();
			if($model->hasErrors() || !$model->translate())
			{
				$MissingMessages[] = $model;
			}
		}
		
		$key = TranslateModule::ID.'-MissingMessage';
		if(empty($MissingMessages) && Yii::app()->getUser()->hasState($key))
		{
			$MissingMessages = Yii::app()->getUser()->getState($key);
		}
		else
		{
			Yii::app()->getUser()->setState($key, $MissingMessages);
		}
		
		foreach($MissingMessages as &$missing)
		{
			$model = new MissingMessage();
			$model->setAttributes($missing);
			$missing = $model;
		}
		
		$this->render('missing', array('MissingMessage' => $MissingMessages, 'translator' => $this->getModule()->getTranslator()));
	}

	public function actionTranslate($message, $sourceLanguage, $targetLanguage = null)
	{
		$translator = $this->getModule()->getTranslator();
		if(!$translator->autoTranslate)
		{
			$this->renderMessage(TController::ERROR, $this->getTranslateModule()->t('Auto translation are disabled.'));
		}
		else if($message === '')
		{
			$this->renderMessage(TController::ERROR, $this->getTranslateModule()->t('The message to translate must be specified.'));
		}
		else if($sourceLanguage === '')
		{
			$this->renderMessage(TController::ERROR, $this->getTranslateModule()->t('The source language of the message to be translated must be specified.'));
		}
		else
		{
			if($targetLanguage === null)
			{
				$targetLanguage = Yii::app()->getLanguage();
			}
			$translation = $translator->translate($message, $sourceLanguage, $targetLanguage);
			if($translation === false)
			{
				$this->renderMessage(TController::ERROR, $this->getTranslateModule()->t('Failed to translate message.'));
			}
			else
			{
				$this->renderMessage(TController::SUCCESS, is_array($message) ? $translation : (is_array($translation) ? $transaltion[0] : $translation));
			}
		}
	}

	/**
	 * Manages all models.
	 */
	public function actionIndex()
	{
		$this->render('index', array('translateModule' => $this->getModule()));
	}
	
	public function actionGarbage()
	{
		$tabs = array();
		foreach(array(
			'AcceptedLanguage', 
			'Category', 
			'Language', 
			'Message', 
			'MessageSource', 
			'Route', 
			'View', 
			'ViewSource') as $modelName)
		{
			$model = call_user_func(array($modelName, 'model'));
			if($model !== false && $model->getIsInstalled())
			{
				$tabs[TActiveRecord::formatName($modelName)] = $this->forwardAndReturn($modelName.'/garbage');
			}
		}
		
		$this->render('garbage', array('tabs' => $tabs));
	}
	
}