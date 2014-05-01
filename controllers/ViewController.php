<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class ViewController extends ExplorerController
{

	/*public function filters()
	{
		return array_merge(parent::filters(), array(
			'ajaxOnly + ajaxIndex, ajaxView',
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + index',
				'conditions' => 'ajaxIndex + ajax'
			),
			array(
				'ext.LDConditionChainFilter.LDForwardActionFilter.LDForwardActionFilter + view',
				'conditions' => 'ajaxView + ajax'
			)
		));
	}*/

	/**
	 * Deletes a View
	 *
	 * @param integer $id the ID of the View to be deleted
	 * @param integer $languageId The ID of the view's language to be deleted
	 */
	public function actionDelete(array $View = array(), $dryRun = true)
	{
		$dryRun = true;
		$model = $this->getModel('search');
		$model->setAttributes($View);
		$condition = $model->getSearchCriteria();

		$viewsDeleted = 0;
		
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			if(empty($View))
			{
				if($dryRun)
				{
					$viewsDeleted = View::model()->count();
				}
				else 
				{
					$viewsDeleted = View::model()->deleteAll();
				}
			}
			else
			{
				if($dryRun)
				{
					$viewsDeleted = $model->count($condition);
				}
				else
				{
					$primaryKeys = array();
					foreach($model->filter($model->findAll($condition)) as $record)
					{
						$primaryKeys[] = array('id' => $record['id'], 'language_id' => $record['language_id']);
					}
					$viewsDeleted = View::model()->deleteByPk($primaryKeys);
				}
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderMessage($e);
		}

		$this->renderMessage(
				TController::SUCCESS,
				$dryRun ? $this->getTranslateModule()->t('This action will delete {views} translated views. Are you sure that you would like to continue?', array('{views}' => $viewsDeleted)) : $this->getTranslateModule()->t('{views} translated views have been deleted.', array('{views}' => $viewsDeleted)),
				'index'
		);
	}
	
	public function actionGarbage()
	{
		$readableGarbageModel = new View('search');
		$readableGarbageModel->setAttributes($this->getModelParams());
		$readableGarbageModel->setIsReadable(true);
		
		$unreadableGarbageModel = new View('search');
		$unreadableGarbageModel->setAttributes($this->getModelParams());
		$unreadableGarbageModel->setIsReadable(false);

		$data = array();
		$data['relationPathParam'] = $this->relationPathParam;
		$data['model'] = $readableGarbageModel;
		$data['relatedGrids'] = array();
		$data['dataProvider'] = new TArrayDataProvider(array_merge($unreadableGarbageModel->filter($unreadableGarbageModel->findAll($unreadableGarbageModel->getSearchCriteria())), $readableGarbageModel->filter($readableGarbageModel->findAll($readableGarbageModel->getSearchCriteria(array('scopes' => 'isGarbage'))), 'isReadable', array(), false)));
		$data['deleteButton'] = true;
		$data['updateButton'] = false;
		$data['viewButton'] = false;

		return $this->renderPartial($this->getViewFile('_grid') === false ? $this->getLocalLayoutPathAlias().'._grid' : '_grid', $data);
	}

}