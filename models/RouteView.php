<?php

/**
 * This is the model class for table "{{translate_route_view}}".
 *
 * The followings are the available columns in table '{{translate_route_view}}':
 * @property integer $route_id
 * @property integer $view_id
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class RouteView extends TActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return RouteView the static model class
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
		return TranslateModule::viewSource()->routeViewTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'route_id' => array('partialMatch' => false, 'escape' => true),
					'view_id' => array('partialMatch' => false, 'escape' => true),
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
			array('route_id, view_id', 'required', 'except' => 'search'),
			array('route_id, view_id', 'numerical', 'integerOnly' => true),
			array('route_id', 'exist', 'attributeName' => 'id', 'className' => 'Route', 'except' => 'search'),
			array('view_id', 'exist', 'attributeName' => 'id', 'className' => 'ViewSource', 'except' => 'search'),

			array('route_id, view_id', 'safe', 'on' => 'search')
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = parent::relations();
		if(Route::model()->getIsInstalled())
		{
			$relations['route'] = array(self::BELONGS_TO, 'Route', 'route_id');
		}
		if(ViewSource::model()->getIsInstalled())
		{
			$relations['viewSource'] = array(self::BELONGS_TO, 'ViewSource', 'view_id');
		}
		return $relations;
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
			'route_id' => TranslateModule::translate('Route ID'),
			'view_id' => TranslateModule::translate('View ID'),
			
			// Relations
			'route' => TranslateModule::translate('Route'),
			'viewSource' => TranslateModule::translate('View Source'),
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
	
}