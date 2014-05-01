<?php $this->breadcrumbs = array(TranslateModule::translate('Garbage Collection')); ?>
<h1>
	<?php echo TranslateModule::translate('Garbage Collection'); ?>
</h1>
<div id="single-column">
	<div id="description">
	<?php echo TranslateModule::translate('Garbage records are any records that may be obsolete or unusable for some reason. Records are flagged as garbage if a related record necessary to maintain a foreign key constraint is missing. However, in the case of Views and their sources, if their associated files are not readable they will also be flagged as garbage even if all foreign key constraints are satisfied.'); ?>
	</div>
	<div id="records" class="box-white">
		<?php
		$this->widget(
				'zii.widgets.jui.CJuiTabs',
				array(
					'tabs' => $tabs,
					'headerTemplate' => '<li><a href="{url}" title="{title}">{title}</a></li>',
					'id' => 'relatedRecordGrids'
				)
		);
		?>
	</div>
</div>
