<?php

Yii::import(TranslateModule::ID.'.widgets.configurationStatus.ConfigurationStatus');
Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

class TViewRenderer extends CViewRenderer implements ConfigurationStatus, ITranslateModuleComponent
{
	
	/**
	 * The regular expression used to extract translate tags inside views.
	 *
	 * @name TTranslator::TRANSLATE_TAG_REGEX
	 * @type string
	 * @const string
	 */
	const TRANSLATE_TAG_REGEX = '/\{t(?:\s+category\s*=\s*[\'"](.*?)[\'"])?(?:\s*params\s*=\s*([\'"].*?[\'"]\s*=>\s*[\'"].*?[\'"])+)?(?:\s+source\s*=\s*[\'"](.*?)[\'"])?(?:\s+language\s*=\s*[\'"](.*?)[\'"])?\}\s*(.+?)\s*\{\/t\}/s';
	
	/**
	 * The regular expression used to parse the params option of translate tags.
	 *
	 * @name TTranslator::PARAM_PARSE_REGEX
	 * @type string
	 * @const string
	 */
	const PARAM_PARSE_REGEX = '/[\'"](.*?)[\'"]=>[\'"](.*?)[\'"](?:\s*,\s*|\s*$)/';
	
	private $_translateModuleID;
	
	private $_viewSource;
	
	public function init()
	{
		parent::init();
		if($this->getTranslateModuleID() === null)
		{
			$this->setTranslateModuleID(TranslateModule::ID);
		}
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
		if($this->_viewSource instanceof TDbViewSource)
		{
			$this->_viewSource->detachEventHandler('onMissingTranslation', array($this, 'missingTranslation'));
			$this->_viewSource = null;
		}
		$this->_translateModuleID = $translateModuleID;
		$this->_viewSource = $this->getTranslateModule()->getViewSource();
		if($this->_viewSource instanceof TDbViewSource)
		{
			$this->_viewSource->attachEventHandler('onMissingTranslation', array($this, 'missingTranslation'));
		}
	}
	
	public function getViewSource()
	{
		return $this->_viewSource;
	}
	
	public function attributeNames()
	{
		return array(
			'translateModuleID',
		);
	}
	
	public function attributeRules()
	{
		return array(
			array('translateModuleID', 'required'),
			//array('viewSourceID', 'translate.validators.ComponentValidator', 'type' => 'TDbViewSource'),
		);
	}
	
	public function attributeLabels()
	{
		$module = $this->getTranslateModule();
		return array(
			'translateModuleID' => $module->t('Translate Module ID'),
		);
	}
	
	public function attributeDescriptions()
	{
		$module = $this->getTranslateModule();
		return array(
			'translateModuleID' => $module->t('The translate module that this view source belongs to. Defaults to the value of TranslateModule::ID.'),
		);
	}
	
	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component is responsible for rendering views containing message translation tags like "{t}message to be translated{/t}". The message translation tags will simply be stripped out by this component such that "{t}message to be translated{/t}" becomes "message to be translated". The messages will NOT be translated by this particular renderer. Please use "TViewTranslator" to have the messages translated, or implement your own renderer by extending this class.');
	}

	/**
	 * Parses the source view file and saves the results as another file.
	 * This method is required by the parent class.
	 * @param string $sourcePath the source view file path
	 * @param string $compiledPath the resulting view file path
	 * @param string $route The route that requested this view.
	 * If not set the route name 'default' will be used.
	 * @param string $source The name of the translated messsage source component to use.
	 * If not set the message source component name at {@link TTranslator::messageSource} will be used.
	 * @param string $language The language that this view is being translated to.
	 * If not set the application's current language will be used.
	 * @param boolean $useTransaction If true all database queries will be wrapped in a transaction.
	 */
	public function generateViewFile($sourcePath, $compiledPath)
	{
		if(($mutex = $this->getTranslateModule()->getMutex()) !== null)
		{
			$mutex->acquire($sourcePath.'.'.$compiledPath);
		}
		try
		{
			if(is_dir($compiledPathDir = dirname($compiledPath)) === false)
			{
				if(@mkdir($compiledPathDir, $this->filePermission, true) === false)
				{
					throw new CException($this->getTranslateModule()->t("The compiled view directory '{dir}' does not exist and could not be created.", array('{dir}' => $compiledPathDir)));
				}
			}
				
			$sourceViewContents = @file_get_contents($sourcePath);
			if($sourceViewContents === false)
			{
				throw new CException($this->getTranslateModule()->t("Unable to read the contents of the source view at path '{path}'.", array('{path}' => $sourcePath)));
			}

			if(@file_put_contents($compiledPath, preg_replace(self::TRANSLATE_TAG_REGEX, '$5', $sourceViewContents)) === false)
			{
				throw new CException($this->getTranslateModule()->t("Failed to create compiled view file at path '{path}'.", array('{path}' => $compiledPath)));
			}
			@chmod($compiledPath, $this->filePermission);
		}
		catch(Exception $e)
		{
			if(file_exists($compiledPath))
			{
				@unlink($compiledPath);
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

	/**
	 * (non-PHPdoc)
	 * @see CViewRenderer::renderFile()
	 */
	public function renderFile($context, $sourceFile, $data, $return)
	{
		return $context->renderInternal($this->getViewSource()->translate($context, $sourceFile), $data, $return);
	}

	/**
	 * Generates the resulting view file path.
	 * @param string $file source view file path.
	 * @param string $language the language of the view we are looking for.
	 * If not set the application's current language setting will be used.
	 * @return string resulting view file path.
	 */
	public function getViewFile($file, $language = null)
	{
		if($language === null)
		{
			$language = Yii::app()->getLanguage();
		}

		if($this->useRuntimePath)
		{
			return $this->getTranslateModule()->getRuntimePath().DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.sprintf('%x', crc32(__CLASS__.Yii::getVersion().dirname($file))).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.basename($file);
		}
		return $file.'c.'.$language;
	}

	/**
	 * This method handles on missing view translation events
	 *
	 * @param TMissingViewTranslationEvent $event
	 */
	public function missingTranslation($event)
	{
		$compiledPath = $this->getViewFile($event->path, $event->language);
		$this->generateViewFile($event->path, $compiledPath, $event->route, $event->language);
		$event->path = $compiledPath;
	}

}