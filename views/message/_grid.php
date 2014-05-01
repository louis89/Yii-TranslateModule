<?php
$relationPath = $model->getRelationPath();
$relationPathName = $relationPath->getName();
$messageUpdateFormId = 'message-update-form_'.$relationPathName;
$relatedGrids = array_map(create_function('$path', 'return "TComponentGridView-".(string)$path;'), $model->getRelatedPathNames());
$grid = $this->createWidget(
		'translate.widgets.componentGridView.TComponentGridView',
		array(
			'id' => 'TComponentGridView-'.$relationPathName,
			'model' => $model,
			'requestParams' => array($relationPathParam => $relationPath->toArray(true)),
			'relatedGrids' => $relatedGrids,
			'viewButton' => true,
			'deleteButton' => array('click' => 'function(){return false;}'),
			'updateButton' => array(
				'click' => 'function(){'.
					'$("#'.$messageUpdateFormId.'").tMessageForm("open", $(this).attr("href"));'.
						'return false;'.
					'}'
			),
			'dataProvider' => $dataProvider,
		)
);

$id = $grid->getId();
$grid->run();
array_unshift($relatedGrids, $id);
$this->renderPartial(
	'_form', 
	array(
		'Message' => new Message, 
		'MessageSource' => new MessageSource, 
		'id' => $messageUpdateFormId, 
		'clientOptions' => array(
			'submitSuccess' => 'js:function($dialog, $form, data){$("#'.implode('").yiiGridView("update");$("#', $relatedGrids).'").yiiGridView("update");return true;}'
		)
	)
);

?>
