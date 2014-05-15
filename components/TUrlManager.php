<?php

Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TUrlManager extends CUrlManager implements ITranslateModuleComponent
{
	
	private $_translateModuleID = TranslateModule::ID;
	
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

	/**
	 * (non-PHPdoc)
	 * @see CUrlManager::createUrl()
	 */
	public function createUrl($route, $params = array(), $ampersand = '&')
	{
		$languageVarName = $this->getTranslateModule()->getTranslator()->languageVarName;
		if(!isset($params[$languageVarName]))
		{
			$params[$languageVarName] = Yii::app()->getLanguage();
		}
		return parent::createUrl($route, $params, $ampersand);
	}

	/**
	 * (non-PHPdoc)
	 * @see CUrlManager::parseUrl()
	 */
	public function parseUrl($request)
	{
		$route = parent::parseUrl($request);
		$translator = $this->getTranslateModule()->getTranslator();
		
		// First check if GET language parameter is set and is not supported by Yii.
		// If true, this probably means a language was not specified in the URL
		// which means this language is actually supposed to be part of the route.
		// So fix up the route and unset the GET parameter language.
		if(isset($_GET[$translator->languageVarName]) && !TranslateModule::isLocaleSupported($_GET[$translator->languageVarName]))
		{
			$route = $_GET[$translator->languageVarName].'/'.$route;
			unset($_GET[$translator->languageVarName]);
		}

		// Determine language setting

		// POST
		if(isset($_POST[$translator->languageVarName]))
		{
			$language = $_POST[$translator->languageVarName];
			unset($_POST[$translator->languageVarName]);
			// If the GET parameter was also set then assume the client is switching languages 
			// so unset the GET parameter because it is now obsolete.
			if(isset($_GET[$translator->languageVarName]))
			{
				unset($_GET[$translator->languageVarName]);
			}
		}
		// GET
		else if(isset($_GET[$translator->languageVarName]))
		{
			$language = $_GET[$translator->languageVarName];
		}
		// Session
		else if(Yii::app()->getUser()->hasState($translator->languageVarName))
		{
			$language = Yii::app()->getUser()->getState($translator->languageVarName);
		}
		// Cookie
		else if($request->getCookies()->contains($translator->languageVarName))
		{
			$language = $request->getCookies()->itemAt($translator->languageVarName)->value;
		}
		// Client's preferred language setting
		else if($request->getPreferredLanguage() !== false)
		{
			$language = $request->getPreferredLanguage();
		}
		// Application's default language
		else
		{
			$language = Yii::app()->getLanguage();
		}

		// Process language:
		
		// If the language is not supported by Yii set language to the application's default language
		if(!TranslateModule::isLocaleSupported($language))
		{
			$language = Yii::app()->getLanguage();
		}

		$messageSource = $this->getTranslateModule()->getMessageSource();
		
		if($messageSource instanceof TDbMessageSource)
		{
			// If we should enforce accepted languages only and the language is not acceptable set it to the application's default language.
			if($messageSource->acceptedLanguagesOnly && !$messageSource->isAcceptedLanguage($language))
			{
				$language = Yii::app()->getLanguage();
			}
			
			// Canonicalize the language if we are using generic locales
			if($messageSource->useGenericLocales)
			{
				$language = Yii::app()->getLocale()->getLanguageID($language);
			}
		}

		// Set application's current language to derived language.
		$translator->setLanguage($language);

		// Check that the URL contained the correct language GET parameter. If not redirect to the same URL with language GET parameter set.
		if(!array_key_exists($translator->languageVarName, $_GET) || $_GET[$translator->languageVarName] !== $language)
		{
			$_GET[$translator->languageVarName] = $language;
			$request->redirect(
					Yii::app()->createUrl($route, $_GET),
					true,
					($request->getIsPostRequest() && isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') ? 303 : 302
			);
		}
		else
		{
			unset($_GET[$translator->languageVarName]);
		}

		return $route;
	}

}