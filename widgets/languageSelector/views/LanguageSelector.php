<div id="<?php echo $id; ?>">
<?php 
	// Render options as dropDownList
	echo CHtml::form();
	foreach($languages as $key => $lang) 
	{
		echo CHtml::hiddenField($key, $this->getOwner()->createUrl('', array($languageVarName => $key)));
	}
	echo CHtml::dropDownList($languageVarName, $selectedLanguage, $languages, array('submit' => ''));
	echo CHtml::endForm();
?>
</div>