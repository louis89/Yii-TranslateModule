<?php
$clientScript = Yii::app()->getClientScript();
$clientScript->registerCssFile($this->getStylesUrl('messageForm.css', 'message'));
$clientScript->registerScriptFile($this->getScriptsUrl('jquery.messageForm.js', 'message'));

$action = $Message->getIsNewRecord() ? 'Create' : 'Update';

$dialog = $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
	'id' => $id,
	//'target' => $id.'-submit',
	'options' => array(
		'title' => TranslateModule::translate($action.' Translation'),
		'autoOpen' => false,
		'modal' => true,
		'width' => 'auto',
		'height' => 'auto'
	),
));
?>
<div class="form">
	<?php 
	$form = $this->beginWidget('CActiveForm',
			array(
				'action' => $this->createUrl('message/view'),
				'enableAjaxValidation' => true,
				'enableClientValidation' => true,
				'clientOptions' => array(
					'validateOnSubmit' => true,
					'afterValidate' => 'js:'.
						'function(form, data, hasError){'.
							'if (!hasError)'.
							'{'.
								'$("#'.$id.'").tMessageForm("submit");'.
								'return false;'.
							'}'.
						'}'
				),
			)
	);
	?>
	<p id="<?php echo $id; ?>-status" class="status hide"></p>
	<?php
	echo $form->errorSummary($Message);
	?>
	<p class="note">
		<?php echo TranslateModule::translate('Fields with {span} are required.', array('{span}' => '<span class="required">*</span>')); ?>
	</p>
	<div class="row">
		<?php 
		echo CHtml::label(TranslateModule::translate('Source Language'), CHtml::activeId($MessageSource, 'language_id'));
		echo $form->hiddenField($MessageSource, 'language_id');
		echo '<p id="MessageSource_language"></p>';
		?>
	</div>
	<div class="row">
		<?php 
		echo $form->label($MessageSource, 'message');
		echo $form->textArea($MessageSource, 'message', array('readonly' => 'readonly', 'rows' => 3, 'cols' => 90));
		echo $form->error($MessageSource, 'message');
		?>
	</div>
	<div class="row">
		<?php 
		echo $form->labelEx($Message, 'language_id');
		if($Message->getIsNewRecord())
		{
			echo $form->dropDownList($Message, 'language_id', CHtml::listData(Language::model()->with(array('missingTranslations' => $Message->id))->findAll(), 'id', 'name'));
		}
		else
		{
			echo $form->hiddenField($Message, 'language_id');
			echo '<p id="Message_language"></p>';
		}
		echo $form->error($Message, 'language_id'); 
		?>
	</div>
	<div class="row">
		<?php 
		echo $form->labelEx($Message, 'translation');
		echo $form->textArea($Message, 'translation', array('rows' => 3, 'cols' => 90));
		echo $form->error($Message, 'translation'); 
		?>
	</div>
	<?php
	echo $form->hiddenField($Message, 'id');
	echo CHtml::hiddenField('_method', $Message->getIsNewRecord() ? 'POST' : 'PUT'); 
	?>
	<div class="row buttons">
		<?php 
		echo CHtml::submitButton(TranslateModule::translate($action), array('id' => $id.'-submit'));
		
		$this->widget('translate.widgets.translator.TranslatorWidget',
				array(
					'selectors' => array(
						'message' => '#'.$id.' #MessageSource_message',
						'sourceLanguage' => '#'.$id.' #MessageSource_language_id',
						'targetLanguage' => '#'.$id.' #Message_language_id',
						'target' => "#$id #Message_translation",
					)
				)
		);
		?>
	</div>
	<?php $this->endWidget($form->getId()); ?>
</div>
<?php 
$this->endWidget($dialog->getId()); 
$options = array(
		'id' => $id,
		'scenario' => $Message->getScenario(),
		'loading' => true,
		'createText' => TranslateModule::translate('Create'),
		'saveText' => TranslateModule::translate('Save'),
		'loadingClass' => 'loading',
		'status' => array('success' => 'success', 'message' => null)
	);
if(isset($clientOptions))
{
	$options = array_merge($options, $clientOptions);
}
$clientScript->registerScript(__CLASS__.'-'.$id, "jQuery('#$id').tMessageForm(".CJavaScript::encode($options).");");
?>

