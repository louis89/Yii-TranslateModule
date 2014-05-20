<?php

Yii::import(TranslateModule::ID.'.components.TViewRenderer');

class TViewTranslator extends TViewRenderer
{

	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component is responsible for rendering views containing message translation tags like "{t}some text to be translated{/t}". The message translation tags will be replaced by their respective translations such that "{t}message to be translated{/t}" becomes "the translated message".');
	}
	
	/**
	 * Parses the source view file and saves the results as another file.
	 * This method is required by the parent class.
	 * @param string $sourcePath the source view file path
	 * @param string $compiledPath the resulting view file path
	 * @param string $route The route that requested this view.
	 * If not set the route name 'default' will be used.
	 * @param string $source The name of the messsage source component to use.
	 * If not set the message source component name at {@link TTranslator::messageSource} will be used.
	 * @param string $language The language that this view is being translated to.
	 * If not set the application's current language will be used.
	 * @param boolean $useTransaction If true all database queries will be wrapped in a transaction.
	 */
	public function generateViewFile($sourcePath, $compiledPath, $route = null, $language = null, $useTransaction = true)
	{
		if(($mutex = $this->getTranslateModule()->getMutex()) !== null)
		{
			$mutex->acquire($sourcePath.'.'.$compiledPath);
		}
		if($language === null)
		{
			$language = Yii::app()->getLanguage();
		}
		$viewSource = $this->getViewSource();

		if($useTransaction)
		{
			$transaction = $viewSource->getDbConnection()->beginTransaction();
		}
		
		try
		{
			$view = $viewSource->getView($route, $sourcePath, $language, true);
		
			if($view['id'] === null)
			{
				throw new CException($this->getTranslateModule()->t("The source view with path '{path}' could not be found or added to the view source.", array('{path}' => $sourcePath)));
			}
		
			if($route !== null && $view['route_id'] === null)
			{
				throw new CException($this->getTranslateModule()->t("The source view with path '{path}' could not be associated with the route {route}.", array('{path}' => $sourcePath, '{route}' => $route)));
			}
		
			if($view['language_id'] === null)
			{
				throw new CException($this->getTranslateModule()->t("The language '{language}' could not be found or added to the view source.", array('{language}' => $language)));
			}
		
			if($view['path'] === null)
			{
				if($viewSource->addView($view['id'], $compiledPath, $view['language_id']) === null)
				{
					Yii::log("The source view with source path '$sourcePath' and compiled path '$compiledPath' could not be added to the view source. This source view will be recompiled for each request until this problem is fixed...", CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
				}
			}
			else if($view['path'] !== $compiledPath && file_exists($view['path']))
			{
				unlink($view['path']);
			}
		
			if(is_dir($compiledPathDir = dirname($compiledPath)) === false)
			{
				if(file_exists($compiledPathDir))
				{
					throw new CException($this->getTranslateModule()->t("The compiled view directory '{dir}' exists, but is not a directory. Your compiled view's path may be corrupted.", array('{dir}' => $compiledPathDir)));
				}
				else if(@mkdir($compiledPathDir, $this->directoryPermission, true) === false)
				{
					throw new CException($this->getTranslateModule()->t("The compiled view directory '{dir}' does not exist and could not be created.", array('{dir}' => $compiledPathDir)));
				}
			}
		
			$subject = @file_get_contents($sourcePath);
			if($subject === false)
			{
				throw new CException($this->getTranslateModule()->t("Unable to read the contents of the source view at path '{path}'.", array('{path}' => $sourcePath)));
			}
		
			// Extract messages
			preg_match_all(self::TRANSLATE_TAG_REGEX, $subject, $messages);
		
			// Load view's messages
			$unconfirmedMessages = array();
			foreach($viewSource->getViewMessages($view['id']) as $message)
			{
				$unconfirmedMessages[$message['message']] = $message['id'];
			}
		
			// Translate the messages
			$confirmedMessages = array();
			foreach($messages[5] as $i => &$message)
			{
				// extract translate parameters
				preg_match_all(self::PARAM_PARSE_REGEX, $messages[2][$i], $params);
				$paramCount = min(count($params[1]), count($params[2]));
				$category = $messages[1][$i] === '' ? null : $messages[1][$i];
				$params = array_combine(array_slice($params[1], 0, $paramCount), array_slice($params[2], 0, $paramCount));
				$source = $messages[3][$i] === '' ? null : $messages[3][$i];
				$lang = $messages[4][$i] === '' ? $language : $messages[4][$i];
				if(!isset($confirmedMessages[$message])) // If the message has not been confirmed as being in this view
				{
					if(isset($unconfirmedMessages[$message])) // If the message was previously unconfirmed for this view then confirm it and move on.
					{
						$confirmedMessages[$message] = $unconfirmedMessages[$message];
						unset($unconfirmedMessages[$message]);
					}
					else // If the message was not pending confirmation then it is not known to be in this view so add it to this view and confirm it.
					{
						if($confirmedMessages[$message] = $viewSource->addSourceMessageToView($view['id'], $category, $message, true) === null)
						{
							Yii::log("Failed to add source message '$message' to view with id '{$view['id']}'.", CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
						}
					}
				}
				// Translate the message
				$message = Yii::t($category, $message, $params, $source, $lang);
			}
		
			$viewSource->deleteViewMessages($view['id'], $unconfirmedMessages);
		
			// Replace messages with respective translations in source and write to compiled path.
			if(@file_put_contents($compiledPath, str_replace($messages[0], $messages[5], $subject)) === false)
			{
				throw new CException($this->getTranslateModule()->t("Failed to create translated view file at path '{path}'.", array('{path}' => $compiledPath)));
			}
			@chmod($compiledPath, $this->filePermission);
		
			// Update the created time for the view.
			$viewSource->updateView($view['id'], $view['language_id'], time(), $compiledPath);
		
			if(isset($transaction))
			{
				$transaction->commit();
			}
		}
		catch(Exception $e)
		{
			if(file_exists($compiledPath))
			{
				@unlink($compiledPath);
			}
			if(isset($transaction))
			{
				$transaction->rollback();
			}
			if($mutex !== null)
			{
				$mutex->release();
			}
			throw $e;
		}
		if($mutex !== null)
		{
			$mutex->release();
		}
	}

}