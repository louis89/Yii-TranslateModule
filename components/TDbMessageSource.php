<?php

Yii::import(TranslateModule::ID.'.widgets.configurationStatus.ConfigurationStatus');
Yii::import(TranslateModule::ID.'.widgets.configurationStatus.Installable');
Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class TDbMessageSource extends CDbMessageSource implements ConfigurationStatus, Installable, ITranslateModuleComponent
{

	/**
	 * @var string the name of the language table. Defaults to 'translate_language'.
	 */
	public $languageTable = '{{translate_language}}';

	/**
	 * @var string the name of the accepted language table. Defaults to 'translate_accepted_language'.
	 */
	public $acceptedLanguageTable = '{{translate_accepted_language}}';

	/**
	 * @var string the name of the category table. Defaults to 'translate_category'.
	 */
	public $categoryTable = '{{translate_category}}';

	/**
	 * @var string the name of the message category table. Defaults to 'translate_category_message'.
	 */
	public $categoryMessageTable = '{{translate_category_message}}';

	/**
	 * @var string The default category to assign messages when the category supplied via the translate functions is an empty string.
	 */
	public $defaultMessageCategory = TranslateModule::ID;
	
	/**
	 * @var boolean Use generic locales. If True and the current language and source language are locale IDs they will be stripped to the language ID portion only.
	 */
	public $genericLocale = true;
	
	/**
	 * @var boolean Whether to trim translation attributes before attempting to perform the translation
	 */
	public $trim = true;

	/**
	 * @var boolean If true each call to the translate method will be profiled.
	 */
	public $enableProfiling = false;
	
	public $acceptedLanguagesOnly = false;
	
	/**
	 * @var boolean If true the translations returned by onMissingTranslation events will be immediately cached locally.
	 * This is different than Yii's normal behavior. Normally Yii will fire an onMissingTranslation event everytime a mising message is 
	 * encountered during the current request whether it was translated successfully or not. This can slow things down significantly if messages
	 * keep failing to translate and repeat many times in the current request. It is recommended to leave this enabled unless you absolutely need this 
	 * message source to behave like a default message source would when firing onMissingTranslation events. Defaults to True.
	 */
	public $cacheMissingLocally = true;
	
	private $_translateModuleID = TranslateModule::ID;

	/**
	 * @var array Cached message translation in the format 'message' => 'translation'
	 */
	private $_messages = array();
	
	private $_acceptedLanguageNames = array();
	
	public function attributeNames()
	{
		return array(
			'sourceMessageTable',
			'translatedMessageTable',
			'languageTable',
			'acceptedLanguageTable',
			'categoryTable',
			'categoryMessageTable',
			'defaultMessageCategory',
			'enableProfiling',
			'cacheMissingLocally',
			'genericLocale',
			'trim',
			'translateModuleID',
			'connectionID',
			'cachingDuration',
			'cacheID',
			'forceTranslation',
			'acceptedLanguagesOnly',
			'language',
		);
	}
	
	public function attributeRules()
	{
		return array(
			array('sourceMessageTable, translatedMessageTable, languageTable, acceptedLanguageTable, categoryTable, categoryMessageTable, translateModuleID, connectionID, defaultMessageCategory, language, cacheMissingLocally', 'required'),
			array('sourceMessageTable, translatedMessageTable, languageTable, acceptedLanguageTable, categoryTable, categoryMessageTable', 'translate.validators.DbTableExistsValidator', 'dbSchema' => $this->getDbConnection()->getSchema()),
			array('connectionID', 'translate.validators.ComponentValidator', 'type' => 'CDbConnection'),
			array('cacheID', 'translate.validators.ComponentValidator', 'type' => 'ICache', 'allowEmpty' => false),
			array('defaultMessageCategory, language', 'length', 'allowEmpty' => false),
			array('enableProfiling, genericLocale, forceTranslation, acceptedLanguagesOnly, trim, cacheMissingLocally', 'boolean', 'allowEmpty' => false, 'trueValue' => true, 'falseValue' => false, 'strict' => true, 'message' => '{attribute} must strictly be a boolean value of either "true" or "false".')
		);
	}
	
	public function attributeLabels()
	{
		$module = $this->getTranslateModule();
		return array(
			'sourceMessageTable' => $module->t('Source Message Table'),
			'translatedMessageTable' => $module->t('Translated Message Table'),
			'languageTable' => $module->t('Language Table'),
			'acceptedLanguageTable' => $module->t('Accepted Language Table'),
			'categoryTable' => $module->t('Category Table'),
			'categoryMessageTable' => $module->t('Category Message Table'),
			'defaultMessageCategory' => $module->t('Default Message Category'),
			'enableProfiling' => $module->t('Enable Profiling'),
			'genericLocale' => $module->t('Use Generic Locales'),
			'trim' => $module->t('Trim'),
			'connectionID' => $module->t('Database Connection ID'),
			'cachingDuration' => $module->t('Caching Duration'),
			'cacheID' => $module->t('Cache Component ID'),
			'forceTranslation' => $module->t('Force Translations'),
			'acceptedLanguagesOnly' => $module->t('Translate Only Accepted Languages'),
			'language' => $module->t('Language'),
			'cacheMissingLocally' => $module->t('Cache Missing Translations Locally')
		);
	}

	public function attributeDescriptions()
	{
		$module = $this->getTranslateModule();
		return array(
			'sourceMessageTable' => $module->t('The database table containing source messages. Defaults to "SourceMessage".'),
			'translatedMessageTable' => $module->t('The database table containing message translations. Defaults to "Message".'),
			'languageTable' => $module->t('The database table containing languages. Defaults to "{{translate_language}}".'),
			'acceptedLanguageTable' => $module->t('The database table containing accepted languages. Defaults to "{{translate_accepted_language}}".'),
			'categoryTable' => $module->t('The database table containing message categories. Defaults to "{{translate_category}}".'),
			'categoryMessageTable' => $module->t('The database table containing the links between source messages and message categories. Defaults to "{{translate_category_message}}".'),
			'defaultMessageCategory' => $module->t('The default category to assign messages when the category parameter of the translate function is specified as null. Defaults to the Translate Module\'s ID.'),
			'enableProfiling' => $module->t('If True each translation attempt will be profiled. Defaults to False.'),
			'genericLocale' => $module->t('If True the locale protion of requested languages (if specified) will be stripped off and only the generic language code will be considered when translating messages. Defaults to True.'),
			'trim' => $module->t('If True then all attributes passed to this source\'s translate function will have any whitespace trimmed before an attempt to translate the message is made. Defaults to True.'),
			'connectionID' => $module->t('The name of the database connection component. Defaults to "db"'),
			'cachingDuration' => $module->t('The time in seconds to cache translated messages in the caching component. Defaults to 0 meaning do not cache.'),
			'cacheID' => $module->t('The name of the caching component for caching message translations. Defaults to "cache"'),
			'forceTranslation' => $module->t('If True an attempt to translate every message will be made, regardless of whther the language is accepted or the source message\'s source language is the same as the target language. Defaults to False.'),
			'acceptedLanguagesOnly' => $module->t('If True messages will only be translated if their target language is an accepted language. Defaults to False.'),
			'language' => $module->t('The language of the messages translated by this source. Defaults to the application\'s source language.'),
			'cacheMissingLocally' => $module->t('If True the translations returned by onMissingTranslation events will be immediately cached locally. This is different than Yii\'s normal behavior. Normally Yii will fire an onMissingTranslation event everytime a mising message is encountered during the current request, regardless of whether it was previously translated successfully or not. This can slow things down significantly if messages keep failing to translate and are used repeatedly throughout the current request. It is recommended to leave this enabled unless you absolutely need this message source to behave like a Yii\'s default message source would. Defaults to True.')
		);
	}
	
	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component is a source of messages, and their respective translations, categories, and languages. This source is mutable. Records may be added, removed, or retrieved using this source.');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITMessageSource::getIsInstalled()
	 */
	public function getIsInstalled()
	{
		return ($db = $this->getDbConnection()) !== null &&
			($schema = $db->getSchema()) !== null && 
			$schema->getTable($this->languageTable) !== null &&
			$schema->getTable($this->acceptedLanguageTable) !== null &&
			$schema->getTable($this->categoryTable) !== null &&
			$schema->getTable($this->categoryMessageTable) !== null &&
			$schema->getTable($this->sourceMessageTable) !== null &&
			$schema->getTable($this->translatedMessageTable) !== null;
	}
	
	public function installAttributeRules()
	{
		return array(
			array('sourceMessageTable, translatedMessageTable, languageTable, acceptedLanguageTable, categoryTable, categoryMessageTable, connectionID', 'required'),
			array('connectionID', 'translate.validators.ComponentValidator', 'type' => 'CDbConnection'),
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITMessageSource::install()
	 */
	public function install($reinstall = false)
	{
		if(!$reinstall && $this->getIsInstalled())
		{
			return Installable::OVERWRITE; // Already installed
		}
		
		$tableNames = array(
			$this->languageTable => $this->languageTable,
			$this->acceptedLanguageTable => $this->acceptedLanguageTable,
			$this->categoryTable => $this->categoryTable,
			$this->categoryMessageTable => $this->categoryMessageTable,
			$this->sourceMessageTable => $this->sourceMessageTable,
			$this->translatedMessageTable => $this->translatedMessageTable);
		
		$db = $this->getDbConnection();
		if($db->tablePrefix !== null)
		{
			foreach($tableNames as &$name)
			{
				if(!is_string($name) || $name === '')
				{
					return Installable::ERROR;
				}
				else if(strpos($name, '{{') !== false)
				{
					$name = preg_replace('/\{\{(.*?)\}\}/', $db->tablePrefix.'$1', $name);
				}
			}
		}
		
		$transaction = $db->beginTransaction();
		$schema = $db->getSchema();
		try
		{
			$schema->checkIntegrity(false);
			$sql = '';
			// Drop the tables if they exist
			foreach($tableNames as $table)
			{
				if(($table = $schema->getTable($table)) !== null)
				{
					$sql .= $schema->dropTable($table->name).';';
				}
			}
		
			// Create tables
			$sql .= $schema->createTable(
				$tableNames[$this->languageTable],
				array(
					'id' => 'pk',
					'code' => 'varchar(16) NOT NULL',
					'UNIQUE KEY '.$schema->quoteColumnName('code').' ('.$schema->quoteColumnName('code').')'
				)
			).';';
			
			$sql .= $schema->createTable(
				$tableNames[$this->acceptedLanguageTable],
				array(
					'id' => 'pk',
				)
			).';';
		
			$sql .= $schema->createTable(
				$tableNames[$this->categoryTable],
				array(
					'id' => 'pk',
					'category' => 'varchar(255) NOT NULL',
					'UNIQUE KEY '.$schema->quoteColumnName('category').' ('.$schema->quoteColumnName('category').')'
				)
			).';';
		
			$sql .= $schema->createTable(
				$tableNames[$this->categoryMessageTable],
				array(
					'category_id' => 'integer NOT NULL',
					'message_id' => 'integer NOT NULL',
					'PRIMARY KEY ('.$schema->quoteColumnName('category_id').','.$schema->quoteColumnName('message_id').'),'.
					'KEY '.$schema->quoteColumnName('message_id').' ('.$schema->quoteColumnName('message_id').')'
				)
			).';';
				
			$sql .= $schema->createTable(
				$tableNames[$this->sourceMessageTable],
				array(
					'id' => 'pk',
					'language_id' => 'integer NOT NULL',
					'message' => 'text',
					'KEY '.$schema->quoteColumnName('language_id').' ('.$schema->quoteColumnName('language_id').')'
				)
			).';';
				
			$sql .= $schema->createTable(
				$tableNames[$this->translatedMessageTable],
				array(
					'id' => 'integer NOT NULL',
					'language_id' => 'integer NOT NULL',
					'translation' => 'text',
					'last_modified' => 'integer NOT NULL',
					'PRIMARY KEY ('.$schema->quoteColumnName('id').','.$schema->quoteColumnName('language_id').'),'.
					'KEY '.$schema->quoteColumnName('last_modified').' ('.$schema->quoteColumnName('last_modified').'),'.
					'KEY '.$schema->quoteColumnName('language_id').' ('.$schema->quoteColumnName('language_id').')'
				)
			).';';
		
			// Add foreign key constraints
			$sql .= $schema->addForeignKey(
				$tableNames[$this->acceptedLanguageTable].'_fk_1',
				$tableNames[$this->acceptedLanguageTable],
				'id',
				$tableNames[$this->languageTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->categoryMessageTable].'_fk_1',
				$tableNames[$this->categoryMessageTable],
				'message_id',
				$tableNames[$this->sourceMessageTable],
				'id',
				'CASCADE',
				'CASCADE').';';
		
			$sql .= $schema->addForeignKey(
				$tableNames[$this->categoryMessageTable].'_fk_2',
				$tableNames[$this->categoryMessageTable],
				'category_id',
				$tableNames[$this->categoryTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->translatedMessageTable].'_fk_1',
				$tableNames[$this->translatedMessageTable],
				'id',
				$tableNames[$this->sourceMessageTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->translatedMessageTable].'_fk_2',
				$tableNames[$this->translatedMessageTable],
				'language_id',
				$tableNames[$this->languageTable],
				'id',
				'CASCADE',
				'CASCADE').';';
			
			$sql .= $schema->addForeignKey(
				$tableNames[$this->sourceMessageTable].'_fk_2',
				$tableNames[$this->sourceMessageTable],
				'language_id',
				$tableNames[$this->languageTable],
				'id',
				'CASCADE',
				'CASCADE').';';
		
			$db->createCommand($sql)->execute();
		
			$schema->checkIntegrity(true);
				
			$transaction->commit();
		}
		catch(Exception $ex)
		{
			$transaction->rollback();
			return Installable::ERROR;
		}
		
		return Installable::SUCCESS;
	}
	
	public function getTranslateModuleID()
	{
		return $this->_translateModuleID;
	}
	
	public function setTranslateModuleID($translateModuleID = TranslateModule::ID)
	{
		$this->_translateModuleID = $translateModuleID;
	}
	
	public function getTranslateModule()
	{
		return TranslateModule::findModule($this->getTranslateModuleID());
	}
	
	/**
	 * Get the list of 'accepted' languages.
	 *
	 * @return array An array of the accepted languages in the form of 'ID' => 'display name or ID if display name could not be determined'.
	 */
	public function getAcceptedLanguageNames()
	{
		$cacheKey = $this->getTranslateModuleID() . '-cache-accepted-languages-' . Yii::app()->getLanguage();
		if(!isset($this->_acceptedLanguageNames[$cacheKey]))
		{
			if(($cache = $this->getCache()) === null || ($languages = $cache->get($cacheKey)) === false)
			{
				$languageDisplayNames = $this->getTranslateModule()->getTranslator()->getLanguageDisplayNames();
				$sourceLanguage = $this->getLanguage();
				$languages[$sourceLanguage] = isset($languageDisplayNames[$sourceLanguage]) ? $languageDisplayNames[$sourceLanguage] : $sourceLanguage;
				foreach($this->getAcceptedLanguages() as $lang)
				{
					$languages[$lang['code']] = $languageDisplayNames[$lang['code']];
				}
				asort($languages, SORT_LOCALE_STRING);
				if($cache !== null)
				{
					$cache->set($cacheKey, $languages, $this->cacheDuration);
				}
			}
			$this->_acceptedLanguageNames[$cacheKey] = $languages;
		}
		return $this->_acceptedLanguageNames[$cacheKey];
	}
	
	/**
	 * Get whether a language ID is an 'accepted' language ID.
	 *
	 * @param string $languageId The language ID
	 * @return boolean true if the language ID is an accepted language ID false otherwise.
	 */
	public function isAcceptedLanguage($languageId)
	{
		return array_key_exists($languageId, $this->getAcceptedLanguageNames());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CMessageSource::getLanguage()
	 */
	public function getLanguage($category = null)
	{
		$language = $this->getTranslateModule()->tCategory === $category ? TranslateModule::LANGUAGE : parent::getLanguage();
		return $this->genericLocale ? Yii::app()->getLocale()->getLanguageID($language) : $language;
	}
	
	/**
	 * Gets whether caching is enabled for this message source.
	 *
	 * @return boolean True if caching is enabled false otherwise.
	 */
	public function getIsCachingEnabled()
	{
		return $this->getCache() !== null;
	}
	
	/**
	 * Flushes the cache component used by this message source if it is configured.
	 * 
	 * @return boolean True on success. False on failure.
	 */
	public function flushCache()
	{
		return ($cache = $this->getCache()) !== null && $cache->flush();
	}
	
	/**
	 * Deletes all cached messages for a specified category and language. 
	 * This should be called if a translation changes while caching is enabled and you would like to change to take effect immediately.
	 *
	 * @param string $category the category.
	 * @param string $language the language.
	 * @return boolean True on success. False on failure.
	 */
	public function deleteCache($category, $language)
	{
		return ($cache = $this->getCache()) !== null && $cache->delete($this->getCacheKey($category, $language));
	}

	/**
	 * Returns the cache component used by this message source or null if no cache has been configured.
	 * 
	 * @return ICache The cache component used by this message source or null if no cache has been configured.
	 */
	protected function getCache()
	{
		return $this->cachingDuration > 0 && $this->cacheID !== false ? Yii::app()->getComponent($this->cacheID) : null;
	}

	/**
	 * Returns the string ID of the message source component used by this message source.
	 * 
	 * @param string $category
	 * @param string $language
	 * @return string The ID of the cache to use for this message source.
	 */
	protected function getCacheKey($category, $language)
	{
		return $this->getTranslateModuleID().'.'.$this->getLanguage($category).'.messages.'.$category.'.'.$language;
	}

	/**
	 * Loads message translations for the specified language and category.
	 * @param string $category the message category
	 * @param string $language the target language
	 * @return array the loaded messages
	 */
	protected function loadMessages($category, $language)
	{
		if(($cache = $this->getCache()) !== null)
		{
			$key = $this->getCacheKey($category, $language);
			$messages = $cache->get($key);
			if($messages === false)
			{
				$messages = $this->loadMessagesFromDb($category, $language);
				$cache->set($key, $messages, $this->cachingDuration);
			}
		}
		else
		{
			$messages = $this->loadMessagesFromDb($category, $language);
		}

		return $messages;
	}

	/**
	 * Loads message translations for the specified language and category from the database.
	 * You may override this method to customize the message storage in the database.
	 * @param string $category the message category
	 * @param string $language the target language
	 * @return array the messages loaded from database
	 */
	protected function loadMessagesFromDb($category, $language)
	{
		$db = $this->getDbConnection();
		$cmd = $db->createCommand()
		->select(array('smt.message AS message', 'tmt.translation AS translation'))
		->from($this->sourceMessageTable.' smt')
		->join($this->languageTable.' slt', $db->quoteColumnName('slt.code').'=:source_language')
		->join($this->categoryMessageTable.' cmt', $db->quoteColumnName('smt.id').'='.$db->quoteColumnName('cmt.message_id'))
		->join($this->categoryTable.' ct', array('and', $db->quoteColumnName('cmt.category_id').'='.$db->quoteColumnName('ct.id'), $db->quoteColumnName('ct.category').'=:category'))
		->join($this->languageTable.' lt', $db->quoteColumnName('lt.code').'=:language')
		->join($this->translatedMessageTable.' tmt', array('and', $db->quoteColumnName('smt.id').'='.$db->quoteColumnName('tmt.id'), $db->quoteColumnName('tmt.language_id').'='.$db->quoteColumnName('lt.id')));

		$messages = array();
		foreach($cmd->queryAll(true, array(':source_language' => $this->getLanguage($category), ':category' => $category, ':language' => $language)) as $row)
		{
			$messages[$row['message']] = $row['translation'];
		}

		return $messages;
	}

	/**
	 * Adds a source message to the source message table
	 *
	 * @param string $message The source message to add to the source message table
	 * @return array The ID of the source message that was inserted or null if the source message was not added
	 */
	public function addSourceMessage($message, $language, $createLanguageIfNotExists = false)
	{
		$languageId = $this->getLanguageId($language, $createLanguageIfNotExists);
		if($languageId !== false && $this->getDbConnection()->createCommand()->insert($this->sourceMessageTable, array('message' => $message, 'language_id' => $languageId)) > 0)
		{
			return $this->getDbConnection()->getLastInsertID($this->sourceMessageTable);
		}
		return null;
	}

	/**
	 * Adds a category to the category table
	 *
	 * @param string $message The category to add to the category table
	 * @return string The ID of the category that was inserted or null if the category was not added
	 */
	public function addCategory($category)
	{
		if($this->getDbConnection()->createCommand()->insert($this->categoryTable, array('category' => $category)) > 0)
		{
			return $this->getDbConnection()->getLastInsertID($this->categoryTable);
		}
		return null;
	}

	/**
	 * Adds a language to the language table
	 *
	 * @param string $language The language to add to the language table
	 * @return string The ID of the language that was inserted or null if the language was not added
	 */
	public function addLanguage($language)
	{
		if($this->getDbConnection()->createCommand()->insert($this->languageTable, array('code' => $language)) > 0)
		{
			return $this->getDbConnection()->getLastInsertID($this->languageTable);
		}
		return null;
	}

	/**
	 * Adds a source message to a category
	 *
	 * @param integer $messageId
	 * @param string $category
	 * @param boolean $createCategoryIfNotExists
	 * @return string The ID of the category the message was added to otherwise null if the category could not be found or the message could not be added to the category.
	 */
	public function addMessageToCategory($messageId, $category, $createCategoryIfNotExists = false)
	{
		$categoryId = $this->getCategoryId($category, $createCategoryIfNotExists);
		if($categoryId !== false && $this->getDbConnection()->createCommand()->insert($this->categoryMessageTable, array('category_id' => $categoryId , 'message_id' => $messageId)) > 0)
		{
			return $categoryId;
		}
		return null;
	}

	/**
	 * Adds a translation of a source message
	 *
	 * @param integer $sourceMessageId
	 * @param string $language
	 * @param string $translation
	 * @param boolean $createLanguageIfNotExists
	 * @return string The ID of the language this translation was added to otherwise null if the language could not be found or the translation could not be associated with the language and source message
	 */
	public function addTranslation($sourceMessageId, $language, $translation, $createLanguageIfNotExists = false)
	{
		$languageId = $this->getLanguageId($language, $createLanguageIfNotExists);
		if($languageId !== false && $this->getDbConnection()->createCommand()->insert($this->translatedMessageTable, array('id' => $sourceMessageId, 'language_id' => $languageId, 'translation' => $translation, 'last_modified' => time())) > 0)
		{
			return $languageId;
		}
		return null;
	}

	/**
	 * Returns the database accepted languages
	 *
	 * @return array An array of accepted language codes
	 */
	public function getAcceptedLanguages()
	{
		$db = $this->getDbConnection();
		return $db->createCommand()
		->select('lt.code')
		->from($this->languageTable.' lt')
		->join($this->acceptedLanguageTable.' alt', $db->quoteColumnName('lt.id').'='.$db->quoteColumnName('alt.id'))
		->queryAll();
	}

	/**
	 * Returns the primary key of a SourceMessage
	 * 
	 * @param string $message
	 * @param boolean $createIfNotExists
	 * @return array The ID of the source message followed by the ID of the source message language
	 */
	public function getSourceMessageId($message, $sourceLanguage, $createIfNotExists = false)
	{
		$db = $this->getDbConnection();
		$row = $db->createCommand()
		->select('smt.id')
		->from($this->sourceMessageTable.' smt')
		->join($this->languageTable.' slt', $db->quoteColumnName('slt.code').'=:source_language')
		->where($db->quoteColumnName('smt.message').'=:message')
		->queryScalar(array(':source_language' => $sourceLanguage, ':message' => $message));

		return $row === false && $createIfNotExists && ($row = $this->addSourceMessage($message, $sourceLanguage)) === null ? false : $row;
	}

	/**
	 * Returns the primary key of a Category
	 *
	 * @param string $category
	 * @param boolean $createIfNotExists
	 * @return integer The ID of the category if it is found or if createIfNotExists is set true and the category is successfully added otherwise null.
	 */
	public function getCategoryId($category, $createIfNotExists = false)
	{
		$db = $this->getDbConnection();
		$categoryId = $db->createCommand()
		->select('id')
		->from($this->categoryTable)
		->where($db->quoteColumnName('category').'=:category')
		->queryScalar(array(':category' => $category));

		return $categoryId === false && $createIfNotExists && ($categoryId = $this->addCategory($category)) === null ? false : $categoryId;
	}

	/**
	 * Returns the primary key of a Language
	 * 
	 * @param string $language
	 * @param boolean $createIfNotExists
	 * @return integer The ID of the language if it is found otherwise null.
	 */
	public function getLanguageId($language, $createIfNotExists = false)
	{
		$db = $this->getDbConnection();
		$languageId = $db->createCommand()
		->select('lt.id')
		->from($this->languageTable.' lt')
		->where($db->quoteColumnName('lt.code').'=:language')
		->queryScalar(array(':language' => $language));

		return $languageId === false && $createIfNotExists && ($languageId = $this->addLanguage($language)) === null ? false : $languageId;
	}
	
	/**
	 *
	 * @param string $category
	 * @param string $message
	 * @param string $language
	 * @param boolean $createSourceMessageIfNotExists
	 * @return string The translation of the message or null if it does not exist.
	 */
	public function getTranslation($category, $message, $language, $createSourceMessageIfNotExists = false)
	{
		$sourceLanguage = $this->getLanguage($category);
		$db = $this->getDbConnection();
		$translation = $db->createCommand()
		->select(array('MIN('.$db->quoteColumnName('ct.id').') AS '.$db->quoteColumnName('category_id'), 'MIN('.$db->quoteColumnName('smt.id').') AS '.$db->quoteColumnName('id'), 'lt.id AS language_id', 'tmt.translation AS translation'))
		->from($this->sourceMessageTable.' smt')
		->join($this->languageTable.' slt', $db->quoteColumnName('slt.code').'=:source_language')
		->leftJoin($this->categoryMessageTable.' cmt', $db->quoteColumnName('smt.id').'='.$db->quoteColumnName('cmt.message_id'))
		->leftJoin($this->categoryTable.' ct', array('and', $db->quoteColumnName('cmt.category_id').'='.$db->quoteColumnName('ct.id'), $db->quoteColumnName('ct.category').'=:category'))
		->leftJoin($this->languageTable.' lt', $db->quoteColumnName('lt.code').'=:language')
		->leftJoin($this->translatedMessageTable.' tmt', array('and', $db->quoteColumnName('smt.id').'='.$db->quoteColumnName('tmt.id'), $db->quoteColumnName('tmt.language_id').'='.$db->quoteColumnName('lt.id')))
		->where($db->quoteColumnName('smt.message').'=:message')
		->queryRow(true, array(':message' => $message, ':source_language' => $sourceLanguage, ':category' => $category, ':language' => $language));

		if($createSourceMessageIfNotExists)
		{
			if($translation['id'] === null)
			{
				if(($translation['id'] = $this->addSourceMessage($message, $sourceLanguage, true)) !== null)
				{
					$translation['category_id'] = $this->addMessageToCategory($translation['id'], $category, true);
					$translation['language_id'] = $this->getLanguageId($language, true);
				}
			}
			else
			{
				if($translation['category_id'] === null)
				{
					$translation['category_id'] = $this->addMessageToCategory($translation['id'], $category, true);
				}

				if($translation['language_id'] === null)
				{
					$translation['language_id'] = $this->addLanguage($language);
				}
			}
		}

		return $translation;
	}

	/**
	 * Translates a message to the specified language.
	 *
	 * Note, if the specified language is the same as
	 * the {@link getLanguage source message language}, messages will NOT be translated.
	 *
	 * If the message is not found in the translations, an {@link onMissingTranslation}
	 * event will be raised. Handlers can mark this message or do some
	 * default handling. The {@link CMissingTranslationEvent::message}
	 * property of the event parameter will be returned.
	 *
	 * @param string $category the message category
	 * @param string $message the message to be translated
	 * @param string $language the target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string the translated message (or the original message if translation is not needed)
	 */
	public function translate($category, $message, $language = null)
	{
		if($this->enableProfiling)
		{
			Yii::beginProfile($this->getTranslateModuleID().'.'.get_class($this).'.translate()', $this->getTranslateModuleID());
		}

		if($category === null)
		{
			$category = $this->defaultMessageCategory;
		}
		
		if($language === null)
		{
			$language = Yii::app()->getLanguage();
		}
		
		if($this->genericLocale)
		{
			$language = Yii::app()->getLocale()->getLanguageID($language);
		}
		
		if($this->forceTranslation || ($language !== $this->getLanguage($category) && (!$this->acceptedLanguagesOnly || $this->isAcceptedLanguage($language))))
		{
			$translation = $this->trim ? $this->translateMessage(trim((string)$category), trim((string)$message), trim((string)$language)) : $this->translateMessage((string)$category, (string)$message, (string)$language);
		}
		else
		{
			$translation = $message;
		}

		if($this->enableProfiling)
		{
			Yii::endProfile($this->getTranslateModuleID().'.'.get_class($this).'.translate()', $this->getTranslateModuleID());
		}

		return $translation;
	}
	
	/**
	 * Translates the specified message.
	 * If the message is not found, an {@link onMissingTranslation}
	 * event will be raised.
	 * 
	 * @param string $category the category that the message belongs to
	 * @param string $message the message to be translated
	 * @param string $language the target language
	 * @return string the translated message
	 */
	protected function translateMessage($category, $message, $language)
	{
		$key = $this->getLanguage($category).'.'.$language.'.'.$category;
		
		if(!isset($this->_messages[$key]))
		{
			$this->_messages[$key] = $this->loadMessages($category, $language);
		}
		
		if(isset($this->_messages[$key][$message]) && $this->_messages[$key][$message] !== '')
		{
			return $this->_messages[$key][$message];
		}
		
		if($this->hasEventHandler('onMissingTranslation'))
		{
			$event = new CMissingTranslationEvent($this, $category, $message, $language);
			$this->onMissingTranslation($event);
			if($this->cacheMissingLocally)
			{
				$this->_messages[$key][$message] = $event->message;
			}
			return $event->message;
		}
		
		return $message;
	}

}
