<?php

Yii::import(TranslateModule::ID.'.components.TController');

/**
 * Base class for exploring the translation system's database structure.
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class ExplorerController extends TController
{
	
	/**
	 * @var string The request parameter name containing the relation path info.
	 */
	public $relationPathParam = 'path';
	
	private $_modelParams;
	
	public function actions()
	{
		return  array(
			'EActionDialog.' => array(
				'class' => 'translate.widgets.actionDialog.EActionDialog',
				'create' => array(),
				'read' => array(),
				'udpate' => array(),
				'delete' => array(),
			),
		);
	}
	
	/**
	 * Returns the current active record model's name based on the name of this controller.
	 * 
	 * @return string The current model's name
	 */
	public function getModelName()
	{
		return ucfirst($this->getId());
	}
	
	public function getModel($scenario = null)
	{
		$modelName = $this->getModelName();
		if($scenario === null)
		{
			$model = call_user_func(array($modelName, 'model'));
		}
		else
		{
			$model = new $modelName($scenario);
		}
		if(isset($_GET[$this->relationPathParam]))
		{
			$_GET[$this->relationPathParam] = (array)$_GET[$this->relationPathParam];
		}
		else
		{
			$_GET[$this->relationPathParam] = array();
		}
		$model->setRelationPath($_GET[$this->relationPathParam], $this->getModelParams());
		//$model->applyPath();
		return $model;
	}
	
	/**
	 * Retusn the current model's action parameters if they have been specified.
	 * Otherwise an empty array is returned.
	 * 
	 * @return array The current model's parameters
	 */
	public function getModelParams()
	{
		if($this->_modelParams === null)
		{
			$modelName = $this->getModelName();
			if(!isset($_GET[$modelName]) || !is_array($_GET[$modelName]))
			{
				if(empty($_GET[$modelName]))
				{
					$_GET[$modelName] = array();
				}
				else
				{
					return $this->invalidActionParams($this->getAction());
				}
			}
			$this->_modelParams = $_GET[$modelName];
		}
		return $this->_modelParams;
	}
	
	/**
	 * Pushes a relation and its parameters onto the relation path.
	 * Takes 1 or 2 parameters.
	 * The first can be an array with the new relation as the key and the value its parameters or it can be a string meaning no paremeters.
	 * If the second argument is set the first should be the relation's name string. The second is the parameters for the relation.
	 *
	 * @return integer The number of relations in the path after pushing the new relation onto the path
	 */
	public function getRelationPath()
	{
		return $_GET[$this->relationPathParam];
	}
	
	/**
	 * Returns the relation at the beginning of the relation path.
	 *
	 * @return string|array A string of the relation's name if the relation is not parameterized. Otherwise an array in the format relation => parameters.
	 */
	public function setRelationPath($path)
	{
		$_GET[$this->relationPathParam] = $path;
	}
	
	/**
	 * Generates a configuration array for CJuiTabs widget. 
	 * Each tab will be an index of an unexplored HAS_MANY or MANY_MANY relation of the current model.
	 * 
	 * @return array CJuiTabs configuration array
	 */
	public function generateRelatedTabs()
	{
		$model = $this->getModel();
		$params = $this->getModelParams();
		$modelName = $this->getModelName();
		if(isset($_GET[$modelName]))
		{
			unset($_GET[$modelName]);
			$setModelParams = true;
		}
		else
		{
			$setModelParams = false;
		}
		$relationPath = $model->getRelationPath();
		$relations = $model->relations();
		$tabs = array();
		foreach($relationPath->getUnexploredSegments() as $relationName => $inverseRelation)
		{
			if(in_array($relations[$relationName][0], array(CActiveRecord::BELONGS_TO, CActiveRecord::HAS_ONE)))
			{
				continue;
			}
			$relationPath->push($relationName);
			$this->setRelationPath($relationPath->toArray(true));
			$tabs[TActiveRecord::formatName($relationName)] = $this->forwardAndReturn($relationPath->getLast()->getModelName().'/grid');
			$relationPath->pop();
		}
		$this->setRelationPath($relationPath->toArray(true));
		if($setModelParams)
		{
			$_GET[$modelName] = $params;
		}
		return $tabs;
	}
	
	/**
	 * Generates a configuration array for CBreadcrumbs widget based on the current relation path.
	 * 
	 * @return array CBreadcrumbs configuration array
	 */
	public function generateBreadcrumbs()
	{
		$breadcrumbs = array(TActiveRecord::formatName($this->getModelName()));
		
		$relationPath = $this->getModel()->getRelationPath();
		while(!$relationPath->getIsEmpty())
		{
			$node = $relationPath->pop();
			$primaryKey = (array)$node->getModel()->getTableSchema()->primaryKey; // @TODO if primaryKey is not defined in table definition check active record primaryKey()
			if(empty($primaryKey))
			{
				$action = 'index';
			}
			else
			{
				$action = 'view';
				foreach($primaryKey as $pk)
				{
					$attr = $node->getModel()->getAttribute($pk);
					if(!isset($attr) || (!is_numeric($attr) && empty($attr)) || (is_array($attr) && count($attr) > 1))
					{
						$action = 'index';
						break;
					}
				}
			}
			$relation = $node->getSource()->getRelation();
			$breadcrumbs[TActiveRecord::formatName($relation->name)] = $this->createUrl($relation->className.'/'.$action, $relationPath->getIsEmpty() ? array($this->relationPathParam => $relationPath->toArray(true)) : array());
		}
		
		return array_reverse($breadcrumbs, true);
	}
	
	/**
	 * Finds the current model by primary key, if the primary key for the current model is set.
	 * If the primary key is not set invalidActionParams will be called.
	 * 
	 * @param string|array $conditon Condition string or initial configuration for CDbCriteria
	 * @param array $params The parameters to be bound to the condition
	 * @return CActiveRecord The current model found via its primary key. 
	 */
	public function findModelByPk($condition = '', $params = array())
	{
		$model = call_user_func(array($this->getModelName(), 'model'));
		$requestParams = $this->getModelParams();
		$primaryKey = $model->getTableSchema()->primaryKey;
	
		$primaryKey = is_array($primaryKey) ? array_flip($primaryKey) : array($primaryKey => null);
		foreach($primaryKey as $col => &$val)
		{
			if(!isset($requestParams[$col]))
			{
				return $this->invalidActionParams($this->getAction());
			}
			$val = $requestParams[$col];
		}
	
		return $model->findByPk(count($primaryKey) === 1 ? reset($primaryKey) : $primaryKey, $condition, $params);
	}
	
	/**
	 * Renders the index for the current model
	 */
	public function actionIndex()
	{
		$this->render(
				$this->getViewFile('index') === false ? $this->getLocalLayoutPathAlias().'.index' : 'index', 
				array(
					'breadcrumbs' => $this->generateBreadcrumbs(),
					'modelName' => $this->getModelName(),
					'tabs' => $this->generateRelatedTabs(),
					'grid' => $this->internalActionGrid(true),
				)
		);
	}
	
	/**
	 * Renders a view for a particular instance of the currrent model.
	 * 
	 * @throws CHttpException Throws exception if a particular instance of the current model cannot be determined.
	 */
	public function actionView()
	{
		$model = $this->findModelByPk();

		if($model === null)
		{
			throw new CHttpException(404);
		}
		
		$this->internalActionView($model);
	}
	
	public function internalActionView($model, $return = false)
	{
		$this->render(
				$this->getViewFile('view') === false ? $this->getLocalLayoutPathAlias().'.view' : 'view', 
				array(
					'breadcrumbs' => $this->generateBreadcrumbs(),
					'model' => $model,
					'tabs' => $this->generateRelatedTabs(),
				),
				$return
		);
	}
	
	/**
	 * Renders a CGridView for the current model.
	 */
	public function actionGrid()
	{
		$this->internalActionGrid();
	}
	
	public function internalActionGrid($return = false, $criteria = array())
	{
		$modelName = $this->getModelName();
		$model = $this->getModel('search');
		//$model->setAttributes($this->getModelParams());
		//$model->applyPath();
		//$model->getDbCriteria()->mergeWith($criteria);

		$data = array();
		$data['relationPathParam'] = $this->relationPathParam;
		$data['model'] = $model;
		$data['relatedGrids'] = array();
		$data['dataProvider'] = $model->search();
		$data['deleteButton'] = true;
		$data['updateButton'] = true;
		$data['viewButton'] = true;

		return $this->renderPartial($this->getViewFile('_grid') === false ? $this->getLocalLayoutPathAlias().'._grid' : '_grid', $data, $return);
	}
	
	public function actionGarbage()
	{
		$this->internalActionGrid(false, array('scopes' => 'isGarbage'));
	}

}
