<?php

Yii::import('zii.widgets.CDetailView');

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class TComponentDetailView extends CDetailView
{

	public $deleteButton = true;
	
	/**
	 * (non-PHPdoc)
	 * @see CDetailView::init()
	 */
	public function init()
	{
		if($this->attributes === null && $this->data instanceof TActiveRecord)
		{
			$this->initAttributes();
		}
		
		parent::init();
		
		$id = $this->getId();
		Yii::app()->getClientScript()->registerCss(__CLASS__.'#'.$id, 'table#'.$id.'-details{min-width:100%;width:100%;max-width:100%;}');
	}
	
	/**
	 * Initializes the attributes displayed by this detail view.
	 */
	protected function initAttributes()
	{
		// Determine the names of the attributes that have already been defined to avoid defining the again in the next step
		$definedAttributes = array();
		if(isset($this->attributes))
		{
			foreach($this->attributes as $attr)
			{
				if(is_string($attr))
				{
					$i = strpos($attr, ':');
					$definedAttributes[$i === false ? $attr : substr($attr, 0, $i)] = true;
				}
				else if(isset($attr['name']))
				{
					$definedAttributes[$attr['name']] = true;
				}
					
			}
		}
		
		// Configure renderable attribute attributes if it has not already been defined manually.
		foreach($this->data->getAllAttributeNames() as $name)
		{
			if(!isset($definedAttributes[$name]))
			{
				$attr = array(
					'name' => $name,
					'type' => $this->data->getAttributeType($name),
				);

				if($attr['type'] === 'text')
				{
					$attr['template'] = "<tr class=\"{class}\"><th>{label}</th><td style=\"word-wrap:break-word;word-break:break-all;\">{value}</td></tr>\n";
				}
				$this->attributes[] = $attr;
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see CWidget::getId()
	 */
	public function getId($autoGenerate = true)
	{
		$id = parent::getId(false);
		if($id !== null)
		{
			return $id;
		}
		else if($autoGenerate)
		{
			$id = __CLASS__.'-'.$this->getModelName();
			$this->setId($id);
		}
		return $id;
	}
	
	/**
	 * 
	 * @return string The name of this detail view's model
	 */
	public function getModelName()
	{
		return get_class($this->data);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CDetailView::run()
	 */
	public function run()
	{
		parent::run();
		if($this->deleteButton)
		{
			$this->renderDeleteButton();
		}
	}
	
	/**
	 * Renders a delete button.
	 */
	protected function renderDeleteButton()
	{
		$modelName = $this->getModelName();
		$pkParams = array($modelName => is_array($this->data->getPrimaryKey()) ? $this->data->getPrimaryKey() : array($this->data->getTableSchema()->primaryKey => $this->data->getPrimaryKey()));
		
		echo CHtml::button(
				TranslateModule::translate('Delete'),
				array(
					'onClick' => 'if(confirm("'.TranslateModule::translate('You are about to delete this record. All related records will also be deleted. Are you sure that you would like to continue?').'")){'.
						'document.location.href = "'.$this->getController()->createUrl($modelName.'/delete', $pkParams).'";'.
					'}'
				)
		);
	}
	
}
