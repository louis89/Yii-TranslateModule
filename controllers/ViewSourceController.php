<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class ViewSourceController extends ExplorerController
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
	
	public function actionTranslate(array $ViewSource = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();

		$model = $this->getModel('search');
		$model->with('missingViews');
		$model->setAttributes($ViewSource);
		$condition = $model->getSearchCriteria();

		$translationsCreated = 0;
		$translationErrors = 0;
		
		$languageIds = array();
		$unreadableViewSources = array();
	
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			$viewSources = $model->findAll($condition);
			if($dryRun)
			{
				foreach($viewSources as $key => $viewSource)
				{
					if($viewSource->getIsReadable())
					{
						foreach($viewSource->missingViews as $language)
						{
							$languageIds[$language->id] = $language->id;
							$translationsCreated++;
						}
					}
					else
					{
						unset($viewSources[$key]);
						$unreadableViewSources[] = $viewSource;
					}
				}
			}
			else
			{
				$viewRenderer = Yii::app()->getViewRenderer();
				foreach($viewSources as $key => $viewSource)
				{
					if($viewSource->getIsReadable())
					{
						foreach($viewSource->missingViews as $language)
						{
							$languageIds[$language->id] = $language->id;
							try
							{
								$viewRenderer->generateViewFile($viewSource->path, $viewRenderer->getViewFile($viewSource->path, $language->code), null, $translator->messageSource, $language->code, false);
							}
							catch(CException $ce)
							{
								Yii::log($ce->getMessage(), CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
								$translationErrors++;
								continue;
							}
							$translationsCreated++;
						}
					}
					else
					{
						unset($viewSources[$key]);
						$unreadableViewSources[] = $viewSource;
						Yii::log('The source view with ID "'.$viewSource->id.'" could not be translated because its path is not readable.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
					}
				}
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			return $this->renderMessage($e);
		}
	
		if($dryRun)
		{
			$this->renderMessage(
					TController::SUCCESS,
					$this->getTranslateModule()->t(
						'This action will translate {viewsTranslated} source views into {languagesCount} languages. {translationsCreated} view translations in total will be created. {unreadableViews} views cannot be translated because they are not readable. Are you sure that you would like to continue?', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{viewsTranslated}' => count($viewSources), '{unreadableViews}' => count($unreadableViewSources))
				)
			);
		}
		else
		{
			$this->renderMessage(
					$translationErrors > 0 ? TController::NOTICE : TController::SUCCESS,
					$this->getTranslateModule()->t(
						'{translationsCreated} view translations have been created for {viewsTranslated} source views in {languagesCount} languages. {unreadableViews} views could not be translated because they are not readable. {translationErrors} errors occurred (see system log).', 
						array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{viewsTranslated}' => count($viewSources), '{translationErrors}' => $translationErrors, '{unreadableViews}' => count($unreadableViewSources))
				)
			);
		}
	}

	/**
	 * Deletes a ViewSource and all associated Views
	 *
	 * @param integer $id the ID of the ViewSource to be deleted
	 */
	public function actionDelete(array $ViewSource = array(), $dryRun = true)
	{
		$dryRun = true;

		$model = $this->getModel('search');
		$model->setAttributes($ViewSource);
		$condition = $model->getSearchCriteria();

		$viewsDeleted = 0;
		$sourceViewsDeleted = 0;
		
		$transaction = ViewSource::model()->getDbConnection()->beginTransaction();
		try
		{
			if(empty($ViewSource))
			{
				if($dryRun)
				{
					$viewsDeleted = View::model()->count();
					$sourceViewsDeleted = ViewSource::model()->count();
				}
				else
				{
					$viewsDeleted = View::model()->deleteAll();
					$sourceViewsDeleted = ViewSource::model()->deleteAll();
					ViewMessage::model()->deleteAll();
				}
			}
			else
			{
				$primaryKeys = array();
				foreach($model->filter($model->findAll($condition)) as $record)
				{
					$primaryKeys[] = $record['id'];
				}
				if($dryRun)
				{
					$viewsDeleted = View::model()->countByAttributes(array('id' => $primaryKeys));
					$sourceViewsDeleted = count($primaryKeys);
				}
				else
				{
					$viewsDeleted = View::model()->deleteAllByAttributes(array('id' => $primaryKeys));
					$sourceViewsDeleted = ViewSource::model()->deleteByPk($primaryKeys);
					ViewMessage::model()->deleteAllByAttributes(array('view_id' => $primaryKeys));
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
				TConrtoller::SUCCESS,
				$dryRun ? $this->getTranslateModule()->t('{sourceViews} source views and {views} translated views have been deleted.', array('{sourceViews}' => $sourceViewsDeleted, '{views}' => $viewsDeleted)) : $this->getTranslateModule()->t('This action will delete {sourceViews} source views and {views} translated views. Are you sure that you would like to continue?', array('{sourceViews}' => $sourceViewsDeleted, '{views}' => $viewsDeleted))
		);
	}
	
	/**
	 * Flushes the cache of the translator's view source component.
	 */
	public function actionFlushCache()
	{
		$this->getTranslateModule()->viewSource()->flushCache();
	}
	
	public function actionGarbage()
	{
		$readableGarbageModel = new ViewSource('search');
		$readableGarbageModel->setAttributes($this->getModelParams());
		$readableGarbageModel->setIsReadable(true);
		
		$unreadableGarbageModel = new ViewSource('search');
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