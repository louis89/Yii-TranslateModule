<?php

Yii::import('translate.widgets.actionDialog.actions.DialogAction');

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class DeleteAction extends DialogAction
{

	public $acceptedRequestTypes = 'delete';
	
	public $redirectTo = 'index';

	public function handleRequest($model)
	{
		parent::handleRequest($model);
		
		$condition = $model->getSearchCriteria();
		
		$recordsDeleted = 0;
		var_dump($model->deleteRelated());die;
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			if($this->getIsConfirmed())
			{
				$primaryKey = array_flip((array)$model->getTableSchema()->primaryKey);
				$primaryKeys = array();
				foreach($model->filter($model->findAll($condition)) as $record)
				{
					$primaryKeys[] = array_intersect_key($record, $primaryKey);
				}
				//$recordsDeleted = $model->deleteByPk($primaryKeys);
			}
			else
			{
				$recordsDeleted = $model->count($condition);
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderResponse($e);
		}
		
		if($this->getIsConfirmed())
		{
			$this->renderResponse(self::SUCCESS, $recordsDeleted.' records deleted.');
		}
		else
		{
			$this->renderResponse(self::CONFIRM, $recordsDeleted.' records will be deleted. Are you sure you would like to continue?');
		}
	}

}
?>