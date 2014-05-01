<?php
Yii::app()->getClientScript()->registerCssFile($this->getStylesUrl('index.css'));

$translateModuleWidget = $this->createWidget('translate.widgets.configurationStatus.ConfigurationStatusWidget', array('component' => $translateModule, 'actionPrefix' => 'configurationStatus.'));
$translateModuleModel = $translateModuleWidget->getModel();
$messageSource = $translateModuleModel->hasErrors('messageSourceID') ? null : new InstallableModel($translateModuleModel->getAttribute('messageSourceID'));
$viewSource = $translateModuleModel->hasErrors('viewSourceID') ? null : new InstallableModel($translateModuleModel->getAttribute('viewSourceID'));
?>
<h1>
	<?php echo TranslateModule::translate('Translation System Management'); ?>
</h1>
<div class="column-fill">
	<h2>Database Status</h2>
	<div class="box-white">
	<?php $this->renderPartial('_database', array('modelPath' => Yii::getPathOfAlias('translate.models'))); ?>
	</div>
	<h2>System Status</h2>
	<div class="box-white">
	<?php $translateModuleWidget->run();?>
	</div>
</div>
