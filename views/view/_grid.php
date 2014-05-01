<?php

$this->renderPartial(
		$this->getLocalLayoutPathAlias().'._grid',
		array(
			'dataProvider' => $dataProvider,
			'relationPathParam' => $relationPathParam,
			'model' => $model,
			'viewButton' => true,
			'updateButton' => false,
			'deleteButton' => true,
		)
);
?>