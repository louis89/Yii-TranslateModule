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
					'id' => $this->getId(),
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
			$this->_languages = TranslateModule::getLocaleDisplayNames(CLocale::getLocaleIDs());
		}
		return $this->_languages;
	}

}
?>