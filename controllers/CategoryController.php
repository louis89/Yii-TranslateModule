<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class CategoryController extends ExplorerController
{
	
	public function filters()
	{
		return array_merge(parent::filters(), array(
			/*array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + index, view',
				'conditions' => 'grid + ajax'
			),*/
		));
	}
	
	public function actionTranslate(array $Category = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();
		if(!$translator->autoTranslate)
		{
			throw new CHttpException(501, $this->getTranslateModule()->t("Auto translate is disabled. Please check your system configuration."));
		}

		$model = $this->getModel('search');
		$model->with('missingTranslations');
		$model->setAttributes($Category);
		$condition = $model->getSearchCriteria();
	
		$translationsCreated = 0;
		$translationErrors = 0;
		$languageIds = array();
		$messageSourceIds = array();
	
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			$categories = $model->findAll($condition);
			if($dryRun)
			{
				foreach($categories as $category)
				{
					foreach($category->messageSources as $messageSource)
					{
						if(!isset($messageSourceIds[$messageSource->id]))
						{
							$messageSourceIds[$messageSource->id] = $messageSource->id;
							foreach($messageSource->missingTranslations as $language)
							{
								$languageIds[$language->id] = $language->id;
								$translationsCreated++;
							}
						}
					}
				}
			}
			else
			{
				$source = $translator->getMessageSourceComponent();
				foreach($categories as $category)
				{
					foreach($category->messageSources as $messageSource)
					{
						if(!isset($messageSourceIds[$messageSource->id]))
						{
							$messageSourceIds[$messageSource->id] = $messageSource->id;
							foreach($messageSource->missingTranslations as $language)
							{
								$languageIds[$language->id] = $language->id;
								$translation = $translator->translate($category->category, $messageSource->message, $messageSource->language->code, $language->code);
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
						'This action will translate {categoryCount} categories ({messagesTranslated} source messages) into {languagesCount} languages. {translationsCreated} message translations in total will be created. Are you sure that you would like to continue?', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{messagesTranslated}' => count($messageSourceIds), '{categoryCount}' => count($categories)))
			);
		}
		else
		{
			$this->renderMessage(
					$translationErrors > 0 ? TController::NOTICE : TController::SUCCESS,
					$this->getTranslateModule()->t(
					'{categoryCount} categories were translated. {translationsCreated} translations were created for {messagesTranslated} source messages in {languagesCount} languages. {translationErrors} errors occurred (see system log).',
					array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{messagesTranslated}' => count($messageSourceIds), '{categoryCount}' => count($categories), '{translationErrors}' => $translationErrors))
			);
		}
	}

	/**
	 * Deletes a Category and any associated Messages and MessageSources.
	 * 
	 * @param integer $id the ID of the Category to be deleted
	 */
	public function actionDelete(array $Category = array(), $dryRun = true)
	{
		$dryRun = true;
		$model = $this->getModel('search');
		$model->setAttributes($Category);
		$condition = $model->getSearchCriteria();

		$categoriesDeleted = 0;
		$messagesDeleted = 0;
		$sourceMessagesDeleted = 0;

		$transaction = Category::model()->getDbConnection()->beginTransaction();
		try
		{
			if(empty($Category))
			{
				if($dryRun)
				{
					$categoriesDeleted = Category::model()->count();
					$messagesDeleted = Message::model()->count();
					$sourceMessagesDeleted = MessageSource::model()->count();
				}
				else 
				{
					$categoriesDeleted = Category::model()->deleteAll();
					$messagesDeleted = Message::model()->deleteAll();
					$sourceMessagesDeleted = MessageSource::model()->deleteAll();
					CategoryMessage::model()->deleteAll();
					ViewMessage::model()->deleteAll();
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
					$categoriesDeleted = count($primaryKeys);
					$messagesDeleted = Message::model()->category($primaryKeys)->count();
					$sourceMessagesDeleted = MessageSource::model()->category($primaryKeys)->count();
				}
				else
				{
					$categoriesDeleted = Category::model()->deleteAllByPk($primaryKeys);
					$messagesDeleted = Message::model()->category($primaryKeys)->deleteAll();
					$sourceMessagesDeleted = MessageSource::model()->category($primaryKeys)->deleteAll();
					CategoryMessage::model()->deleteAllByAttributes(array('category_id' => $primaryKeys));
					ViewMessage::model()->deleteAllByAttributes(array('category_id' => $primaryKeys));
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
				$dryRun ? $this->getTranslateModule()->t('This action will deleted {categories} categories, {sourceMessages} source messages, and {messages} translations. Are you sure that you would like to continue?', array('{categories}' => $categoriesDeleted, '{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted)) : $this->getTranslateModule()->t('{categories} categories, {sourceMessages} source messages, and {messages} translations have been deleted.', array('{categories}' => $categoriesDeleted, '{sourceMessages}' => $sourceMessagesDeleted, '{messages}' => $messagesDeleted)), 
				'index'
		);
	}

}