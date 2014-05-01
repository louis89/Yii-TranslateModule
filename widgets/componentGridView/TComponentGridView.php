<?php

Yii::import('zii.widgets.grid.CGridView');

/**
 * 
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class TComponentGridView extends CGridView
{
	
	public $viewButton;
	
	public $updateButton;
	
	public $deleteButton;
	
	public $requestParams = array();
	
	public $model;
	
	public $modelName;
	
	public $keyAttributes;
	
	public $relatedGrids = array();
	
	public $showButtonsOnEmpty = true;
	
	private $_updateHandler;
	
	private $_deleteHandler;
	
	/**
	 * (non-PHPdoc)
	 * @see CGridView::init()
	 */
	public function init()
	{
		$this->initModel();
		
		if(!$this->model instanceof CModel)
		{
			throw new CException(TranslateModule::translate('The "model" property must be set to an instance of "CModel".'));
		}
		
		$this->initFilter();
		
		parent::init();
		
		// If the page was requested with specific model parameters then set the filters to include a drop down of the parameters or elminate the filter if a single parameter value is used.
		if(!$this->getIsAjaxUpdate())
		{
			foreach($this->columns as $col)
			{
				if($col instanceof CDataColumn)
				{
					if(($col->filter === null || $col->filter != false) && isset($this->filter->{$col->name}))
					{
						$col->filter = false;
					}
				}
			}
		}

		$this->initAjaxUrl();
		
		$id = $this->getId();
		
		// Add CSS for setting a maximum table width. This is necessary to split column data of long strings with no spaces.
		Yii::app()->getClientScript()->registerCss(__CLASS__.'#'.$id, 'div#'.$id.' table.items{min-width:100%;width:100%;max-width:100%;}');
	}
	
	protected function initModel()
	{
		if($this->model === null)
		{
			if($this->modelName !== null)
			{
				$reflection = new ReflectionClass($this->modelName);
				$this->model = $reflection->isSubclassOf('CActiveRecord') ? $reflection->getMethod('model')->invoke(null) : $reflection->newInstance();
			}
			elseif($this->dataProvider instanceof CActiveDataProvider)
			{
				$this->model = $this->dataProvider->model;
			}
			elseif($this->filter !== null)
			{
				$this->model = $this->filter;
			}
		}
		
		if($this->modelName === null)
		{
			$this->modelName = get_class($this->model);
		}
		
		if($this->keyAttributes === null)
		{
			if($this->model instanceof CActiveRecord)
			{
				$this->keyAttributes = (array)$this->model->getTableSchema()->primaryKey;
			}
			elseif($this->dataProvider instanceof IDataProvider)
			{
				$this->keyAttributes = (array)$this->dataProvider->getKeys();
			}
		}
	}
	
	protected function initFilter()
	{
		if($this->filter === null)
		{
			$reflection = new ReflectionClass($this->modelName);
			$this->filter = $reflection->isSubclassOf('CActiveRecord') ? $reflection->newInstance('search') : $reflection->newInstance();
			$this->filter->attachBehavior('ERememberFiltersBehavior',
					array(
						'class' => 'ext.ERememberFiltersBehavior.ERememberFiltersBehavior',
						'rememberId' => $this->getId()
					));
		}
	}
	
	protected function initAjaxUrl()
	{
		if($this->ajaxUrl === null)
		{
			$this->ajaxUrl = $this->getController()->createUrl($this->getController()->getAction()->getId(), $_GET + $this->requestParams);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CGridView::initColumns()
	 */
	protected function initColumns()
	{
		// Determine the names of the columns that have already been defined to avoid defining the again in the next step
		$definedColumns = array();
		foreach($this->columns as $col)
		{
			if(is_string($col))
			{
				$i = strpos($col, ':');
				$definedColumns[$i === false ? $col : substr($col, 0, $i)] = true;
			}
			else if(isset($col['name']))
			{
				$definedColumns[$col['name']] = true;
			}
		}

		// Configure attribute columns
		foreach($this->model->attributeNames() as $name)
		{
			if(!isset($definedColumns[$name]))
			{
				$col = array(
					'name' => $name,
					'type' => $this->model->getAttributeType($name),
				);
				$col['filter'] = $this->createDataColumnFilter($name, $col['type']);
				if($col['type'] === 'text')
				{
					$col['htmlOptions'] = array('style' => 'word-wrap:break-word;word-break:break-all;');
				}
				$this->columns[] = $col;
			}
		}

		// Configure virtual attribute columns
		foreach($this->model->virtualAttributeNames() as $name)
		{
			if(!isset($definedColumns[$name]))
			{
				$col = array(
					'name' => $name,
					'type' => $this->model->getAttributeType($name),
					'sortable' => $this->dataProvider instanceof CArrayDataProvider
				);
				$col['filter'] = $this->model->canSetProperty($name) ? $this->createDataColumnFilter($name, $col['type']) : '';

				if($col['type'] === 'text')
				{
					$col['htmlOptions'] = array('style' => 'word-wrap:break-word;word-break:break-all;');
				}
				$this->columns[] = $col;
			}
		}

		$this->initButtons();
		
		parent::initColumns();
	}
	
	protected function createDataColumnFilter($name, $type)
	{
		if($this->filter !== null && $name !== null)
		{
			switch($type)
			{
				case 'number':
					return CHtml::activeNumberField($this->filter, $name, array('id' => false));
				case 'time':
					return CHtml::activeTimeField($this->filter, $name, array('id' => false));
				case 'date':
					return CHtml::activeDateField($this->filter, $name, array('id' => false));
				case 'email':
					return CHtml::activeEmailField($this->filter, $name, array('id' => false));
				case 'url':
					return CHtml::activeUrlField($this->filter, $name, array('id' => false));
				case 'boolean':
					return $this->getFormatter()->booleanFormat;
				case 'text':
				case 'ntext':
				case 'html':
					return CHtml::activeTextField($this->filter, $name, array('id' => false));
			}
		}
		return false;
	}
	
	protected function createDataColumn($text)
	{
		$column = parent::createDataColumn($text);
		$column->filter = $this->createDataColumnFilter($column->name, $column->type);
		return $column;
	}
	
	/**
	 * Create the default view, udpate, delete buttons for each one enabled in the widget's configuration
	 * 
	 * @TODO Clean up this method, maybe change the way button configuration entirely. This code is ugly.
	 */
	protected function initButtons()
	{
		$template = '';
		$buttons = array();
		$rowParamsExpression = $this->generateRowParamsExpression();

		if($this->viewButton)
		{
			// Setup View/Details button
			$template .= '{view}';
			$buttons['view'] = array_merge(
					array(
						'label' => TranslateModule::translate('View Details'),
						'url' => 'Yii::app()->getController()->createUrl(\'view\','.$rowParamsExpression.');',
					),
					(array)$this->viewButton
			);
		}

		if($this->updateButton)
		{
			// Setup Update/Translate button
			$template .= '{update}';
			$buttons['update'] = array_merge(
					array(
						'label' => TranslateModule::translate('Translate'),
						'url' => 'Yii::app()->getController()->createUrl(\'translate\','.$rowParamsExpression.');',
					),
					(array)$this->updateButton
			);
		}

		if($this->deleteButton)
		{
			// Setup Delete button
			$template .= '{delete}';
			$buttons['delete'] = array_merge(
					array(
						'label' => TranslateModule::translate('Delete'),
						'url' => 'Yii::app()->getController()->createUrl(\'EActionDialog.delete\','.$rowParamsExpression.');',
					),
					(array)$this->deleteButton
			);
		}
		
		if(!empty($buttons))
		{
			$this->columns[] = array(
				'class' => 'CButtonColumn',
				'template' => $template,
				'buttons' => $buttons
			);
		}
	}
	
	/**
	 * Generates a PHP expression for CGridView rows URL parameters.
	 *
	 * @return string PHP expression representing the URL params to be eval'd for a CGridView row.
	 */
	public function generateRowParamsExpression()
	{
		$urlExpression = 'array(\''.$this->modelName.'\'=>array(';
		foreach($this->keyAttributes as $key)
		{
			$urlExpression .= '\''.$key.'\'=>$data[\''.$key.'\'],';
		}
		$urlExpression .= '))';
	
		if(!empty($this->requestParams))
		{
			$urlExpression .= '+unserialize(\''.serialize($this->requestParams).'\')';
		}
		return $urlExpression;
	}
	
	/**
	 * Return True if the current request is an ajax request to update this grid specifically.
	 *
	 * @return boolean True if this is an ajax update request. False otherwise.
	 */
	public function getIsAjaxUpdate()
	{
		return isset($_GET[$this->ajaxVar]) && $_GET[$this->ajaxVar] === $this->getId();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CBaseListView::run()
	 */
	public function run()
	{
		if($this->dataProvider->getItemCount() > 0 || $this->showButtonsOnEmpty)
		{
			$this->renderButtons();
		}
		parent::run();
	}
	
	/**
	 * Renders the selection action buttons that appear above the grid view.
	 * These buttons perform operations on the selected rows of the grid view.
	 */
	public function renderButtons()
	{
		echo '<div id="'.$this->getId().'-selectionActions">'.TranslateModule::translate('Selection Actions').':<br />';
		
		/*if(isset($this->_updateHandler))
		{
			$this->_updateHandler->run();
		}
		if(isset($this->_deleteHandler))
		{
			$this->_deleteHandler->run();
		}*/
		
		echo '</div>';
	}
	
}
