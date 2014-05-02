<?php

Yii::import(TranslateModule::ID.'.widgets.configurationStatus.ConfigurationStatus');
Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class DefaultTranslator extends CApplicationComponent implements ConfigurationStatus, ITranslateModuleComponent
{

	/**
	 * @var string The name of the variable to use for saving and retrieving language settings for a client.
	*/
	public $languageVarName = 'language';

	/**
	 * @var integer time in seconds to store language setting in cookie. Defaults to 63072000 seconds (2 Years).
	 */
	public $cookieExpire = 63072000; // 2 Years

	/**
	 * @var boolean Whether to define a global translate function called 't' to simplify translating messages.
	 */
	public $defineGlobalFunction = true;

	/**
	 * @var boolean Whether to use database transactions when updating the database.
	 */
	public $useTransaction = true;
	
	/**
	 * @var boolean If True the translator will automatically attempt to translate messages otherwise the translator will simply store the message in the missing translations collection for the current request. Defaults to True.
	 */
	public $autoTranslate = true;
	
	private $_translateModuleID;

	/**
	 * @var array $_messages contains the untranslated messages found during the current request
	 * */
	private $_messages = array();

	/**
	 * Initialize the translate component.
	 *
	 * If {@see TTranslator::defineGlobalTranslateFunction} is set to true a global function called 't' will be defined to simplify message translations
	 * over the built in {@see Yii::t()} function.
	 *
	 * @see CApplicationComponent::init()
	*/
	public function init()
	{
		if($this->getTranslateModuleID() === null)
		{
			$this->setTranslateModuleID(TranslateModule::ID);
		}
		if($this->defineGlobalFunction)
		{
			function t($message, $params = array(), $category = null)
			{
				return Yii::t($category, $message, $params);
			}
		}
		return parent::init();
	}
	
	public function attributeNames()
	{
		return array(
			'translateModuleID',
			'languageVarName',
			'cookieExpire',
			'defineGlobalFunction',
			'useTransaction',
			'autoTranslate',
			'cacheDuration',
		);
	}
	
	public function attributeRules()
	{
		return array(
			array('languageVarName, translateModuleID', 'required'),
			array('languageVarName, translateModuleID', 'length', 'allowEmpty' => false),
			array('cookieExpire, cacheDuration', 'numerical', 'allowEmpty' => false, 'integerOnly' => true, 'min' => 0),
			array('defineGlobalFunction, useTransaction, autoTranslate', 'boolean', 'allowEmpty' => false, 'trueValue' => true, 'falseValue' => false, 'strict' => true, 'message' => '{attribute} must strictly be a boolean value of either "true" or "false".')
		);
	}
	
	public function attributeLabels()
	{
		$module = $this->getTranslateModule();
		return array(
			'languageVarName' => $module->t('Language Variable Name'),
			'cookieExpire' => $module->t('Language Cookie Expire Time'),
			'defineGlobalFunction' => $module->t('Define a global translate function'),
			'useTransaction' => $module->t('Use Database Transactions'),
			'cacheDuration' => $module->t('Caching Duration'),
			'autoTranslate' => $module->t('Translator Enabled'),
		);
	}
	
	public function attributeDescriptions()
	{
		$module = $this->getTranslateModule();
		return array(
			'languageVarName' => $module->t('The name of the request variable that specifies the language of the current request. Defaults to "language".'),
			'cookieExpire' =>  $module->t('Expire time in seconds of the cookie specifying the client\'s preferred language. Defaults to 2 years.'),
			'defineGlobalFunction' => $module->t('If True a global translate function "{t}" will be defined to help simplify translating messages. Defaults to True.', array('{t}' => 't()')),
			'useTransaction' => $module->t('If True all database commands necessary for translating a message will be wrapped in a transaction. Defaults to True.'),
			'cacheDuration' => $module->t('Time in seconds to cache locale display names. Defaults to 0 meaning do not cache.'),
			'autoTranslate' => $module->t('If True the translator will automatically attempt to translate messages, otherwise the translator will simply store the message in the missing translations collection for the current request. Defaults to True.'),
		);
	}
	
	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component is the default translator. No message translations will be performed, but all messages and their related attributes will be stored in the configured message source component if autoTranslate is enabled. Extend this class and override the "translate" method to define how messages should be translated.');
	}
	
	public function getTranslateModule()
	{
		return TranslateModule::findModule($this->getTranslateModuleID());
	}
	
	public function getTranslateModuleID()
	{
		return $this->_translateModuleID;
	}
	
	public function setTranslateModuleID($translateModuleID = TranslateModule::ID)
	{
		if($this->getTranslateModuleID() !== null)
		{
			try
			{
				$messageSource = $this->getTranslateModule()->getMessageSource();
			}
			catch(Exception $e)
			{
				$messageSource = null;
			}
			if($this->_messageSource !== null)
			{
				$this->_messageSource->detachEventHandler('onMissingTranslation', array($this, 'missingTranslation'));
				$this->_messageSource = null;
			}
		}
		$this->_translateModuleID = $translateModuleID;
		try
		{
			$messageSource = $this->getTranslateModule()->getMessageSource();
		}
		catch(Exception $e)
		{
			$messageSource = null;
		}
		if($messageSource !== null)
		{
			$messageSource->attachEventHandler('onMissingTranslation', array($this, 'missingTranslation'));
		}
	}

	/**
	 * Performs the following actions with the langauge parameter:
	 * Sets the application's current language.
	 * Sets php's current language.
	 * Creates a session variable containing the current language's value.
	 * Creates a cookie containing the current language value.
	 *
	 * @param string $language The language ID
	 */
	public function setLanguage($language)
	{
		Yii::app()->setLanguage($language);
		setLocale(LC_ALL, $language.'.'.Yii::app()->charset);
		Yii::app()->getUser()->setState($this->languageVarName, $language);
		Yii::app()->getRequest()->getCookies()->add($this->languageVarName, new CHttpCookie($this->languageVarName, $language, array('expire' => time() + $this->cookieExpire)));
	}

	/**
	 * Get whether there are missing translations for the current request.
	 *
	 * @return boolean true if missing translations exist for the current request. False otherwise.
	 */
	public function hasMissingTranslations()
	{
		return !empty($this->_messages);
	}
	
	public function getMissingTranslations()
	{
		return $this->_messages;
	}
	
	/**
	 * @return boolean True if the view renderer is correctly configured. False otherwise.
	 */
	public function isViewRendererConfigured()
	{
		return Yii::app()->getViewRenderer() instanceof TViewRenderer;
	}

	/**
	 * method that handles {@link CMissingTranslationEvent}s
	 *
	 * @param CMissingTranslationEvent $event
	 */
	public function missingTranslation($event)
	{
		if($this->autoTranslate)
		{
			$event->message = $this->getTranslation($event->category, $event->message, $event->language, $event->sender, $this->useTransaction);
		}
		else 
		{
			$this->addMissingTranslation($event->category, $event->message, $event->sender->getLanguage($event->category), $event->language);
		}
	}
	
	/**
	 * Attempts to translate a message.
	 *
	 * @param string $category The category the message should be associated with
	 * @param string $message The message to be translated
	 * @param string $language The language the message should be translate to
	 * @param TDbMessageSource $source The message source to use.
	 * @param bool $useTransaction If true a transaction will be used when updating the database entries for this category, message, language, translation.
	 * @throws CException If the source message, source message category, language, or translation could not be added to the message source.
	 * @return string The translation for the message or the message it self if either the translation failed or the target language was the same as source language.
	 */
	public function getTranslation($category, $message, $language, $source, $useTransaction = true)
	{
		if($message !== '' && $language !== ($sourceLanguage = $source->getLanguage($category)))
		{
			if(($mutex = $this->getTranslateModule()->getMutex()) !== null)
			{
				$mutex->acquire($category.'.'.$language.'.'.$message);
			}
			if($useTransaction && $source instanceof CDbMessageSource && $source->getDbConnection()->getCurrentTransaction() === null)
			{
				$transaction = $source->getDbConnection()->beginTransaction();
			}
				
			try
			{
				if($source instanceof TDbMessageSource)
				{
					$translation = $source->getTranslation($category, $message, $language, true);
	
					if($translation['id'] === null)
					{
						throw new CException("Source message '$message' could not be found or added to the message source.");
					}
	
					if($translation['category_id'] === null)
					{
						throw new CException("The category '$category' was not found or could not be associated with the source message '$message'.");
					}
	
					if($translation['language_id'] === null)
					{
						throw new CException("The language '$language' could not be found or added to the message source.");
					}
				}
				else
				{
					$translation = array('translation' => null);
				}
				if($translation['translation'] === null)
				{
					$translation['translation'] = $this->translate($message, $sourceLanguage, $language);
					if($translation['translation'] === false)
					{
						Yii::log(get_class($this).' failed to translate message.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
						$this->addMissingTranslation($category, $message, $source->getLanguage(), $language);
					}
					else
					{
						$message = trim((string)$translation['translation']);
						if($source instanceof TDbMessageSource)
						{
							if($source->addTranslation($translation['id'], $language, $message, true) === null)
							{
								throw new CException("Translation '$message' could not be added to the message source");
							}
						}
					}
				}
				else
				{
					$message = $translation['translation'];
				}
				if(isset($transaction))
				{
					$transaction->commit();
				}
			}
			catch(Exception $e)
			{
				if(isset($transaction))
				{
					$transaction->rollback();
				}
				if(isset($mutex))
				{
					$mutex->release();
				}
				$this->addMissingTranslation($category, $message, $source->getLanguage(), $language);
				throw $e;
			}
			if(isset($mutex))
			{
				$mutex->release();
			}
		}
			
		return $message;
	}
	
	/**
	 * Translate a message of a particular source language to a particular target language.
	 * This method should return the translated message on success and False on failure.
	 * Default implementation always returns false. 
	 * To implement specific translation functionality extends this class and override this method. 
	 * 
	 * @param string $message source message
	 * @param string $sourceLanguage source message's language
	 * @param string $targetLanguage language the source message should be translated to
	 * @return boolean|string False if the message could not be translated. Otherwise the translated message string. Default always returns False.
	 */
	public function translate($message, $sourceLanguage, $targetLanguage)
	{
		return false;
	}

	/**
	 * Adds a message to the list of missing translations for this request.
	 * 
	 * @param string $category The missing source message's category
	 * @param string $message The missing source message
	 * @param string $sourceLanguage This missing source message's source language
	 * @param string $targetLanguage The missing translation's language
	 */
	protected function addMissingTranslation($category, $message, $sourceLanguage, $targetLanguage)
	{
		$this->_messages[md5($category.$message.$sourceLanguage.$targetLanguage)] = array('category' => $category, 'message' => $message, 'sourceLanguage' => $sourceLanguage, 'targetLanguage' => $targetLanguage);
	}

}
