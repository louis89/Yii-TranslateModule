<?php
$installerOptions['id'] = $id;
$clientScript = Yii::app()->getClientScript();
$clientScript->registerScriptFile($assetsUrl.'/jquery.installComponent.js');
?>
<div id="<?php echo $id; ?>">
	<?php 
	echo CHtml::button($buttonText, $buttonHtmlOptions);
	
	$dialogWidget = $this->beginWidget(
		'zii.widgets.jui.CJuiDialog',
		array(
			'id' => $installerOptions['dialogId'],
			'options' => $dialogOptions,
		)
	);
	
		echo CHtml::tag('p', array('id' => $installerOptions['statusId'], 'class' => $statusCssClass));
		$this->widget(
			'zii.widgets.jui.CJuiProgressBar', 
			$progressBarOptions
		);
		
	$this->endWidget('zii.widgets.jui.CJuiDialog');
	?>
</div>
<?php 
$clientScript->registerCssFile($assetsUrl.'/installer.css');
$clientScript->registerScript(__CLASS__.$id, "jQuery('#$id').tInstaller(".CJavaScript::encode($installerOptions).");");
?>
