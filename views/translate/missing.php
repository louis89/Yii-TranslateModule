<?php
Yii::app()->getClientScript()->registerCssFile($this->getStylesUrl('missing.css')); 
$id = TranslateModule::ID.'-missing'; 
?>
<h1>
	<?php echo TranslateModule::translate('Translate missing');?>
</h1>
<div class="column-fill">
	<div class="form box-white">
		<?php 
		echo CHtml::beginForm('', 'POST', array('id' => $id.'-form'));
		$this->widget('zii.widgets.CListView', array(
					'dataProvider' => new TArrayDataProvider($MissingMessage),
					'id' => $id.'-messages',
					'pager' => array(
						'id' => $id.'-pager',
						'class' => 'CLinkPager',
					),
					'viewData' => array(
						'autoTranslate' => $translator->autoTranslate
					),
					'itemView' => '_missing_message',
		));
		echo CHtml::submitButton(TranslateModule::translate('Save Translations'));
		if($translator->autoTranslate)
		{
			echo CHtml::button(TranslateModule::translate('Auto Translate'), array('id' => $id.'-translate-all'));
			echo CHtml::script(
					'$("#'.$id.'-translate-all").click(function(){' .
						'var formData = $("#'.$id.'-form").serializeArray();' .
						'var pattern = /^MissingMessage\[\d+\]\[message\]$/;'.
						'for(var i = 0; i < formData.length; i++){' .
							'if(pattern.test(formData[i].name)){'.
								'$("textarea[name=\'"+formData[i].name+"\']").translator("translate");'.
							'}'.
						'}'.
						'return false;' .
					'});');
		}
		echo CHtml::endForm();
		?>
	</div>
</div>
