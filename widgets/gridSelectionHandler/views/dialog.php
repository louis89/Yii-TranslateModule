<?php
$clientScript = Yii::app()->getClientScript();
$clientScript->registerScriptFile($assetsUrl.'/scripts/jquery.selectionHandler.js');
$clientScript->registerScript(__CLASS__.$id, "jQuery('#$id').tSelectionHandler(".CJavaScript::encode($selectionHandlerOptions).");");

echo CHtml::button($buttonText, $buttonHtmlOptions);

$dialogWidget = $this->beginWidget(
	'zii.widgets.jui.CJuiDialog',
	array(
		'id' => $id,
		'options' => $dialogOptions,
	)
);

	echo CHtml::tag('p', array('id' => $selectionHandlerOptions['statusId'], 'class' => $statusCssClass));
	$this->widget(
		'zii.widgets.jui.CJuiProgressBar', 
		$progressBarOptions
	);
	
$this->endWidget('zii.widgets.jui.CJuiDialog');

$clientScript->registerCssFile($assetsUrl.'/styles/selectionHandler.css');
?>