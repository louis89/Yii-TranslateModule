<?php
if(!isset($gridConfig))
{
	$relationPath = $model->getRelationPath();
	$gridConfig = array(
		'id' => 'TComponentGridView-'.$relationPath->getName(),
		'model' => $model,
		'requestParams' => array($relationPathParam => $relationPath->toArray(true)),
		'relatedGrids' => array_map(create_function('$path', 'return "TComponentGridView-".(string)$path;'), $model->getRelatedPathNames()),
		'dataProvider' => $dataProvider,
		'deleteButton' => array('click' => 'function(){return false;}')
	);

	if(isset($viewButton))
	{
		$gridConfig['viewButton'] = $viewButton;
	}
	if(isset($updateButton))
	{
		$gridConfig['updateButton'] = $updateButton;
	}
}

$grid = $this->widget('translate.widgets.componentGridView.TComponentGridView', $gridConfig);

$this->widget(
		'translate.widgets.actionDialog.EActionDialog',
		array(
			'target' => $grid->tagName.'#'.$grid->getId().'.grid-view .delete',
			'actionOptions' => array('requestType' => 'delete')
		)
);
?>