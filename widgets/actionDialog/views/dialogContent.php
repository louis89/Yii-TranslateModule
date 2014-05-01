<?php
$actionOptions = CMap::mergeArray(
	array(
		'dialog' => '#'.$id,
		'progressBarId' => $id.'-progress-bar',
		'contentId' => $id.'-content',
		'dialogOptions' => array(
			'defaultContents' => array(
				'success' => Yii::t($tCategory, 'Success'),
				'error' => Yii::t($tCategory, 'Error'),
				'notice' => Yii::t($tCategory, 'Notice'),
				'confirm' => Yii::t($tCategory, 'Confirm'),
				'loading' => Yii::t($tCategory, 'Loading...')
			),
			'default' => array(
				'buttons' => array(
					'close' => array('text' => Yii::t($tCategory, 'Close'), 'click' => 'js:function(){$(this).dialog("close");}')
				),
				'position' => array('center', 'center'),
			),
			'confirm' => array(
				'buttons' => array(
					'confirm' => array('text' => Yii::t($tCategory, 'Confirm')),
					'cancel' => array('text' => Yii::t($tCategory, 'Cancel'), 'click' => 'js:function(){$(this).dialog("close");}')
				),
				'position' => array('center', 'center'),
			)
		)
	),
	$actionOptions
);

$cs = Yii::app()->getClientScript();
// Set CSRF token if using CSRF validation
if(Yii::app()->getRequest()->enableCsrfValidation)
{
	// Set CSRF token
	$actionOptions['csrfToken'] = array(Yii::app()->getRequest()->csrfTokenName => Yii::app()->getRequest()->getCsrfToken());
}
	
echo '<div id="#'.$actionOptions['progressBarId'].'">';
$this->widget('zii.widgets.jui.CJuiProgressBar', $progressBarOptions);
echo '</div>';
echo '<div id="#'.$actionOptions['contentId'].'"></div>';

$cs->registerScriptFile($this->getAssetsUrl() . '/scripts/EActionDialog.js', CClientScript::POS_END);
$cs->registerScript(__CLASS__.$this->getId(), 'jQuery("'.$target.'").eActionDialog('.CJavaScript::encode($actionOptions).');');
$cs->registerCssFile($this->getAssetsUrl().'/styles/EActionDialog.css');
?>
