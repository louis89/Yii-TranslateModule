<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class LanguageController extends ExplorerController
{

	/*public function filters()
	{
		return array_merge(parent::filters(), array(
			'ajaxOnly + ajaxIndex, ajaxView, ajaxCreate',
			'postOnly + create, ajaxCreate',
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + index',
				'conditions' => 'ajaxIndex + ajax'
			),
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + view',
				'conditions' => 'ajaxView + ajax'
			),
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + create',
				'conditions' => 'ajaxCreate + ajax'
			)
		));
	}*/
	
	public function actionTranslate(array $Language = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();
		if(!$translator->autoTranslate)
		{
			throw new CHttpException(501, $this->getTranslateModule()->t("Auto translate is disabled. Please check your system configuration."));
		}
	
		$model = $this->getModel('search');
		$model->with('missingTranslations');
		$model->setAttributes($Language);
		$condition = $model->getSearchCriteria();
	
		$translationsCreated = 0;
		$translationErrors = 0;
		$messageSourceIds = array();
	
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			$languages = $model->findAll($condition);
			if($dryRun)
			{
				foreach($languages as $language)
				{
					foreach($language->missingTranslations as $messageSource)
					{
						$messageSourceIds[$messageSource->id] = $messageSource->id;
						$translationsCreated++;
					}
				}
			}
			else
			{
				$source = $translator->getMessageSourceComponent();
				foreach($languages as $language)
				{
					foreach($language->missingTranslations as $messageSource)
					{
						$messageSourceIds[$messageSource->id] = $messageSource->id;
						$translation = $translator->translate($messageSource->message, $messageSource->language->code, $language->code);
						if($translation !== false)
						{
							if($source->addTranslation($messageSource->id, $language->code, trim($translation)) === null)
							{
								Yii::log("Message with ID '{$messageSource->id}' could not be added to the message source component after translating it to language '{$language->code}'", CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
								$translationErrors++;
								continue;
							}
						}
						else
						{
							Yii::log("Message with ID '{$messageSource->id}' could not be translated to '{$language->code}'.", CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
							$translationErrors++;
							continue;
						}
					}
				}
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderMessage($e);
		}
	
		if($dryRun)
		{
			$this->renderMessage(
				TController::SUCCESS,
				$this->getTranslateModule()->t(
						'This action will translate {messagesTranslated} source messages into {languagesCount} languages. {translationsCreated} message translations in total will be created. Are you sure that you would like to continue?', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languages), '{messagesTranslated}' => count($messageSourceIds))
				)
			);
		}
		else
		{
			$this->renderMessage(
				$translationErrors > 0 ? TController::NOTICE : TController::SUCCESS,
				$this->getTranslateModule()->t(
						'{translationsCreated} translations were created for {messagesTranslated} source messages in {languagesCount} languages. {translationErrors} errors occurred (see system log).', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languages), '{messagesTranslated}' => count($messageSourceIds), '{translationErrors}' => $translationErrors)
				)
			);
		}
	}
	
	public function actionCompile(array $ViewSource = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();

		$model = $this->getModel('search');
		$model->with('missingViews');
		$model->setAttributes($ViewSource);
		$condition = $model->getSearchCriteria();
	
		$translationsCreated = 0;
		$viewsTranslated = 0;
		$languagesCount = 0;
		$translationErrors = 0;
	
		$transaction = Yii::app()->db->beginTransaction();
		try
		{
			$viewSources = $model->findAll($condition);
			if($dryRun)
			{
				$languageIds = array();
				foreach($viewSources as $viewSource)
				{
					foreach($viewSource->missingViews as $language)
					{
						$languageIds[$language->id] = $language->id;
						$translationsCreated++;
					}
					$viewsTranslated++;
				}
				$languagesCount = count($languageIds);
			}
			else
			{
				$viewRenderer = Yii::app()->getViewRenderer();
				$languageIds = array();
				foreach($viewSources as $viewSource)
				{
					if($viewSource->getIsReadable())
					{
						foreach($viewSource->missingViews as $language)
						{
							$languageIds[$language->id] = $language->id;
							try
							{
								$viewRenderer->generateViewFile($viewSource->path, $viewRenderer->getViewFile($viewSource->path, $language->code), null, $translator->messageSource, $language->code, false);
							}
							catch(CException $ce)
							{
								Yii::log($ce->getMessage(), CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
								$translationErrors++;
								continue;
							}
							$translationsCreated++;
						}
						$viewsTranslated++;
					}
					else
					{
						Yii::log('The source view with ID "'.$viewSource->id.'" could not be translated because its path not readable.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
						$translationErrors++;
					}
				}
				$languagesCount = count($languageIds);
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderMessage($e);
		}
	
		if($dryRun)
		{
			$this->renderMessage(
				TController::SUCCESS,
				$this->getTranslateModule()->t('This action will translate {viewsTranslated} source views into {languagesCount} languages. {translationsCreated} view translations in total will be created. Are you sure that you would like to continue?', array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => $languagesCount, '{viewsTranslated}' => $viewsTranslated))
			);
		}
		else if($translationErrors > 0)
		{
			$this->renderMessage(
				TController::NOTICE,
				$this->getTranslateModule()->t('{translationErrors} views could not be translated. Only {translationsCreated} views were successfully translated. Please see the system\'s logs for details.', array('{translationsCreated}' => $translationsCreated, '{translationErrors}' => $translationErrors))
			);
		}
		else
		{
			$this->renderMessage(
				TController::SUCCESS,
				$this->getTranslateModule()->t('{translationsCreated} view translations have been created for {viewsTranslated} source views in {languagesCount} languages.', array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => $languagesCount, '{viewsTranslated}' => $viewsTranslated))
			);
		}
	}
	
	public function actionCreate($id)
	{
		$model = new Language;
		$model->id = $id;

		$success = $model->save();

		if($model->save())
		{
			$this->renderMessage(TController::SUCCESS, 'The language has been created.');
		}
		else
		{
			$this->renderMessage(TController::ERROR, 'The language could not be created.');
		}
	}

	public function actionAjaxCreate(array $Language = array(), $ajax = null)
	{
		if(isset($ajax))
		{
			if($ajax === 'accepted-language-create-form')
			{
				$language = new Language;
				$language->setAttributes($Language);
				echo CActiveForm::validate($language);
			}
		}
	}

	/**
	 * Deletes a Language and any associated AcceptedLanguages, MessageSources, Messages, and Views
	 * 
	 * @param integer $id the ID of the Language to be deleted
	 */
	/*public function actionDelete(array $Language = array(), $dryRun = true)
	{
		$dryRun = true;

		$model = $this->getModel('search');
		$model->setAttributes($Language);
		$condition = $model->getSearchCriteria();
		
		$messagesDeleted = 0;
		$sourceMessagesDeleted = 0;
		$viewsDeleted = 0;
		$acceptedLanguagesDeleted = 0;
		$languagesDeleted = 0;
		
		$transaction = Language::model()->getDbConnection()->beginTransaction();
		try
		{
			if(empty($Language))
			{
				if($dryRun)
				{
					$messagesDeleted = Message::model()->count();
					$sourceMessagesDeleted = MessageSource::model()->count();
					$viewsDeleted = View::model()->count();
					$acceptedLanguagesDeleted = AcceptedLanguage::model()->count();
					$languagesDeleted = Language::model()->count();
				}
				else 
				{
					$messagesDeleted = Message::model()->deleteAll();
					$sourceMessagesDeleted = MessageSource::model()->deleteAll();
					$viewsDeleted = View::model()->deleteAll();
					$acceptedLanguagesDeleted = AcceptedLanguage::model()->deleteAll();
					$languagesDeleted = Language::model()->deleteAll();
					ViewMessage::model()->deleteAll();
					CategoryMessage::model()->deleteAll();
				}
			}
			else
			{
				$primaryKeys = array();
				foreach($model->filter($model->findAll($condition)) as $record)
				{
					$primaryKeys[] = $record['id'];
				}
				
				if($dryRun)
				{
					$messagesDeleted = Message::model()->languageSelfOrSource($primaryKeys)->count();
					$sourceMessagesDeleted = MessageSource::model()->language($primaryKeys)->count();
					$viewsDeleted = View::model()->language($primaryKeys)->count();
					$acceptedLanguagesDeleted = AcceptedLanguage::model()->countByAttributes(array('id' => $primaryKeys));
					$languagesDeleted = count($primaryKeys);
				}
				else
				{
					$messagesDeleted = Message::model()->languageSelfOrSource($primaryKeys)->deleteAll();
					$sourceMessagesDeleted = MessageSource::model()->language($primaryKeys)->deleteAll();
					$viewsDeleted = View::model()->language($primaryKeys)->deleteAll();
					$acceptedLanguagesDeleted = AcceptedLanguage::model()->deleteAllByPk($primaryKeys);
					$languagesDeleted = Language::model()->deleteAllByPk($primaryKeys);
					ViewMessage::model()->deleteAllByAttributes(array('message_id' => $primaryKeys));
					CategoryMessage::model()->deleteAllByAttributes(array('message_id' => $primaryKeys));
				}
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderMessage($e);
		}
		
		$this->renderMessage(
				TController::SUCCESS, 
				$dryRun ? $this->getTranslateModule()->t('This action will deleted {languages} languages, {acceptedLanguages} accepted languages, {sourceMessages} source messages, {messages} translations, and {views} translated views. Are you sure that you would like to continue?', array('{languages}' => $languagesDeleted, '{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted, '{acceptedLanguages}' => $acceptedLanguagesDeleted, '{views}' => $viewsDeleted)) : $this->getTranslateModule()->t('{languages} languages, {acceptedLanguages} accepted languages, {sourceMessages} source messages, {messages} translations, and {views} translated views have been deleted.', array('{languages}' => $languagesDeleted, '{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted, '{acceptedLanguages}' => $acceptedLanguagesDeleted, '{views}' => $viewsDeleted)),
				'index'
		);
	}*/

}