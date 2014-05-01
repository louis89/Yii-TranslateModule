<?php

Yii::import('translate.components.ExplorerController');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class RouteController extends ExplorerController
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
	
	public function actionTranslate(array $Route = array(), $dryRun = true)
	{
		$dryRun = true;
		$translator = $this->getTranslateModule()->translator();
		
		$model = $this->getModel('search');
		$model->with('missingViews');
		$model->setAttributes($Route);
		$condition = $model->getSearchCriteria();
		
		$translationsCreated = 0;
		$translationErrors = 0;
		$viewSourceIds = array();
		$unreadableViewSourceIds = array();
		$languageIds = array();
	
		$transaction = $model->getDbConnection()->beginTransaction();
		try
		{
			$routes = $model->findAll($condition);

			if($dryRun)
			{
				foreach($routes as $route)
				{
					foreach($route->viewSources as $viewSource)
					{
						if(!isset($viewSourceIds[$viewSource->id]) && !isset($unreadableViewSourceIds[$viewSource->id]))
						{
							if($viewSource->getIsReadable())
							{
								$viewSourceIds[$viewSource->id] = $viewSource->id;
								foreach($viewSource->missingViews as $language)
								{
									$languageIds[$language->id] = $language->id;
									$translationsCreated++;
								}
							}
							else
							{
								$unreadableViewSourceIds[$viewSource->id] = $viewSource->id;
							}
						}
					}
				}

			}
			else
			{
				$viewRenderer = Yii::app()->getViewRenderer();
				foreach($routes as $route)
				{
					foreach($route->viewSources as $viewSource)
					{
						if(!isset($viewSourceIds[$viewSource->id]) && !isset($unreadableViewSourceIds[$viewSource->id]))
						{
							if($viewSource->getIsReadable())
							{
								$viewSourceIds[$viewSource->id] = $viewSource->id;
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
								Yii::log('The source view with ID "'.$viewSource->id.'" could not be translated because it is not readable.', CLogger::LEVEL_ERROR, $this->getTranslateModuleID());
								$unreadableViewSourceIds[$viewSource->id] = $viewSource->id;
							}
						}
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
					'This action will translate {routeCount} routes, containing {viewsTranslated} source views, into {languagesCount} languages. A total of {translationsCreated} view translations will be created. {unreadableViewsCount} source views cannot be translated because they are not readable. Are you sure that you would like to continue?', 
					array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{viewsTranslated}' => count($viewSourceIds), '{unreadableViewsCount}' => count($unreadableViewSourceIds), '{routeCount}' => count($routes))
				)
			);
		}
		else
		{
			$this->renderMessage(
				$translationErrors > 0 ? 'warning' : TController::SUCCESS,
				$this->getTranslateModule()->t(
					'{routeCount} routes translated, {translationsCreated} view translations have been created for {viewsTranslated} source views in {languagesCount} languages. {unreadableViewsCount} source views were unreadable. {errorCount} errors occurred (see system log).', 
					array('{translationsCreated}' => $translationsCreated, '{languagesCount}' => count($languageIds), '{viewsTranslated}' => count($viewSourceIds), '{unreadableViewsCount}' => count($unreadableViewSourceIds), '{routeCount}' => count($routes), '{errorCount}' => $translationErrors)
				)
			);
		}
	}

	/**
	 * Deletes a Route and all associated ViewSources and Views
	 *
	 * @param integer $id the ID of the Route to be deleted
	 */
	public function actionDelete(array $Route = array(), $dryRun = true)
	{
		$dryRun = true;
		$model = $this->getModel('search');
		$model->setAttributes($Route);
		$condition = $model->getSearchCriteria();

		$routesDeleted = 0;
		$viewsDeleted = 0;
		$sourceViewsDeleted = 0;
		
		$transaction = Route::model()->getDbConnection()->beginTransaction();
		try
		{
			$primaryKeys = array();
			foreach($model->filter($model->findAll($condition)) as $record)
			{
				$primaryKeys[] = $record['id'];
			}
			
			if($dryRun)
			{
				$routesDeleted = count($primaryKeys);
				$viewsDeleted = View::model()->route($primaryKeys)->applySafeScopes($scopes)->count();
				$sourceViewsDeleted = ViewSource::model()->route($primaryKeys)->applySafeScopes($scopes)->count();
			}
			else
			{
				$viewsDeleted = View::model()->route($primaryKeys)->applySafeScopes($scopes)->deleteAll();
				$sourceViewsDeleted = ViewSource::model()->route($primaryKeys)->applySafeScopes($scopes)->deleteAll();
				$routesDeleted = Route::model()->deleteByPk($primaryKeys);
				RouteView::model()->deleteAllByAttributes(array('route_id' => $primaryKeys));
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
			$dryRun ? 
				$this->getTranslateModule()->t(
					'{routes} routes, {sourceViews} source views, and {views} translated views have been deleted.', 
					array('{routes}' => $routesDeleted, '{sourceViews}' => $sourceViewsDeleted, '{views}' => $viewsDeleted)) :
				$this->getTranslateModule()->t(
					'This action will delete {routes} routes, {sourceViews} source views, and {views} translated views. Are you sure that you would like to continue?', 
					array('{routes}' => $routesDeleted, '{sourceViews}' => $sourceViewsDeleted, '{views}' => $viewsDeleted)),
			'index'
		);
	}

}