<?php

Yii::import(TranslateModule::ID.'.components.translators.DefaultTranslator');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class GoogleTranslator extends DefaultTranslator
{

	/**
	 * @var integer Maximum time in seconds before a query to Google translate will time out
	 */
	public $googleQueryTimeLimit = 30;

	/**
	 * @var integer maximum number of characters to allow in a single query to Google
	 */
	public $googleMaxChars = 5000;

	/**
	 * @var string URL to Google's translation service
	 */
	public $googleTranslateUrl = 'https://www.googleapis.com/language/translate/v2';

	/**
	 * @var string $googleApiKey your google translate api key
	 * set this if you wish to use Google's translate service to translate the messages
	 * if empty it will not use the Google's translate API service
	*/
	public $googleApiKey = null;
	
	/**
	 * @var array Additional CURL options.
	 */
	public $googleCurlOptions = array(CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4);
	
	public $cachingDuration = 0;
	
	public $cacheID = 'cache';
	
	private $_cache = array();
	
	public function attributeNames()
	{
		return array_merge(parent::attributeNames(), array(
			'googleQueryTimeLimit',
			'googleMaxChars',
			'googleTranslateUrl',
			'googleApiKey',
			'googleCurlOptions',
			'cachingDuration'
		));
	}
	
	public function attributeRules()
	{
		return array_merge(parent::attributeRules(), array(
			array('googleApiKey', 'length', 'allowEmpty' => false),
			array('googleQueryTimeLimit, googleMaxChars, cachingDuration', 'numerical', 'allowEmpty' => false, 'integerOnly' => true, 'min' => 0),
			array('googleTranslateUrl', 'url', 'allowEmpty' => false),
			array('googleCurlOptions', 'type', 'allowEmpty' => false, 'type' => 'array')
		));
	}
	
	public function attributeLabels()
	{
		$module = $this->getTranslateModule();
		return array_merge(parent::attributeLabels(), array(
			'googleQueryTimeLimit' => $module->t('Google Query Time Limit'),
			'googleMaxChars' => $module->t('Google Query Maximum Characters'),
			'googleTranslateUrl' => $module->t('Google Translate API URL'),
			'googleApiKey' => $module->t('Google Translate API Key'),
			'googleCurlOptions' => $module->t('Google CURL Options'),
			'cachingDuration' => $module->t('Caching Duration'),
		));
	}
	
	public function attributeDescriptions()
	{
		$module = $this->getTranslateModule();
		return array_merge(parent::attributeDescriptions(), array(
			'googleQueryTimeLimit' => $module->t('The maximum number of seconds allowed for a single Google query to complete. Defaults to 30 seconds.'),
			'googleMaxChars' => $module->t('Maximum number of characters allowed for a single Google translate query. Defaults to 5000 characters.'),
			'googleTranslateUrl' => $module->t('URL to the Google Translate API.'),
			'googleApiKey' => $module->t('Your API key for access to your Google Translate account.'),
			'googleCurlOptions' => $module->t('Additional CURL options for Google translate requests. Default is empty.'),
			'cachingDuration' => $module->t('Time in seconds to cache locale display names. Defaults to 0 meaning do not cache.'),
		));
	}
	
	public function getDescription()
	{
		return $this->getTranslateModule()->t('This component implements the "translate" method of the default translator using Google Translate. All messages will be translated using Google and their related attributes will be stored in the configured message source component by this translator.');
	}

	/**
	 * Get a list of all languages accepted by Google translate.
	 *
	 * @return array A list of the language local IDs accepted by Google translate.
	 */
	public function getGoogleAcceptedLanguages()
	{
		$cacheKey = $this->getTranslateModuleID() . '-cache-google-accepted-languages';
		if(!isset($this->_cache[$cacheKey]))
		{
			if(($cache = Yii::app()->getCache()) === null || ($languages = $cache->get($cacheKey)) === false)
			{
				$queryLanguages = $this->queryGoogle(array(), $this->googleTranslateUrl.'/languages');
				if($queryLanguages === false)
				{
					Yii::log('Failed to query Google\'s accepted languages.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
					return false;
				}
				foreach($queryLanguages['languages'] as $language)
				{
					$languages[] = $language['language'];
				}
				asort($languages, SORT_LOCALE_STRING);
				if($cache !== null)
				{
					$cache->set($cacheKey, $languages, $this->cachingDuration);
				}
			}
			$this->_cache[$cacheKey] = $languages;
		}
		return $this->_cache[$cacheKey];
	}

	/**
	 * Get whether a language ID is accepted by Google Translate.
	 *
	 * @param string $languageId The language ID
	 * @return boolean true if the language ID is accepted by Google Translate, false otherwise.
	 */
	public function isGoogleAcceptedLanguage($id)
	{
		return array_key_exists($id, $this->getGoogleAcceptedLanguages());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TTranslator::translate()
	 */
	public function translate($message, $sourceLanguage, $targetLanguage)
	{
		try
		{
			return $this->googleTranslate($message, $sourceLanguage, $targetLanguage);
		}
		catch(CharacterLimitExceededException $clee)
		{
			Yii::log($clee->getMessage(), CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
		}
		return parent::translate($message, $sourceLanguage, $targetLanguage);
	}

	/**
	 * translate some message from $sourceLanguage to $targetLanguage using google translate api
	 * googleApiKey must be defined to use this service
	 * @param string $message to be translated
	 * @param mixed $targetLanguage language to translate the message to,
	 * if null it will use the current language in use,
	 * if an array then the message will be translated into each language and an associative array of translations in the form of language=>translation will be returned.
	 * @param mixed $sourceLanguage language that the message is written in,
	 * if null it will use the application source language
	 * @return string translated message
	 */
	public function googleTranslate($message, $sourceLanguage = null, $targetLanguage = null)
	{	
		if($sourceLanguage === null)
		{
			$sourceLanguage = Yii::app()->sourceLanguage;
		}
		
		if(empty($sourceLanguage))
		{
			throw new CException('Source language must be defined');
		}
		
		if($targetLanguage === null)
		{
			$targetLanguage = Yii::app()->getLanguage();
		}

		if($targetLanguage === $sourceLanguage)
		{
			return (string)$message;
		}

		$msg = (string)$message;
		if(strlen($msg) > $this->googleMaxChars)
		{
			throw new CharacterLimitExceededException(strlen($msg), $this->googleMaxChars);
		}

		preg_match_all('/\{(?:.*?)\}/s', $msg, $yiiParams);
		$yiiParams = $yiiParams[0];
		$escapedYiiParams = array();
		foreach($yiiParams as $key => &$match)
		{
			$escapedYiiParams[$key] = "<span class='notranslate'>:mpt$key</span>";
		}
		
		$msg = str_replace($yiiParams, $escapedYiiParams, $msg);

		$query = $this->queryGoogle(array('q' => $msg, 'source' => $sourceLanguage, 'target' => $targetLanguage), $this->googleTranslateUrl);

		if($query === false)
		{
			return false;
		}
		
		if(isset($query['translatedText']))
		{
			$msg = $query['translatedText'];
		}
		else if(isset($query['translations']))
		{
			$msg = $query['translations'][0]['translatedText'];
		}
		else 
		{
			return false;
		}

		return str_replace($escapedYiiParams, $yiiParams, $msg);
	}

	/**
	 * query google translate api
	 *
	 * @param array $args
	 * @return array the google response object
	 */
	protected function queryGoogle($args = array(), $googleTranslateUrl = null)
	{
		if(!isset($args['key']))
		{
			if(empty($this->googleApiKey))
			{
				throw new CException($this->getTranslateModule()->t('You must provide your google API key in property "googleApiKey".'));
			}
			$args['key'] = $this->googleApiKey;
		}

		if(!isset($args['format']))
		{
			$args['format'] = 'html';
		}
		
		if($googleTranslateUrl === null)
		{
			$googleTranslateUrl = $this->googleTranslateUrl;
		}

		$trans = false;
		$queryString = preg_replace('/%5B\d+%5D/', '', http_build_query($args));
		
		if(in_array('curl', get_loaded_extensions()))
		{
			if($curl = curl_init($googleTranslateUrl))
			{
				$curlOpts = $this->googleCurlOptions;
				$curlOpts[CURLOPT_RETURNTRANSFER] = true;
				$curlOpts[CURLOPT_POSTFIELDS] = $queryString;
				$curlOpts[CURLOPT_HTTPHEADER] = array('X-HTTP-Method-Override: GET');
				$curlOpts[CURLOPT_TIMEOUT] = $this->googleQueryTimeLimit;
				if(curl_setopt_array($curl, $curlOpts))
				{
					if(!$trans = curl_exec($curl))
					{
						$trans = '{ "error": { "errors": [ { "domain": "cURL", "reason": "Failed to execute cURL request", "message": "'.curl_error($curl).'" } ], "code": '.curl_errno($curl).', "message": "'.curl_error($curl).'" } }';
					}
				}
				else
				{
					Yii::log('Failed to set cURL options.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
				}
				curl_close($curl);
			}
			else
			{
				Yii::log('Failed to initialize cURL.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
			}
		}
		else
		{
			Yii::log('cURL extension not found. Falling back to file_get_contents() to read Google translation query response.', CLogger::LEVEL_INFO, $this->getTranslateModuleID());
			$trans = file_get_contents($googleTranslateUrl.'?'.$queryString);
		}

		if(!$trans)
		{
			Yii::log('Failed to query Google for message translation. Args: ' . print_r($args, true), CLogger::LEVEL_WARNING, $this->getTranslateModuleID());
			return false;
		}

		$trans = CJSON::decode($trans);

		if(isset($trans['error']))
		{
			Yii::log('Google translate error: '.$trans['error']['code'].'. '.$trans['error']['message'], CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
			return false;
		}
		elseif(!isset($trans['data']))
		{
			Yii::log('Google translate error: '.print_r($trans, true), CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
			return false;
		}
		else
		{
			return $trans['data'];
		}
	}

}

class CharacterLimitExceededException extends CException
{
	
	public function __construct($charCount, $maxAllowed, $translateModuleID = TranslateModule::ID)
	{
		parent::__construct(
			TranslateModule::findModule($translateModuleID)->t(
				'The message requested to be translated is {messageLength} characters long. A maximum of {characters} characters is allowed.',
				array('{characters}' => $maxAllowed, '{messageLength}' => $charCount)
			)
		);
	}
	
}