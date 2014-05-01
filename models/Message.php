<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class Message extends TActiveRecord
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
	
	public function init()
	{
		if($this->getScenario() === 'insert')
		{
			$this->last_modified = time();
		}
	}

	public function tableName()
	{
		return TranslateModule::messageSource()->translatedMessageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'language_id' => array('partialMatch' => false, 'escape' => true),
					'translation' => array('partialMatch' => true, 'escape' => true),
					'last_modified' => array('partialMatch' => false, 'escape' => true),
				)
			),
		));
	}

	public function rules()
	{
		return array(
			array('id, language_id, translation', 'required', 'except' => 'search'),
			array('last_modified', 'default', 'value' => time(), 'except' => 'search'),
			array('id, language_id, last_modified', 'numerical', 'integerOnly' => true),
			array('language_id', 'exist', 'attributeName' => 'id', 'className' => 'Language', 'except' => 'search'),
			array('id', 'exist', 'attributeName' => 'id', 'className' => 'MessageSource', 'except' => 'search'),

			array('id, language_id, translation, last_modified, lastModifiedDate', 'safe', 'on' => 'search'),
		);
	}

	public function relations()
	{
		$relations = parent::relations();
		if(Language::model()->getIsInstalled())
		{
			$relations['language'] = array(self::BELONGS_TO, 'Language', 'language_id');
		}		
		if(MessageSource::model()->getIsInstalled())
		{
			$relations['source'] = array(self::BELONGS_TO, 'MessageSource', 'id');
		}
		return $relations;
	}
	
	public function getLastModifiedDate()
	{
		return empty($this->last_modified) ? null : date('Y-m-d H:i:s', (int)$this->last_modified);
	}
	
	public function setLastModifiedDate($date)
	{
		$last_modified = empty($date) ? null : strtotime($date);
		$this->last_modified = ($last_modified === false || $last_modified === -1 ? null : $last_modified);
	}
	
	public function getAttributeLabel($attribute)
	{
		return CModel::getAttributeLabel($attribute);
	}

	public function attributeLabels()
	{
		return array(
			// Attributes
			'id' => TranslateModule::translate('ID'),
			'language_id' => TranslateModule::translate('Language ID'),
			'translation' => TranslateModule::translate('Translation'),
			'last_modified' => TranslateModule::translate('Last Modified'),
			
			// Relations
			'source' => TranslateModule::translate('Source'),
			'language' => TranslateModule::translate('Language'),
			
			// Virtual Attributes
			'lastModifiedDate' => TranslateModule::translate('Date Last Modified')
		);
	}
	
	public function attributeTypes()
	{
		return array(
			'last_modified' => 'datetime'
		);
	}

	public function scopes()
	{
		$db = $this->getDbConnection();
		return array(
			'isGarbage' => array(
				'with' => array(
					'source' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					),
					'language' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					),
				),
				'condition' => '('.$db->quoteColumnName('source.id').' IS NULL) OR ('.$db->quoteColumnName('language.id').'IS NULL)'
			),
		);
	}

	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if(!$this->getIsNewRecord())
			{
				$languageCode = $this->language->code;
				$translator = TranslateModule::translator();
				$messageSource = $translator->getMessageSourceComponent();
				if($messageSource->getIsCachingEnabled())
				{
					foreach($this->categories as $category)
					{
						$messageSource->invalidateCache($category, $languageCode);
					}
				}
				$viewSource = $translator->getViewSourceComponent();
				if($viewSource->getIsCachingEnabled())
				{
					$criteria = new CDbCriteria();
					$criteria->addInCondition('view_id', CHtml::listData(View::model()->with('messages')->findAll('messages.language_id=:language_id AND messages.id=:message_id', array(':language_id' => $this->language_id, ':message_id' => $this->id)), 'id', 'id'));
					foreach(Route::model()->with(array('routeViews' => $criteria))->findAll() as $route)
					{
						$viewSource->invalidateCache($route->route, $languageCode);
					}
				}
			}
			$this->last_modified = time();
			return true;
		}
		return false;
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
		return (string)$this->translation;
	}

}