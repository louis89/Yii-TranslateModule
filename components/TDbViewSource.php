<?php

Yii::import(TranslateModule::ID.'.widgets.configurationStatus.ConfigurationStatus');
Yii::import(TranslateModule::ID.'.widgets.configurationStatus.Installable');
Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TDbViewSource extends CApplicationComponent implements ConfigurationStatus, Installable, ITranslateModuleComponent
{

	/**
	 * @var string The name of the routes database table.
	 */
	public $routeTable = '{{translate_route}}';

	/**
	 * @var string The name of the route views database table.
	 */
	public $routeViewTable = '{{translate_route_view}}';

	/**
	 * @var string The name of the view sources database table.
	 */
	public $viewSourceTable = '{{translate_view_source}}';

	/**
	 * @var string The name of the views database table.
	 */
	public $viewTable = '{{translate_view}}';

	/**
	 * @var string The name of the view messages database table.
	 */
	public $viewMessageTable = '{{translate_view_message}}';

	/**
	 * @var integer the time in seconds that the messages can remain valid in cache.
	 * Defaults to 0, meaning the caching is disabled.
	 */
	public $cachingDuration = 0;

	/**
	 * @var boolean If true each call to the translate method will be profiled.
	 */
	public $enableProfiling = false;
	
	/**
	 * @var string the ID of the cache application component that is used to cache the messages.
	 * Defaults to 'cache' which refers to the primary cache application component.
	 * Set this property to false if you want to disable caching the messages.
	 */
	public $cacheID = 'cache';
	
	/**
	 * @var boolean If true the translations returned by onMissingTranslation events will be immediately cached locally. Defaults to True.
	 */
	public $cacheMissingLocally = true;
	
	public $trim = true;
	
	private $_translateModuleID = TranslateModule::ID;

	private $_views = array();
	
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
		$this->_translateModuleID = $translateModuleID;
	}
	
	public function attributeNames()
	{
		return array(
			'routeTable',
			'routeViewTable',
			'viewSourceTable',
			'viewTable',
			'viewMessageTable',
			'cachingDuration',
			'enableProfiling',
			'cacheID',
			'translateModuleID',
			'cacheMissingLocally'
		);
	}
	
	public function attributeRules()
	{
		return array(
			array('routeTable, routeViewTable, viewSourceTable, viewTable, viewMessageTable, translateModuleID', 'required'),
			array('routeTable, routeViewTable, viewSourceTable, viewTable, viewMessageTable', 'translate.validators.DbTableExistsValidator', 'dbSchema' => $this->getDbConnection()->getSchema()),
			array('cachingDuration', 'numerical', 'allowEmpty' => false, 'integerOnly' => true, 'min' => 0),
			array('cacheID', 'translate.validators.ComponentValidator', 'type' => 'ICache', 'allowEmpty' => false),
			array('enableProfiling, cacheMissingLocally', 'boolean', 'allowEmpty' => false, 'trueValue' => true, 'falseValue' => false, 'strict' => true, 'message' => '{attribute} must strictly be a boolean value of either "true" or "false".')
		);
	}
	
	public function attributeLabels()
	{
		$module = $this->getTranslateModule();
		return array(
			'routeTable' => $module->t('Route Table'),
			'routeViewTable' => $module->t('Route View Table'),
			'viewSourceTable' => $module->t('View Source Table'),
			'viewTable' => $module->t('View Table'),
			'viewMessageTable' => $module->t('View Message Table'),
			'cachingDuration' => $module->t('Caching Duration'),
			'cacheID' => $module->t('Cache Component ID'),
			'enableProfiling' => $module->t('Enable Profiling'),
			'cacheMissingLocally' => $module->t('Cache Missing Translations Locally')
		);
	}

	public function attributeDescriptions()
	{
		$module = $this->getTranslateModule();
		return array(
			'routeTable' => $module->t('The database table containing requested routes. Defaults to "{{translate_route}}".'),
			'routeViewTable' => $module->t('The database table containing associations of routes with views. Defaults to "{{translate_route_view}}".'),
			'viewSourceTable' => $module->t('The database table containing source views. Defaults to "{{translate_view_source}}".'),
			'viewTable' => $module->t('The database table containing translated views. Defaults to "{{translate_view}}".'),
			'viewMessageTable' => $module->t('The database table containing the associations of messages and views. Defaults to "{{translate_view_message}}".'),
			'enableProfiling' => $module->t('If true each translation attempt will be profiled. Defaults to False.'),
			'cachingDuration' => $module->t('The time in seconds to cache translated views in the caching component. Defaults to 0 meaning do not cache.'),
			'cacheID' => $module->t('The name of the caching component for caching view translations. Defaults to "cache".'),
			'cacheMissingLocally' => $module->t('If True the translated view paths returned by onMissingTranslation events will be immediately cached locally. Defaults to True.')
		);
	}
	
	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component is a source of views, and their respective translations, routes, and messages. This source is mutable. Records may be added, removed, or retrieved using this source.');
	}

	/**
	 * @return boolean True if all database tables are installed. False otherwise.
	 */
	public function getIsInstalled()
	{
		return ($db = $this->getDbConnection()) !== null &&
			($schema = $db->getSchema()) !== null && 
			$schema->getTable($this->routeTable) !== null &&
			$schema->getTable($this->routeViewTable) !== null &&
			$schema->getTable($this->viewMessageTable) !== null &&
			$schema->getTable($this->viewSourceTable) !== null &&
			$schema->getTable($this->viewTable) !== null;
	}

	public function installAttributeRules()
	{
		return array(
			array('routeTable, routeViewTable, viewSourceTable, viewTable, viewMessageTable', 'required'),
			//array('messageSource', 'translate.validators.ClassTypeValidator', 'type' => 'TDbMessageSource'),
		);
	}
	
	/**
	 * @param boolean $reinstall If True and the system is already installed then it will be reinstalled. WARNING: all data will be lost if system is reinstalled. Defaults to false.
	 * @return number Returns status code
	 */
	public function install($reinstall = false)
	{
		if(!$reinstall && $this->getIsInstalled())
		{
			return Installable::OVERWRITE; // Already installed
		}

		$messageSource = $this->getTranslateModule()->getMessageSource();
		
		if(!$messageSource instanceof TDbMessageSource)
		{
			return Installable::ERROR;
		}
		
		$tableNames = array(
			$this->routeTable => $this->routeTable,
			$this->routeViewTable => $this->routeViewTable,
			$this->viewMessageTable => $this->viewMessageTable,
			$this->viewSourceTable => $this->viewSourceTable,
			$this->viewTable => $this->viewTable);
		
		$db = $messageSource->getDbConnection();
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
				$tableNames[$this->routeTable],
				array(
					'id' => 'pk',
					'route' => 'varchar(255) NOT NULL',
					'UNIQUE KEY '.$schema->quoteColumnName('route').' ('.$schema->quoteColumnName('route').')'
				)
			).';';

			$sql .= $schema->createTable(
				$tableNames[$this->routeViewTable],
				array(
					'route_id' => 'integer NOT NULL',
					'view_id' => 'integer NOT NULL',
					'PRIMARY KEY ('.$schema->quoteColumnName('route_id').','.$schema->quoteColumnName('view_id').'),'.
					'KEY '.$schema->quoteColumnName('view_id').' ('.$schema->quoteColumnName('view_id').')'
				)
			).';';

			$sql .= $schema->createTable(
				$tableNames[$this->viewMessageTable],
				array(
					'view_id' => 'integer NOT NULL',
					'message_id' => 'integer NOT NULL',
					'PRIMARY KEY ('.$schema->quoteColumnName('view_id').','.$schema->quoteColumnName('message_id').'),'.
					'KEY '.$schema->quoteColumnName('message_id').' ('.$schema->quoteColumnName('message_id').')'
				)
			).';';
				
			$sql .= $schema->createTable(
				$tableNames[$this->viewSourceTable],
				array(
					'id' => 'pk',
					'path' => 'varchar(255) NOT NULL',
					'UNIQUE KEY '.$schema->quoteColumnName('path').' ('.$schema->quoteColumnName('path').')'
				)
			).';';
				
			$sql .= $schema->createTable(
				$tableNames[$this->viewTable],
				array(
					'id' => 'integer NOT NULL',
					'language_id' => 'integer NOT NULL',
					'path' => 'varchar(255) NOT NULL',
					'created' => 'integer NOT NULL',
					'PRIMARY KEY ('.$schema->quoteColumnName('id').','.$schema->quoteColumnName('language_id').'),'.
					'UNIQUE KEY '.$schema->quoteColumnName('path').' ('.$schema->quoteColumnName('path').'),'.
					'KEY '.$schema->quoteColumnName('created').' ('.$schema->quoteColumnName('created').'),'.
					'KEY '.$schema->quoteColumnName('language_id').' ('.$schema->quoteColumnName('language_id').')'
				)
			).';';

			// Add foreign key constraints
			$sql .= $schema->addForeignKey(
				$tableNames[$this->routeViewTable].'_fk_1',
				$tableNames[$this->routeViewTable],
				'view_id',
				$tableNames[$this->viewSourceTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->routeViewTable].'_fk_2',
				$tableNames[$this->routeViewTable],
				'route_id',
				$tableNames[$this->routeTable],
				'id',
				'CASCADE',
				'CASCADE').';';

			$sql .= $schema->addForeignKey(
				$tableNames[$this->viewMessageTable].'_fk_1',
				$tableNames[$this->viewMessageTable],
				'view_id',
				$tableNames[$this->viewSourceTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->viewMessageTable].'_fk_2',
				$tableNames[$this->viewMessageTable],
				'message_id',
				$messageSource->sourceMessageTable,
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->viewTable].'_fk_1',
				$tableNames[$this->viewTable],
				'id',
				$tableNames[$this->viewSourceTable],
				'id',
				'CASCADE',
				'CASCADE').';';
				
			$sql .= $schema->addForeignKey(
				$tableNames[$this->viewTable].'_fk_2',
				$tableNames[$this->viewTable],
				'language_id',
				$messageSource->languageTable,
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

	/**
	 * Returns the DB connection used for the view source.
	 * @return CDbConnection the DB connection used for the view source.
	 */
	public function getDbConnection()
	{
		return $this->getMessageSource()->getDbConnection();
	}
	
	/**
	 * @return TDbMessageSource the source of messages used by this view source
	 */
	public function getMessageSource()
	{
		return $this->getTranslateModule()->getMessageSource();
	}
	
	/**
	 * Gets whether caching is enabled for this view source.
	 *
	 * @return boolean True if caching is enabled false otherwise.
	 */
	public function getIsCachingEnabled()
	{
		return $this->getCache() !== null;
	}
	
	/**
	 * Flushes the cache component used by this view source if it is configured.
	 * 
	 * @return boolean True on success. False on failure.
	 */
	public function flushCache()
	{
		return ($cache = $this->getCache()) !== null && $cache->flush();
	}
	
	/**
	 * Deletes all cached view for a specified route and language. 
	 * This should be called if a translation changes while caching is enabled and you would like to change to take effect immediately.
	 *
	 * @param string $route the route.
	 * @param string $language the language.
	 * @return boolean True on success. False on failure.
	 */
	public function deleteCache($route, $language)
	{
		return ($cache = $this->getCache()) !== null && $cache->delete($this->getCacheKey($route, $language));
	}

	/**
	 * Returns the cache component used by this view source.
	 *
	 * @return ICache The caching component used by this view source or null if caching is disabled.
	 */
	protected function getCache()
	{
		return $this->cachingDuration > 0 && $this->cacheID !== false ? Yii::app()->getComponent($this->cacheID) : null;
	}

	/**
	 * Given a route and a language this method determines the cache key to be used for caching an item.
	 *
	 * @param string $route
	 * @param string $language
	 * @return string The cache key
	 */
	protected function getCacheKey($route, $language)
	{
		return $this->getTranslateModuleID().'.views.'.$route.'.'.$language;
	}

	/**
	 * Loads and returns the views for a particular route and language.
	 *
	 * @param string $route The requested route
	 * @param string $language The requested language
	 * @return array A list of known views for the specified route and language in the form array(source_path => view_path)
	 */
	protected function loadViews($route, $language)
	{
		if(($cache = $this->getCache()) !== null)
		{
			$key = $this->getCacheKey($route, $language);
			$views = $cache->get($key);
			if($views === false)
			{
				$views = $this->loadViewsFromDb($route, $language);
				$cache->set($key, $views, $this->cachingDuration);
			}
		}
		else
		{
			$views = $this->loadViewsFromDb($route, $language);
		}

		return $views;
	}

	/**
	 * Loads and returns the views for a particular route and language from the database.
	 *
	 * @param string $route The requested route
	 * @param string $language The requested language
	 * @return array A list of known views for the specified route and language in the form array(source_path => view_path)
	 */
	protected function loadViewsFromDb($route, $language)
	{
		$messageSource = $this->getMessageSource();
		$db = $this->getDbConnection();
		$cmd = $db->createCommand()
		->select(array('vst.path AS source_path', 'vt.path AS view_path', 'MAX('.$db->quoteColumnName('tmt.last_modified').') AS '.$db->quoteColumnName('tmt_last_modified'), 'vt.created AS vt_created'))
		->from($this->routeTable.' rt')
		->join($this->routeViewTable.' rvt', $db->quoteColumnName('rt.id').'='.$db->quoteColumnName('rvt.route_id'))
		->join($this->viewSourceTable.' vst', $db->quoteColumnName('rvt.view_id').'='.$db->quoteColumnName('vst.id'))
		->join($messageSource->languageTable.' lt', $db->quoteColumnName('lt.code').'=:language')
		->join($this->viewTable.' vt', array('and', $db->quoteColumnName('vst.id').'='.$db->quoteColumnName('vt.id'), $db->quoteColumnName('vt.language_id').'='.$db->quoteColumnName('lt.id')))
		->leftJoin($this->viewMessageTable.' vmt', $db->quoteColumnName('vst.id').'='.$db->quoteColumnName('vmt.view_id'))
		->leftJoin($messageSource->translatedMessageTable.' tmt', array('and', $db->quoteColumnName('vmt.message_id').'='.$db->quoteColumnName('tmt.id'), $db->quoteColumnName('tmt.language_id').'='.$db->quoteColumnName('vt.language_id')))
		->where($db->quoteColumnName('rt.route').'=:route')
		->group('vst.id')
		->having(array('or', $db->quoteColumnName('tmt_last_modified').' IS NULL', $db->quoteColumnName('tmt_last_modified').'<'.$db->quoteColumnName('vt_created')));

		$views = array();
		foreach($cmd->queryAll(true, array(':language' => $language, ':route' => $route)) as $row)
		{
			if($row['source_path'] !== null)
			{
				$views[$row['source_path']] = $row['view_path'];
			}
		}

		return $views;
	}

	/**
	 * Adds a source view to this view source and returns the source view's unique identifier.
	 *
	 * @param string $path The path to the source view
	 * @return string|null The unique identifier for the source view or null if the source view could not be added.
	 */
	public function addSourceView($path)
	{
		if($this->getDbConnection()->createCommand()->insert($this->viewSourceTable, array('path' => $path)) > 0)
		{
			return $this->getDbConnection()->getLastInsertID($this->viewSourceTable);
		}
		return null;
	}

	/**
	 * Adds a route to this view source and returns the route's unique identifier.
	 *
	 * @param string $route The name of the route
	 * @return string|null The unique identifier for the route or null if the route could not be added.
	 */
	public function addRoute($route)
	{
		if($this->getDbConnection()->createCommand()->insert($this->routeTable, array('route' => $route)) > 0)
		{
			return $this->getDbConnection()->getLastInsertID($this->routeTable);
		}
		return null;
	}

	/**
	 * Adds a view to a a route.
	 *
	 * @param string $viewId The unique identifier of the view.
	 * @param string $route The name of the route.
	 * @param boolean $createRouteIfNotExists Defaults to False. If True and the route does not already exists then the route will be created.
	 * @return string|null The unique identifier the view was added to or null if the view could not be added to the route.
	 */
	public function addViewToRoute($viewId, $route, $createRouteIfNotExists = false)
	{
		$routeId = $this->getRouteId($route, $createRouteIfNotExists);
		if($routeId !== false && $this->getDbConnection()->createCommand()->insert($this->routeViewTable, array('route_id' => $routeId , 'view_id' => $viewId)) > 0)
		{
			return $routeId;
		}
		return null;
	}

	/**
	 * Adds a source message to a view.
	 *
	 * @param string $viewId The unique identifier of the view.
	 * @param string $category The category of the message being added to the view.
	 * @param string $message The message to add to the view.
	 * @param boolean $createMessageIfNotExists Defaults to False. If True and the message does not already exists then the message will be created.
	 * @return string|null The unique identifier the source message was added to or null if the source message could not be added to the view
	 */
	public function addSourceMessageToView($viewId, $category, $message, $createMessageIfNotExists = false)
	{
		$messageSource = $this->getMessageSource();
		$messageId = $messageSource->getSourceMessageId($message, $messageSource->getLanguage($category), $createMessageIfNotExists);
		if($messageId !== false && $this->getDbConnection()->createCommand()->insert($this->viewMessageTable, array('view_id' => $viewId, 'message_id' => $messageId)) > 0)
		{
			return $messageId;
		}
		return null;
	}

	/**
	 * Adds a translated view.
	 *
	 * @param string $sourceViewId The unique identifier of the source view.
	 * @param string $path The path to the translated view.
	 * @param string $languageId The unique identifier of the language the source view has been translated to.
	 * @return string|null The unqiue Identifier of the translated view or null if the translated view could not be added.
	 */
	public function addView($sourceViewId, $path, $languageId)
	{
		$args = array('id' => $sourceViewId, 'language_id' => $languageId, 'path' => $path, 'created' => time());
		if($this->getDbConnection()->createCommand()->insert($this->viewTable, $args) > 0)
		{
			return $args;
		}
		return null;
	}

	/**
	 * Returns the primary key of a route.
	 *
	 * @param string $route The route name.
	 * @param boolean $createIfNotExists Defaults to False. If True and the route does not already exists then the route will be added.
	 * @return string|null The unqiue identifier of the route or null if the route is not found.
	 */
	public function getRouteId($route, $createIfNotExists = false)
	{
		$db = $this->getDbConnection();
		$routeId = $db->createCommand()
		->select('rt.id')
		->from($this->routeTable.' rt')
		->where($db->quoteColumnName('rt.route').'=:route')
		->queryScalar(array(':route' => $route));

		return ($routeId === false && $createIfNotExists && ($routeId = $this->addRoute($route)) === null) ? false : $routeId;
	}

	/**
	 * Gets all messages associated with a particular view.
	 *
	 * @param string $viewId The unique identifier of the view.
	 * @return array An array of the messages associated with the view.
	 */
	public function getViewMessages($viewId)
	{
		$db = $this->getDbConnection();
		return $db->createCommand()
		->select(array('smt.id AS id', 'smt.message AS message'))
		->from($this->getMessageSource()->sourceMessageTable.' smt')
		->join($this->viewMessageTable.' vmt', $db->quoteColumnName('vmt.message_id').'='.$db->quoteColumnName('smt.id'))
		->where($db->quoteColumnName('vmt.view_id').'=:view_id')
		->queryAll(true, array(':view_id' => $viewId));
	}

	/**
	 * Gets a translated view for a particular route, source view, and language.
	 *
	 * @param string $route The name of the route.
	 * @param string $sourcePath The path to the source view.
	 * @param string $language The language of the translated view.
	 * @param boolean $createSourceViewIfNotExists Defaults to False. If True and the source view does not already exists then the source view will be added.
	 * @return array The translated view's meta data as an associative array in the following format array('route_id' => 'route_id', 'source_view_id' => 'source_view_id', 'language_id' => 'language_id', 'translated_view_path' => 'translated_view_path')
	 */
	public function getView($route, $sourcePath, $language, $createSourceViewIfNotExists = false)
	{
		$messageSource = $this->getMessageSource();
		$db = $this->getDbConnection();
		$cmd = $db->createCommand()
			->select(array(($route === null ? '(NULL)' : 'MIN('.$db->quoteColumnName('rt.id').')').' AS '.$db->quoteColumnName('route_id'), 'vst.id AS id', 'lt.id AS language_id', 'vt.path AS path'))
			->from($this->viewSourceTable.' vst');
		
		if($route !== null)
		{
			$cmd->leftJoin($this->routeViewTable.' rvt', $db->quoteColumnName('vst.id').'='.$db->quoteColumnName('rvt.view_id'))
				->leftJoin($this->routeTable.' rt', array('and', $db->quoteColumnName('rvt.route_id').'='.$db->quoteColumnName('rt.id'), $db->quoteColumnName('rt.route').'=:route'), array(':route' => $route));
		}
		
		$cmd->leftJoin($messageSource->languageTable.' lt', $db->quoteColumnName('lt.code').'=:code')
			->leftJoin($this->viewTable.' vt', array('and', $db->quoteColumnName('vst.id').'='.$db->quoteColumnName('vt.id'), $db->quoteColumnName('vt.language_id').'='.$db->quoteColumnName('lt.id')))
			->where($db->quoteColumnName('vst.path').'=:source_path');

		 
		$view = $cmd->queryRow(true, array(':code' => $language, ':source_path' => $sourcePath));
		if($createSourceViewIfNotExists)
		{
			if($view['id'] === null)
			{
				if(($view['id'] = $this->addSourceView($sourcePath)) !== null)
				{
					if($route !== null)
					{
						$view['route_id'] = $this->addViewToRoute($view['id'], $route, true);
					}
					$view['language_id'] = $messageSource->getLanguageId($language, true);
				}
			}
			else
			{
				if($route !== null && $view['route_id'] === null)
				{
					$view['route_id'] = $this->addViewToRoute($view['id'], $route, true);
				}

				if($view['language_id'] === null)
				{
					$view['language_id'] = $messageSource->addLanguage($language);
				}
			}
		}

		return $view;
	}

	/**
	 * Disassociates several messages from a view.
	 *
	 * @param string $viewId The unqiue identifier of the view.
	 * @param array $messageIds The unique identifiers of the messages.
	 * @return integer The number of messages that were disassociated from the view.
	 */
	public function deleteViewMessages($viewId, $messageIds)
	{
		$db = $this->getDbConnection();
		return empty($messageIds) ? 0 : $db->createCommand()->delete($this->viewMessageTable, array('and', $db->quoteColumnName('view_id').'=:view_id', array('in', 'message_id', $messageIds)), array(':view_id' => $viewId));
	}

	/**
	 * Update the meta data for a translated view.
	 *
	 * @param string $viewId The unique identifier of the view.
	 * @param string $languageId The unique identifier of the language
	 * @param string $created The time at which the view was created.
	 * @param string $path The path to the view.
	 * @return integer The number of views updated.
	 */
	public function updateView($viewId, $languageId, $created, $path)
	{
		$db = $this->getDbConnection();
		return $db->createCommand()->update($this->viewTable, array('created' => $created, 'path' => $path), array('and', $db->quoteColumnName('id').'=:id', $db->quoteColumnName('language_id').'=:language_id'), array(':id' => $viewId, ':language_id' => $languageId));
	}

	/**
	 * Translates a view to the specified language.
	 *
	 * If the view is not found in the translated views, an {@link onMissingViewTranslation}
	 * event will be raised. Handlers can mark this message or do some
	 * default handling. The {@link TMissingViewTranslationEvent::path}
	 * property of the event parameter will be returned.
	 *
	 * @param CBaseController $context the controller or widget who is rendering the view file.
	 * @param string $path the path to the source file to be translated
	 * @param string $language the target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string the path to the translated view
	 */
	public function translate($context, $path, $language = null)
	{
		if($this->enableProfiling)
		{
			Yii::beginProfile($this->getTranslateModuleID().'.'.get_class($this).'.translate()', $this->getTranslateModuleID());
		}

		if(!is_file($path) || ($path = realpath($path)) === false)
		{
			throw new CException($this->getTranslateModule()->t('Source view file "{file}" does not exist.', array('{file}' => $path)));
		}

		if($language === null)
		{
			$language = Yii::app()->getLanguage();
		}
		else if($this->trim)
		{
			$language = trim($language);
		}

		$translatedPath = $this->translateView($context instanceof CController ?  $context->getRoute() : $context->getController()->getRoute(), $path, $language);

		if($this->enableProfiling)
		{
			Yii::endProfile($this->getTranslateModuleID().'.'.get_class($this).'.translate()', $this->getTranslateModuleID());
		}

		return $translatedPath;
	}

	/**
	 * Translates the specified view.
	 * If the translated view is not found, an {@link onMissingViewTranslation}
	 * event will be raised.
	 *
	 * @param string $route the requested route that caused this view to need to be translated.
	 * @param string $path the path to the source file to be translated
	 * @param string $language the target language
	 * @return string the path to the translated view
	 */
	protected function translateView($route, $path, $language)
	{
		$key = $route.'.'.$language;

		if(!isset($this->_views[$key]))
		{
			$this->_views[$key] = $this->loadViews($route, $language);
		}

		if(isset($this->_views[$key][$path]) && @filemtime($path) < @filemtime($this->_views[$key][$path]))
		{
			return $this->_views[$key][$path];
		}

		if($this->hasEventHandler('onMissingTranslation'))
		{
			$event = new TMissingViewTranslationEvent($this, $path, $route, $language);
			$this->onMissingTranslation($event);
			if($this->cacheMissingLocally)
			{
				$this->_views[$key][$path] = $event->path;
			}
			return $event->path;
		}

		return $path;
	}

	/**
	 * Raised when a view cannot be translated.
	 * Handlers may log this view or do some default handling.
	 * The {@link TMissingViewTranslationEvent::path} property
	 * will be returned by {@link translateView}.
	 *
	 * @param TMissingViewTranslationEvent $event the event parameter
	 */
	public function onMissingTranslation($event)
	{
		$this->raiseEvent('onMissingTranslation', $event);
	}

}

/**
 * TMissingViewTranslationEvent represents the parameter for the {@link TViewSource::onMissingViewTranslation onMissingViewTranslation} event.
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * @package translate
 */
class TMissingViewTranslationEvent extends CEvent
{

	/**
	 * @var string the path of the source file to be translated
	 */
	public $path;
	/**
	 * @var string the route requesting this view
	 */
	public $route;
	/**
	 * @var string the ID of the language that the source file is to be translated to
	 */
	public $language;

	/**
	 * Constructor.
	 * @param mixed $sender sender of this event
	 * @param string $path the path of the source file to be translated
	 * @param string $route the route requesting this view
	 * @param string $language the ID of the language that the source file is to be translated to
	 */
	public function __construct($sender, $path, $route, $language)
	{
		parent::__construct($sender);
		$this->path = $path;
		$this->route = $route;
		$this->language = $language;
	}

}
