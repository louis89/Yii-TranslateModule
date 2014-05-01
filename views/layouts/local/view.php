<?php $this->breadcrumbs = $breadcrumbs; ?>
<h1>
	<?php echo TActiveRecord::formatName(get_class($model)).' '.TranslateModule::translate('Details'); ?>
</h1>
<div id="single-column">
	<div id="details" class="box-white">
		<?php $this->renderPartial($this->getViewFile('_details') === false ? $this->getLocalLayoutPathAlias().'._details' : '_details', array('model' => $model)); ?>
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
