<?php

/**
 * This is the model class for table "{{translate_view_source}}".
 *
 * The followings are the available columns in table '{{translate_view_source}}':
 * @property integer $id
 * @property string $path
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 */
class ViewSource extends TActiveRecord
{
	
	private $_isReadable;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ViewSource the static model class
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
		return TranslateModule::viewSource()->viewSourceTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'path' => array('partialMatch' => true, 'escape' => true),
				)
			),
		));
	}

	public function getRelativePath()
	{
		if(substr($this->getAttribute('path'), 0, strlen(Yii::app()->getBasePath())) == Yii::app()->getBasePath())
		{
			return substr($this->getAttribute('path'), strlen(Yii::app()->getBasePath()));
		}
		return $this->getAttribute('path');
	}

	public function setRelativePath($path)
	{
		return $this->setAttribute('path', Yii::app()->getBasePath().DIRECTORY_SEPARATOR.$path);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('path', 'filter', 'filter' => 'realpath'),
			array('path', 'required', 'except' => 'search'),
			array('id', 'numerical', 'integerOnly' => true),
			array('path', 'length', 'max' => 255),
			array('id, path', 'unique', 'except' => 'search'),

			array('id, path, isReadable', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = parent::relations();
		if(MessageSource::model()->getIsInstalled())
		{
			$relations['messageSources'] = array(self::MANY_MANY, 'MessageSource', ViewMessage::model()->tableName().'(view_id, message_id)');
			$relations['messageSourceCount'] = array(self::STAT, 'MessageSource', ViewMessage::model()->tableName().'(view_id, message_id)');
		}
		if(Route::model()->getIsInstalled())
		{
			$relations['routes'] = array(self::MANY_MANY, 'Route', RouteView::model()->tableName().'(view_id, route_id)');
			$relations['routeCount'] = array(self::STAT, 'Route', RouteView::model()->tableName().'(view_id, route_id)');
		}
		if(View::model()->getIsInstalled())
		{
			$relations['views'] = array(self::HAS_MANY, 'View', 'id');
			$relations['viewCount'] = array(self::STAT, 'View', 'id');
			if(Language::model()->getIsInstalled())
			{
				$db = $this->getDbConnection();
				$relations['missingViews'] = array(
					self::HAS_MANY,
					'Language',
					Language::model(),
					'joinType' => 'INNER JOIN',
					'with' => array(
						'views' => array(
							'joinType' => 'LEFT OUTER JOIN',
							'on' => $db->quoteColumnName($this->getTableAlias().'.id').'='.$db->quoteColumnName('views.id'),
							'together' => true,
							'select' => false,
						)
					),
					'on' => '1=1',
					'together' => true,
					'condition' => $db->quoteColumnName('views.id').' IS NULL',
				);
			}
		}
		return $relations;
	}
	
	public function scopes()
	{
		$db = $this->getDbConnection();
		return array(
			'isGarbage' => array(
				'with' => array(
					'routes' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					),
				),
				'condition' => $db->quoteColumnName('routes.id').' IS NULL'
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
			'path' => TranslateModule::translate('Source Path'),
			
			// Relations
			'messageSources' => TranslateModule::translate('Source Messages'),
			'messageSourceCount' => TranslateModule::translate('Source Message Count'),
			'routes' => TranslateModule::translate('Routes'),
			'routeCount' => TranslateModule::translate('Route Count'),
			'views' => TranslateModule::translate('Compiled Views'),
			'viewCount' => TranslateModule::translate('Compiled View Count'),
			'missingViews' => TranslateModule::translate('Missing Views'),
			
			// Virtual Attributes
			'relativePath' => TranslateModule::translate('Relative Path'),
			'isMissingViews' => TranslateModule::translate('Missing Views'),
			'isReadable' => TranslateModule::translate('Readable'),
		);
	}
	
	public function virtualAttributeNames()
	{
		return array(
			'isReadable', 
			'isMissingViews'
		);
	}
	
	public function attributeTypes()
	{
		return array(
			'isReadable' => 'boolean',
			'isMissingViews' => 'boolean'
		);
	}
	
	public function getIsMissingViews($languageId = null)
	{
		return $this->with(array('missingViews' => $languageId))->exists();
	}
	
	/**
	 * Returns whether this view source's path exists and is readable.
	 * 
	 * @return boolean True if this view source's path exists and is readable, false otherwise
	 */
	public function getIsReadable($refresh = false)
	{
		if($refresh || !isset($this->_isReadable))
		{
			$this->_isReadable = $this->getScenario() === 'search' ? null : is_readable($this->path);
		}
		return $this->_isReadable;
	}
	
	public function setIsReadable($readable)
	{
		$this->_isReadable = $readable === '' ? null : $readable;
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($dataProviderConfig = array(), $mergeCriteria = array(), $operator = 'AND')
	{
		$dataProviderConfig['criteria'] = $this->getSearchCriteria($mergeCriteria, $this->getTableAlias(), $operator);
		
		if($this->_isReadable !== null)
		{
			return new TArrayDataProvider($this->filter($this->findAll($dataProviderConfig['criteria'])));
		}
		return new CActiveDataProvider($this, $dataProviderConfig);
	}

	public function __toString()
	{
		return (string)$this->path;
	}

}