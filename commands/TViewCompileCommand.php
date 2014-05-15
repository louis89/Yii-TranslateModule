<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TViewCompileCommand extends CConsoleCommand
{

	/**
	 * The regular expression used to extract translate tags inside views.
	 *
	 * @name TTranslator::TRANSLATE_TAG_REGEX
	 * @type string
	 * @const string
	 */
	const TRANSLATE_TAG_REGEX = '/\{t(?:\s+category\s*=\s*[\'"](.*?)[\'"])?(?:\s*params\s*=\s*([\'"].*?[\'"]\s*=>\s*[\'"].*?[\'"])+)?\}\s*(.+?)\s*\{\/t\}/s';

	/**
	 * The regular expression used to parse the params option of translate tags.
	 *
	 * @name TTranslator::PARAM_PARSE_REGEX
	 * @type string
	 * @const string
	 */
	const PARAM_PARSE_REGEX = '/[\'"](.*?)[\'"]=>[\'"](.*?)[\'"](?:\s*,\s*|\s*$)/';

	/**
	 * (non-PHPdoc)
	 * @see CConsoleCommand::defaultAction
	 */
	public $defaultAction = 'compileView';

	/**
	 * Strips all translate translate tags from a view file.
	 *
	 * @param string $sourcePath Path to view file with translate tags.
	 * @param string $compiledPath Path to generate new file at without translate tags.
	 * @param integer $filePermission File permissions for to set on the generated file.
	 * @throws Exception If any part of the tag stripping process fails.
	 */
	public function actionStripTags($sourcePath, $compiledPath, $filePermission = 0755)
	{
		try
		{
			if(is_dir($compiledPathDir = dirname($compiledPath)) === false)
			{
				if(mkdir($compiledPathDir, $filePermission, true) === false)
				{
					throw new CException(TranslateModule::translate("The compiled view directory '{dir}' does not exist and could not be created.", array('{dir}' => $compiledPathDir)));
				}
			}
				
			$sourceViewContents = file_get_contents($sourcePath);
			if($sourceViewContents === false)
			{
				throw new CException(TranslateModule::translate("Unable to read the contents of the source view at path '{path}'.", array('{path}' => $sourcePath)));
			}

			if(file_put_contents($compiledPath, preg_replace(self::TRANSLATE_TAG_REGEX, '$3', $sourceViewContents)) === false)
			{
				throw new CException(TranslateModule::translate("Failed to create compiled view file at path '{path}'.", array('{path}' => $compiledPath)));
			}
			@chmod($compiledPath, $filePermission);
		}
		catch(Exception $e)
		{
			if(file_exists($compiledPath))
			{
				unlink($compiledPath);
			}
			throw $e;
		}
	}

	/**
	 * Compiles a view file by translating the text between translate tags.
	 *
	 * @param string $sourcePath Path to view file with translate tags.
	 * @param string $compiledPath Path to generate new file at with text between translate tags translated and tags removed.
	 * @param string $route Request route that this compiled view should be associated with (roughly used to categorize compiled views).
	 * @param string $source The name of the translated messsage source component to use.
	 * @param string $language The language the source view should be translated to.
	 * @param integer $filePermission File permissions for to set on the generated file.
	 * @param boolean $useTransaction Whether all database queries should be wrapped in a transaction.
	 * @throws Exception If any part of the tag translation process fails.
	 */
	public function actionCompileView($sourcePath, $compiledPath, $route, $source, $language, $filePermission = 0755, $useTransaction = true)
	{
		$translateModule = TranslateModule::getModule();
		$viewSource = $translateModule->getViewSource();
		if($useTransaction)
		{
			$transaction = $viewSource->getDbConnection()->beginTransaction();
		}

		try
		{
			$view = $viewSource->getView($route, $sourcePath, $language, true);

			if($view['id'] === null)
			{
				throw new CException(TranslateModule::translate("The source view with path '{path}' could not be found or added to the view source.", array('{path}' => $sourcePath)));
			}

			if($route !== null && $view['route_id'] === null)
			{
				throw new CException(TranslateModule::translate("The source view with path '{path}' could not be associated with the route {route}.", array('{path}' => $sourcePath, '{route}' => $route)));
			}

			if($view['language_id'] === null)
			{
				throw new CException(TranslateModule::translate("The language '{language}' could not be found or added to the view source.", array('{language}' => $language)));
			}

			if($view['path'] === null)
			{
				if($viewSource->addView($view['id'], $compiledPath, $view['language_id']) === null)
				{
					Yii::log("The source view with source path '$sourcePath' and compiled path '$compiledPath' could not be added to the view source. This source view will be recompiled for each request until this problem is fixed...", CLogger::LEVEL_ERROR, TranslateModule::ID);
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
					throw new CException(TranslateModule::translate("The compiled view directory '{dir}' exists, but is not a directory. Your compiled view's path may be corrupted.", array('{dir}' => $compiledPathDir)));
				}
				else if(mkdir($compiledPathDir, $filePermission, true) === false)
				{
					throw new CException(TranslateModule::translate("The compiled view directory '{dir}' does not exist and could not be created.", array('{dir}' => $compiledPathDir)));
				}
			}

			$subject = file_get_contents($sourcePath);
			if($subject === false)
			{
				throw new CException(TranslateModule::translate("Unable to read the contents of the source view at path '{path}'.", array('{path}' => $sourcePath)));
			}

			// Extract messages
			preg_match_all(self::TRANSLATE_TAG_REGEX, $subject, $messages);

			$messageSource = $translateModule->getMessageSource();

			// Load view's messages
			$unconfirmedMessages = array();
			foreach($viewSource->getViewMessages($view['id']) as $message)
			{
				$unconfirmedMessages[$message['message']] = $message['id'];
			}

			// Translate the messages
			$confirmedMessages = array();
			foreach($messages[3] as $i => &$message)
			{
				if(!isset($confirmedMessages[$message]))
				{
					if(isset($unconfirmedMessages[$message]))
					{
						$confirmedMessages[$message] = $unconfirmedMessages[$message];
						unset($unconfirmedMessages[$message]);
					}
					else
					{
						if($confirmedMessages[$message] = $viewSource->addSourceMessageToView($view['id'], $message, true) === null)
						{
							Yii::log("Failed to add source message '$message' to view with id '{$view['id']}'.", CLogger::LEVEL_ERROR, TranslateModule::ID);
						}
					}
				}
				// extract parameters into their keys and values
				preg_match_all(self::PARAM_PARSE_REGEX, $messages[2][$i], $params);
				$message = Yii::t($messages[1][$i] === '' ? $messageSource->messageCategory : $messages[1][$i], $message, @array_combine($params[1], $params[2]), $source, $language);
			}

			$viewSource->deleteViewMessages($view['id'], $unconfirmedMessages);

			// Replace messages with respective translations in source and write to compiled path.
			if(file_put_contents($compiledPath, str_replace($messages[0], $messages[3], $subject)) === false)
			{
				throw new CException(TranslateModule::translate("Failed to create translated view file at path '{path}'.", array('{path}' => $compiledPath)));
			}
			@chmod($compiledPath, $filePermission);

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
				unlink($compiledPath);
			}
			if(isset($transaction))
			{
				$transaction->rollback();
			}
			throw $e;
		}
	}

}