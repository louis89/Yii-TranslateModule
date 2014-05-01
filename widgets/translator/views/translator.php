<?php
$translatorOptions['id'] = $id;
$clientScript = Yii::app()->getClientScript();
$clientScript->registerCssFile($assetsUrl.'/translator.css');
$clientScript->registerScriptFile($assetsUrl.'/jquery.translator.js');
$clientScript->registerScript(__CLASS__.'-'.$id, "jQuery('".$translatorOptions['selectors']['message']."').translator(".CJavaScript::encode($translatorOptions).");");

if(!isset($htmlOptions['id']))
{
	$htmlOptions['id'] = $id.'-button';
}

$htmlOptions['onclick'] = 'jQuery("'.$translatorOptions['selectors']['message'].'").translator("translate"';
if($sourceLanguage !== null)
{
	$htmlOptions['onclick'] .= ', "'.$sourceLanguage.'"';
	if($targetLanguage !== null)
	{
		$htmlOptions['onclick'] .= ', "'.$targetLanguage.'"';
	}
}
$htmlOptions['onclick'] .= ')';

echo CHtml::button($label, $htmlOptions);
echo CHtml::tag('p', array('class' => $statusCssClass.' '.$errorCssClass.' '.$translatorOptions['hiddenCssClass'], 'id' => $id.'-status'));
?>