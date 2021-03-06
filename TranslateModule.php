<?php

Yii::import('translate.widgets.configurationStatus.ConfigurationStatus');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TranslateModule extends CWebModule implements ConfigurationStatus
{

	/**
	 * The Version of this module
	 *
	 * @name TranslateModule::VERSION
	 * @type string
	 * @const string
	 */
	const VERSION = '0.9';
	
	/**
	 * The ID/name of this module.
	 * This should match the name of the parent directory of this module as well as the name used to identify this module in your application's configuration.
	 *
	 * @name TranslateModule::ID
	 * @type string
	 * @const string
	 */
	const ID = 'translate';

	/**
	 * The language of all source messages in this module.
	 *
	 * @name TranslateModule::LANGUAGE
	 * @type string
	 * @const string
	 */
	const LANGUAGE = 'en';
	
	/**
	 * @var string category of all messages in this module
	 */
	public $tCategory = 'translate';
	
	/**
	 * @var array the IP filters that specify which IP addresses are allowed to access GiiModule.
	 * Each array element represents a single filter. A filter can be either an IP address
	 * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
	 * If you want to allow all IPs to access gii, you may set this property to be false
	 * (DO NOT DO THIS UNLESS YOU KNOW THE CONSEQUENCE!!!)
	 * The default value is array('127.0.0.1', '::1'), which means GiiModule can only be accessed
	 * on the localhost.
	 */
	public $ipFilters = array('127.0.0.1', '::1');
	
	/**
	 * @var string
	 */
	public $defaultController = 'Translate';
	
	/**
	 * @var string The name of the translate component. Change this to what ever component name you used in your application's configuration.
	 */
	public $translatorID;
	
	/**
	 * @var string The name of the message source component. Change this to what ever component name you used in your application's configuration.
	 */
	public $messageSourceID;
	
	/**
	 * @var string The name of the view source component. Change this to what ever component name you used in your application's configuration.
	 */
	public $viewSourceID;
	
	/**
	 * @var string The name of the view renderer component. Change this to what ever component name you used in your application's configuration.
	 */
	public $viewRendererID;
	
	/**
	 * @var string Mutex component ID.
	 */
	public $mutexID;
	
	public $managementAccessRules = array();
	
	public $managementActionFilters = array();
	
	private static $_module;
	
	private $_translator;
	
	private $_messageSource;
	
	private $_viewSource;
	
	private $_viewRenderer;
	
	private $_mutex;
	
	private $_previousModule;
	
	public function getVersion()
	{
		return self::VERSION;
	}
	
	public function init()
	{
		$id = $this->getId();
		$this->setImport(array(
			$id.'.models.*',
			$id.'.controllers.*',
			$id.'.components.*',
		));
		return parent::init();
	}
	
	public function beforeControllerAction($controller, $action)
	{
		if(!$this->allowIp(Yii::app()->getRequest()->getUserHostAddress()))
		{
			throw new CHttpException(403, 'You are not allowed to access this page.');
		}
		$this->_previousModule = self::$_module;
		self::$_module = $this;
		if($controller instanceof ITranslateModuleComponent)
		{
			$controller->setTranslateModuleID($this->getId());
		}
		if($action instanceof ITranslateModuleComponent)
		{
			$action->setTranslateModuleID($this->getId());
		}
		if(!$this->getIsInstalled() && $action->getId() !== 'index')
		{
			Yii::app()->getUser()->setFlash(TranslateModule::ID.'-notice', $this->t('Please fix the configuration errors listed below.'));
			$controller->forward('translate/translate');
		}
		return parent::beforeControllerAction($controller, $action);
	}
	
	public function afterControllerAction($controller, $action)
	{
		$result = parent::afterControllerAction($controller, $action);
		self::$_module = $this->_previousModule;
		return $result;
	}
	
	/**
	 * Checks to see if the user IP is allowed by {@link ipFilters}.
	 * @param string $ip the user IP
	 * @return boolean whether the user IP is allowed by {@link ipFilters}.
	 */
	protected function allowIp($ip)
	{
		if(empty($this->ipFilters))
		{
			return true;
		}
		foreach($this->ipFilters as $filter)
		{
			if($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos)))
			{
				return true;
			}
		}
		return false;
	}
	
	public function attributeNames()
	{
		return array(
			'id',
			'name',
			'version',
			'basePath',
			'modulePath',
			'viewPath',
			'layoutPath',
			'controllerNamespace',
			'defaultController',
			'layout',
			'translatorID',
			'messageSourceID',
			'viewSourceID',
			'viewRendererID',
			'mutexID',
		);
	}
	
	public function attributeRules()
	{
		return array(
			array('translatorID, messageSourceID, basePath, modulePath, layoutPath, viewPath, id', 'required'),
			array('translatorID', 'translate.validators.ConfigurationStatusValidator', 'type' => 'DefaultTranslator'),
			array('messageSourceID', 'translate.validators.ConfigurationStatusValidator', 'type' => 'TDbMessageSource'),
			array('viewSourceID', 'translate.validators.ConfigurationStatusValidator', 'type' => 'TDbViewSource'),
			array('viewRendererID', 'translate.validators.ConfigurationStatusValidator', 'type' => 'TViewRenderer'),
			array('mutexID', 'translate.validators.ComponentValidator', 'type' => 'LDMutex'),
			array('defaultController, basePath, modulePath, layoutPath, viewPath, id', 'length', 'allowEmpty' => false),
			array('controllerNamespace, layout', 'length', 'allowEmpty' => true),
		);
	}
	
	public function attributeLabels()
	{
		return array(
			'translatorID' => $this->t('Translator Component ID'),
			'messageSourceID' => $this->t('Message Source Component ID'),
			'viewSourceID' => $this->t('View Source Component ID'),
			'viewRendererID' => $this->t('View Renderer ID'),
			'mutexID' => $this->t('Mutex Component ID'),
			'defaultController' => $this->t('Default Controller'),
		);
	}
	
	public function attributeDescriptions()
	{
		return array(
			'defaultController' => $this->t('The ID of the default controller for this module.'),
			'basePath' => $this->t('The root directory of the module.'),
			'modulePath' => $this->t('The directory that contains the application modules.'),
			'layoutPath' => $this->t('The root directory of layout files.'),
			'name' => $this->t('The name of this module.'),
			'version' => $this->t('The version of this module.'),
			'viewPath' => $this->t('The root directory of view files.'),
			'id' => $this->t('This module\'s ID.'),
			'mutexID' => $this->t('A mutual exclusion lock to avoid the same message from being translated twice at the same time during separate parallel requests. Defaults to "mutex".'),
			'controllerNamespace' => $this->t('Namespace that should be used when loading controllers.'),
			'layout' => $this->t('The layout that is shared by the controllers inside this module.'),
		);
	}
	
	public function getDescription()
	{
		return $this->t('A module for managing translations and related components.');
	}

	/**
	 * Get the translator component
	 *
	 * @throws CException If the translator component named by {@see TranslateModule::$translatorID} cannot be found of is not an instance of {@link DefaultTranslator}
	 * @return DefaultTranslator The translator component named by {@see TranslateModule::$translatorID}
	 */
	public function getTranslator()
	{
		if($this->_translator === null)
		{
			$this->_translator = Yii::app()->getComponent($this->translatorID);
		}
		return $this->_translator;
	}
	
	/**
	 * Get the message source component
	 *
	 * @throws CException If the translate component named by {@see TranslateModule::$messageSourceID} cannot be found or is not an instance of {@link TDbMessageSource}
	 * @return TDbMessageSource The message source component named by {@see TranslateModule::$messageSourceID}
	 */
	public function getMessageSource()
	{
		if($this->_messageSource === null)
		{
			$this->_messageSource = Yii::app()->getComponent($this->messageSourceID);
		}
		return $this->_messageSource;
	}
	
	/**
	 * Get the view source component
	 *
	 * @throws CException If the view source component named by {@see TranslateModule::$viewSourceID} cannot be found or is not an instance of {@link TDbViewSource}
	 * @return TDbViewSource The view source component named by {@see TranslateModule::$viewSourceID}
	 */
	public function getViewSource()
	{
		if($this->_viewSource === null)
		{
			$this->_viewSource = Yii::app()->getComponent($this->viewSourceID);
		}
		return $this->_viewSource;
	}

	/**
	 * Get the view renderer component
	 *
	 * @throws CException If the view renderer component named by {@see TranslateModule::$viewRendererID} is not set or is not an instance of {@link TViewRenderer}.
	 * @return TViewRenderer The view renderer component named by {@see TranslateModule::$viewRendererID}
	 */
	public function getViewRenderer()
	{
		if($this->_viewRenderer === null)
		{
			$this->_viewRenderer = Yii::app()->getComponent($this->viewRendererID);
		}
		return $this->_viewRenderer;
	}
	
	/**
	 * Returns the mutex used to ensure the same message is not being translated at the same time during separate requests.
	 * @return LDMutex the mutex used to ensure the same message is not being translated at the same time during separate requests.
	 */
	public function getMutex()
	{
		if($this->_mutex === null && $this->mutexID !== null)
		{
			$this->_mutex = Yii::app()->getComponent($this->mutexID);
			if(!$this->_mutex instanceof LDMutex)
			{
				throw new CException($this->getTranslateModule()->t(__CLASS__.'.mutexID is invalid. Please make sure "{id}" refers to a valid mutex application component.',
						array('{id}' => $this->mutexID)));
			}
		}
		return $this->_mutex;
	}
	
	/**
	 * Checks that all required components are installed for the translate module
	 * @return boolean Whether translate module is installed or not
	 */
	public function getIsInstalled()
	{
		Yii::import('translate.widgets.configurationStatus.ConfigurationStatusModel');
		foreach(array(
			$this->translatorID,
			$this->messageSourceID,
			$this->viewSourceID,
			$this->viewRendererID) as $componentID)
		{
			$confStatModel = new ConfigurationStatusModel($componentID);
			if($confStatModel->hasErrors() || !$confStatModel->validate())
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Get the runtime path for this module and its components
	 *
	 * @return string the runtime path for this module and its components
	 */
	public function getRuntimePath()
	{
		return Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.$this->getId();
	}
	
	public static function translator($translateModuleID = null)
	{
		return self::findModule($translateModuleID)->getTranslator();
	}
	
	public static function messageSource($translateModuleID = null)
	{
		return self::findModule($translateModuleID)->getMessageSource();
	}
	
	public static function viewSource($translateModuleID = null)
	{
		return self::findModule($translateModuleID)->getViewSource();
	}
	
	public static function viewRenderer($translateModuleID = null)
	{
		return self::findModule($translateModuleID)->getViewRenderer();
	}
	
	public static function mutex($translateModuleID = null)
	{
		return self::findModule($translateModuleID)->getMutex();
	}
	
	/**
	 * Internal translate function.
	 * The same as Yii's translate function, but messages are translated using the specified translate module's category.
	 * Also if an exception occurs the original message will be returned instead of the exception being thrown.
	 * This is necessary to show the configuration status page even when some basic module components are not properly configured.
	 *
	 * @param string $message The message to translate
	 * @param array $params The parameters of the message
	 * @return string The translated message
	 * @see YiiBase::t
	 */
	public function t($message, $params = array())
	{
		try
		{
			return Yii::t($this->tCategory, $message, $params);
		}
		catch(Exception $e)
		{
			return $message;
		}
	}
	
	/**
	 * Internal translate function. 
	 * The same as Yii's translate function, but messages are translated using the specified translate module's category.
	 * Also if an exception occurs the original message will be returned instead of the exception being thrown. 
	 * This is necessary to show the configuration status page even when some basic module components are not properly configured.
	 * 
	 * @param string $message The message to translate
	 * @param array $params The parameters of the message
	 * @param string $translateModuleID The ID of translate module the message is being translated for. 
	 * 	Defaults to null meaning the currently executing translate module.
	 * 	An exception will be thrown if the translate module ID is not found or the module is not currently active.
	 * @return string The translated message
	 * @see TranslateModule::t
	 */
	public static function translate($message, $params = array(), $translateModuleID = null)
	{
		return self::findModule($translateModuleID)->t($message, $params);
	}
	
	/**
	 * Finds a translate module given its ID.
	 * An exception will be thrown if the module is not found or is not an instance of TranslateModule
	 * 
	 * @param string $moduleID The ID of the translate module to find. Defaults to null meaning find the currently active TranslateModule.
	 * @throws CException Thrown if the module is not found or was not an instance of TranslateModule
	 * @return TranslateModule The translate module with the specified ID
	 */
	public static function findModule($moduleID = null)
	{
		if($moduleID === null)
		{
			if(self::$_module === null)
			{
				throw new CException('A TranslateModule was not specified and call was not made within the context of a TranslateModule instance.');
			}
			return self::$_module;
		}
		$module = self::_findModule(Yii::app(), $moduleID);
		if($module === null)
		{
			throw new CException('Unable to locate translate module with ID "'.$moduleID.'". Make sure that the translate module with ID "'.$moduleID.'" is properly configured.');
		}
		else if(!$module instanceof TranslateModule)
		{
			throw new CException('The module with ID "'.$moduleID.'" was found, but was not an instance of TranslateModule.');
		}
		return $module;
	}
	
	private static function _findModule($parentModule, $moduleID)
	{
		if(($module = $parentModule->getModule($moduleID)) === null)
		{
			foreach($parentModule->getModules() as $module => $config)
			{
				if(($module = $parentModule->getModule($module)) !== null && ($module = self::_findModule($module, $moduleID)) !== null)
				{
					break;
				}
			}
		}
		return $module;
	}
	
	/**
	 * Utility function for determining whether a locale ID is supported by Yii
	 * 
	 * @param string $id A locale ID
	 * @return boolean True if the locale is supported by Yii. False otherwise.
	 */
	public static function isLocaleSupported($id)
	{
		static $flippedLocales;
		if($flippedLocales === null)
		{
			$flippedLocales = array_flip(CLocale::getLocaleIDs());
		}
		return array_key_exists(CLocale::getCanonicalID($id), $flippedLocales);
	}
	
	/**
	 * Utility function to get the display names of a list of locale IDs in their respective languages
	 * Unknown locales will be ignored
	 * 
	 * @param mixed $ids array A list of CLocale IDs to find display names for. string a single CLocale ID to find the display name for.
	 * @param boolean $useGenericLocales Whether to use generic locales. If True only the language ID portion of the locale will be used to identify the locale. Defaults to false.
	 * @param boolean $localizeNames Whether to localize (translate) the names. Defaults to True meaning display names will be returned in their native languages. If False display names will be returned in the application's current language.
	 * @return mixed If $ids was an array then another array of local display names in the form array("locale ID" => "native locale display name", ...) will be returned. Otherwise just the locale display name will be returned. 
	 */
	public static function getLocaleDisplayNames($ids, $useGenericLocales = false, $localizeNames = true)
	{
		$languages = array();
		$locale = Yii::app()->getLocale();
		foreach((array)$ids as $localeID)
		{
			if($useGenericLocales)
			{
				$localeID = $locale->getLanguageID($localeID);
			}
			if(array_key_exists($localeID, $languages) || !self::isLocaleSupported($localeID))
			{
				continue;
			}
			$locale = $localizeNames ? CLocale::getInstance($localeID) : Yii::app()->getLocale();
			$languages[$localeID] = $locale->getLanguage($localeID);
			if($languages[$localeID] === null)
			{
				$languages[$localeID] = $localeID;
			}
			else if(!$useGenericLocales && ($territory = $locale->getTerritory($localeID)) !== null)
			{
				if($locale->getOrientation() === 'ltr')
				{
					$languages[$localeID] = $languages[$localeID].' '.$territory;
				}
				else
				{
					$languages[$localeID] = $territory.' '.$languages[$localeID];
				}
			}
		}
		return is_array($ids) ? $languages : array_pop($languages);
	}
	
	/**
	 * Gets the named component and verifies that it is not null and is of the specified type.
	 *
	 * @param string $componentId The ID of the component to get and verify.
	 * @param mixed $typeClass Either the name of the class or the object that the component should be the same type as.
	 * @throws CException Throw if the component is null (disabled or not configured) or if the component is not the correct type.
	 * @return mixed The named component instance
	 */
	public static function getComponentAndVerify($componentId, $typeClass)
	{
		$component = Yii::app()->getComponent($componentId);
		if($component === null)
		{
			throw new CException('The component "'.$componentId.'" is either disabled or does not exist.');
		}
		else
		{
			$reflection = new ReflectionClass($typeClass);
			if(!$reflection->isInstance($component))
			{
				throw new CException('The component "'.$componentId.'" must be of type "'.$typeClass.'".');
			}
		}
		return $component;
	}

}
