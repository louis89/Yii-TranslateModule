<?php

/**
 * This is the model class for table "{{translate_route}}".
 *
 * The followings are the available columns in table '{{translate_route}}':
 * @property integer $id
 * @property string $route
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class Route extends TActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Route the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return TranslateModule::viewSource()->routeTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'route' => array('partialMatch' => true, 'escape' => true),
				)
			),
		));
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('route', 'required', 'except' => 'search'),
			array('id', 'numerical', 'integerOnly' => true),
			array('route', 'length', 'max' => 255),
			array('id, route', 'unique', 'except' => 'search'),

			array('id, route', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = parent::relations();
		if(VIewSource::model()->getIsInstalled())
		{
			$relations['viewSources'] = array(self::MANY_MANY, 'ViewSource', RouteView::model()->tableName().'(route_id, view_id)');
			$relations['viewSourceCount'] = array(self::STAT, 'ViewSource', RouteView::model()->tableName().'(route_id, view_id)');
		}
		return $relations;
	}
	
	public function scopes()
	{
		return array(
			'isGarbage' => array(
				'with' => array(
					'viewSources' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					)
				),
				'condition' => $this->getDbConnection()->quoteColumnName('viewSources.id').' IS NULL'
			),
		);
	}
	
	public function getAttributeLabel($attribute)
	{
		return CModel::getAttributeLabel($attribute);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			// Attributes
			'id' => TranslateModule::translate('ID'),
			'route' => TranslateModule::translate('Route'),
			
			// Relations
			'viewSources' => TranslateModule::translate('Source Views'),
			'viewSourceCount' => TranslateModule::translate('Source View Count'),
		);
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($dataProviderConfig = array(), $mergeCriteria = array(), $operator = 'AND')
	{
		$dataProviderConfig['criteria'] = $this->getSearchCriteria($mergeCriteria, $this->getTableAlias(), $operator);
		
		return new CActiveDataProvider($this, $dataProviderConfig);
	}

	public function __toString()
	{
		return (string)$this->route;
	}

}