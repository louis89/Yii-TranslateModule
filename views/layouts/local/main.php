<?php
Yii::app()->getClientScript()->registerCssFile($this->getStylesUrl('translateMain.css', '')); 
if(Yii::app()->getUser()->hasFlash(TranslateModule::ID.'-success')): 
?>
<div class="flash-success">
	<?php echo Yii::app()->getUser()->getFlash(TranslateModule::ID.'-success'); ?>
</div>
<?php endif; ?>
<?php if(Yii::app()->getUser()->hasFlash(TranslateModule::ID.'-notice')): ?>
<div class="flash-notice">
	<?php echo Yii::app()->getUser()->getFlash(TranslateModule::ID.'-notice'); ?>
</div>
<?php endif; ?>
<?php if(Yii::app()->getUser()->hasFlash(TranslateModule::ID.'-error')): ?>
<div class="flash-error">
	<?php echo Yii::app()->getUser()->getFlash(TranslateModule::ID.'-error'); ?>
</div>
<?php endif; ?>
<?php echo $content; ?>