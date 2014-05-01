<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class LanguageSelector extends CWidget
{
	
	public $languages = array();
	
	public $selectedLanguage = '';
	
	public $languageVarName = 'language';

	public function run()
	{
		$this->render('LanguageSelector',
				array(
					'selectedLanguage' => $this->selectedLanguage,
					'languages' => $this->languages,
					'languageVarName' => $this->languageVarName
				)
		);
	}

}
?>