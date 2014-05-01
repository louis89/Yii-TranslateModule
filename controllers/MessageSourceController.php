<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class MessageSourceController extends ExplorerController
{

	public function filters()
	{
		/*return array_merge(parent::filters(), array(
			'ajaxOnly + ajaxIndex, ajaxView',
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + index',
				'conditions' => 'ajaxIndex + ajax'
			),
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + view',
				'conditions' => 'ajaxView + ajax'
			)
		));*/
		return array();
	}
	
	public function actionTranslate(array $MessageSource = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();
		if(!$translator->autoTranslate)
		{
			throw new CHttpException(501, $this->getTranslateModule()->t("Auto translate is disabled. Please check your system configuration."));
		}
		
		$model = $this->getModel('search');
		$model->with('missingTranslations');
		$model->setAttributes($MessageSource);
		$condition = $model->getSearchCriteria();
		
		$translationsCreated = 0;
		$translationErrors = 0;
		
		$languageIds = array();
		
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			$messageSources = $model->with('language')->findAll($condition);
			if($dryRun)
			{
				foreach($messageSources as $messageSource)
				{
					foreach($messageSource->missingTranslations as $language)
					{
						$languageIds[$language->id] = $language->id;
						$translationsCreated++;
					}
				}
			}
			else
			{
				$source = $translator->getMessageSourceComponent();
				foreach($messageSources as $messageSource)
				{
					foreach($messageSource->missingTranslations as $language)
					{
						$languageIds[$language->id] = $language->id;
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
						$translationsCreated++;
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
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{messagesTranslated}' => count($messageSources))
				)
			);
		}
		else
		{
			$this->renderMessage(
				$translationErrors > 0 ? TController::NOTICE : TController::SUCCESS,
				$this->getTranslateModule()->t(
						'{translationsCreated} translations were created for {messagesTranslated} source messages in {languagesCount} languages. {translationErrors} errors occurred (see system log).', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{messagesTranslated}' => count($messageSources), '{translationErrors}' => $translationErrors)
				)
			);
		}
	}

	/**
	 * Deletes a MessageSource and all associated Messages
	 * 
	 * @param integer $id the ID of the MessageSource to be deleted
	 */
	public function actionDelete(array $MessageSource = array(), $dryRun = true)
	{
		$dryRun = true;

		$model = $this->getModel('search');
		$model->setAttributes($MessageSource);
		$condition = $model->getSearchCriteria();

		$sourceMessagesDeleted = 0;
		$messagesDeleted = 0;
		
		$transaction = MessageSource::model()->getDbConnection()->beginTransaction();
		try
		{
			$primaryKeys = array();
			foreach($model->filter($model->findAll($condition)) as $record)
			{
				$primaryKeys[] = $record['id'];
			}
			
			if($dryRun)
			{
				$messagesDeleted = Message::model()->countByAttributes(array('id' => $primaryKeys));
				$sourceMessagesDeleted = count($primaryKeys);
			}
			else
			{
				$messagesDeleted = Message::model()->deleteAllByAttributes(array('id' => $primaryKeys));
				$sourceMessagesDeleted = MessageSource::model()->deleteByPk($primaryKeys);
				CategoryMessage::model()->deleteAllByAttributes(array('message_id' => $primaryKeys));
				ViewMessage::model()->deleteAllByAttributes(array('message_id' => $primaryKeys));
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
				$dryRun ? $this->getTranslateModule()->t('This action will delete {sourceMessages} source messages and {messages} translations. Are you sure that you would like to continue?', array('{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted)) : $this->getTranslateModule()->t('{sourceMessages} source messages and {messages} translations have been deleted.', array('{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted)),
				'index'
		);
	}
	
	/**
	 * Flushes the cache of the translator's message source component.
	 */
	public function actionFlushCache()
	{
		$this->getTranslateModule()->getMessageSource()->flushCache();
	}

}