<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class LanguageSelector extends CWidget
{
	
	public $selectedLanguage;
	
	public $languageVarName = 'language';
	
	private $_languages;
	
	public function init()
	{
		if($this->selectedLanguage === null)
		{
			$this->selectedLanguage = Yii::app()->getLanguage();
		}
	}

	public function run()
	{
		$this->render('LanguageSelector',
				array(
					'selectedLanguage' => $this->selectedLanguage,
					'languages' => $this->getLanguages(),
					'languageVarName' => $this->languageVarName
				)
		);
	}
	
	public function setLanguages($languages)
	{
		$this->_languages = $languages;
	}
	
	public function getLanguages()
	{
		if($this->_languages === null)
		{
			$this->_languages = self::getDisplayNames(CLocale::getLocaleIDs());
		}
		return $this->_languages;
	}
	
	public static function getDisplayNames($localeIDs, $useGenericLocales = false)
	{
		$languages = array();
		$locale = Yii::app()->getLocale();
		foreach($localeIDs as $localeID)
		{
			if($useGenericLocales)
			{
				$localeID = $locale->getLanguageID($localeID);
			}
			if(array_key_exists($localeID, $languages))
			{
				continue;
			}
			$locale = CLocale::getInstance($localeID);
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
		return $languages;
	}

}
?>