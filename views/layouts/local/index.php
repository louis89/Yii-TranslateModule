<?php $this->breadcrumbs = $breadcrumbs; ?>
<h1>
	<?php echo TActiveRecord::formatName($modelName).' '.TranslateModule::translate('Index'); ?>
</h1>
<div id="single-column">
	<div id="records" class="box-white">
		<?php echo $grid; ?>
	</div>
	<?php if(!empty($tabs)): ?>
	<div id="relatedRecords" class="box-white">
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
	<?php endif; ?>
</div>
