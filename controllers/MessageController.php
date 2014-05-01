<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class MessageController extends ExplorerController
{

	/*public function filters()
	{
		return array_merge(parent::filters(), array(
			'ajaxOnly + ajaxIndex',
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + index',
				'conditions' => 'ajaxIndex + ajax'
			),
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + view',
				'conditions' => array(
					'update + put',
					'create + post'
				)
			),
		));
	}*/

	public function actionCreate(array $Message = array(), $ajax = null)
	{
		$message = new Message;
		$message->setAttributes($Message);
		if(isset($ajax))
		{
			$message->validate();
		}
		else
		{
			$message->save();
		}
		return $this->internalActionView($message, $this->getTranslateModule()->t($message->hasErrors() ? 'Error creating translation.' : 'Translation created.'));
	}
	
	public function actionUpdate(array $Message = array(), $ajax = null)
	{
		if(!isset($Message['id']) || !isset($Message['language_id']))
		{
			throw new CHttpException(400, $this->getTranslateModule()->t('The source message ID and language ID of the translation to be updated must be specified.'));
		}
		$messageModel = Message::model()->with('source')->findByPk(array('id' => $Message['id'], 'language_id' => $Message['language_id']));
	
		if($messageModel !== null)
		{
			$messageModel->setAttributes($Message);
			if(isset($ajax))
			{
				$messageModel->validate();
			}
			else
			{
				$messageModel->save();
			}
		}
	
		return $this->internalActionView($messageModel, $this->getTranslateModule()->t($messageModel->hasErrors() ? 'Error updating translation!' : 'Translation updated.'));
	}
	
	public function actionTranslate($id = null, $language_id = null)
	{
		$Message = $this->findModelByPk(array('with' => 'source'));
		if($Message === null)
		{
			$Message = new Message;
			$Message->id = $id;
			$Message->language_id = $language_id;
		}
		
		if(!isset($Message->language))
		{
			$Message->language = isset($Message->language_id) ? Language::model()->findByPk($Message->language_id) : new Language;
		}
		
		if(!isset($Message->source))
		{
			if(!isset($Message->id))
			{
				$Message->source = new MessageSource;
				$Message->source->language = new Language;
			}
			else
			{
				$Message->source = MessageSource::model()->with('language')->findByPk($Message->id);
			}
		}
		
		$status = array('status' => $Message->hasErrors() ? TController::NOTICE : TController::SUCCESS, 'message' => null);
		if(Yii::app()->getRequest()->getIsAjaxRequest())
		{
			$result = array('status' => $status);
			if($Message->hasErrors())
			{
				foreach($Message->getErrors() as $attribute => $errors)
				{
					$result[CHtml::activeId($Message, $attribute)] = $errors;
				}
			}
			else
			{
				$result['scenario'] = $Message->getScenario();
				$result['title'] = $this->getTranslateModule()->t(($Message->getIsNewRecord() ? 'Create' : 'Update').' Message Translation');
				$result['id'] = $Message->id;
				$result['message'] = $Message->source->message;
				$result['translation'] = $Message->translation;
				if($Message->getIsNewRecord())
				{
					foreach(Language::model()->with(array('missingTranslations' => $Message->id))->findAll() as $language)
					{
						$result['language_id'][$language->id] = array('text' => $language->name, 'selected' => false);
					}
					$result['language_id'][$Message->language_id]['selected'] = true;
				}
				else
				{
					$result['language_id'] = $Message->language_id;
					$result['language'] = $Message->language->getName();
				}
				$result['source_language_id'] = $Message->source->language_id;
				$result['source_language'] = $Message->source->language->getName();
			}
			echo function_exists('json_encode') ? json_encode($result) : CJSON::encode($result);
		}
		else
		{
			$this->render('view', array('Message' => $Message, 'MessageSource' => $Message->source, 'id' => $Message->getIsNewRecord() ? 'message-create-form' : 'message-update-form', 'clientOptions' => array('status' => $status)));
		}
	}

	/**
	 * Deletes a Message
	 * 
	 * @param integer $id The message's ID
	 * @param integer $languageId The ID of the message's language
	 */
	/*public function actionDelete(array $Message = array(), $dryRun = true)
	{
		$dryRun = true;

		$model = $this->getModel('search');
		$model->setAttributes($Message);
		$condition = $model->getSearchCriteria();

		$messagesDeleted = 0;
		
		$transaction = Message::model()->getDbConnection()->beginTransaction();
		try
		{
			if(empty($Message))
			{
				if($dryRun)
				{
					$messagesDeleted = Message::model()->count();
				}
				else 
				{
					$messagesDeleted = Message::model()->deleteAll();
				}
			}
			else
			{
				if($dryRun)
				{
					$messagesDeleted = $model->count($condition);
				}
				else
				{
					$primaryKeys = array();
					foreach($model->filter($model->findAll($condition)) as $record)
					{
						$primaryKeys[] = array('id' => $record['id'], 'language_id' => $record['language_id']);
					}
					$messagesDeleted = Message::model()->deleteByPk($primaryKeys);
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
				$dryRun ? $this->getTranslateModule()->t('This action will delete {messages} translations. Are you sure that you would like to continue?', array('{messages}' => $messagesDeleted)) : $this->getTranslateModule()->t('{messages} translations have been deleted.', array('{messages}' => $messagesDeleted)), 
				'index');
	}*/

}