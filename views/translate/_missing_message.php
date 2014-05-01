<?php 
$name = 'MissingMessage['.$index.']';
$id = $widget->getId().'-'.$index;
?>
<table id="<?php echo $id; ?>" class="missing">
	<tr>
		<td>
			<?php 
			echo CHtml::activeLabelEx($data, 'category', array('name' => $name.'[category]')); 
			echo CHtml::activeTextField($data, 'category', array('name' => $name.'[category]', 'readonly' => 'readonly'));
			echo CHtml::error($data, 'category', array('name' => $name.'[category]')); 
			?>
		</td>
		<td>
			<?php 
			echo CHtml::activeLabelEx($data, 'sourceLanguage', array('name' => $name.'[sourceLanguage]'));
			echo CHtml::activeTextField($data, 'sourceLanguage', array('name' => $name.'[sourceLanguage]', 'readonly' => 'readonly')); 
			echo CHtml::error($data, 'sourceLanguage', array('name' => $name.'[sourceLanguage]')); 
			?>
		</td>
		<td>
			<?php 
			echo CHtml::activeLabelEx($data, 'targetLanguage', array('name' => $name.'[targetLanguage]'));
			echo CHtml::activeTextField($data, 'targetLanguage', array('name' => $name.'[targetLanguage]', 'readonly' => 'readonly'));
			echo CHtml::error($data, 'targetLanguage', array('name' => $name.'[targetLanguage]')); 
			?>
		</td>
	</tr>
	<tr>
		<th class="right"><?php echo CHtml::activeLabelEx($data, 'message', array('name' => $name.'[message]')); ?></th>
		<td colspan="2" class="fill">
			<?php 
			
			echo CHtml::activeTextArea($data, 'message', array('name' => $name.'[message]', 'readonly' => 'readonly'));
			echo CHtml::error($data, 'message', array('name' => $name.'[message]')); 
			?>
		</td>
	</tr>
	<tr>
		<th class="right">
			<?php echo CHtml::activeLabelEx($data, 'translation', array('name' => $name.'[translation]')); ?> 
		</th>
		<td colspan="2" class="fill">
			<?php 
			echo CHtml::activeTextArea($data, 'translation', array('name' => $name.'[translation]'));
			echo CHtml::error($data, 'translation', array('name' => $name.'[translation]')); 
			?>
		</td>
	</tr>
	<?php if($autoTranslate): ?>
		<tr>
			<td colspan="3">
			<?php 
			$this->widget(
				'translate.widgets.translator.TranslatorWidget', 
				array(
					'id' => CHtml::getIdByName($name.'[message]'),
					'selectors' => array(
						'target' => '#'.CHtml::getIdByName($name.'[translation]'),
						'sourceLanguage' => '#'.CHtml::getIdByName($name.'[sourceLanguage]'),
						'targetLanguage' => '#'.CHtml::getIdByName($name.'[targetLanguage]')
					)
				)
			);
			?>
		</tr>
	<?php endif; ?>
</table>
