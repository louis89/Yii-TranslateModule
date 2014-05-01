<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class Category extends TActiveRecord 
{

	private $_isMissingTranslations;

	public static function model($className = __CLASS__) 
	{
		return parent::model($className);
	}

	public function tableName() 
	{
		return TranslateModule::messageSource()->categoryTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'category' => array('partialMatch' => true, 'escape' => true),
				)
			),
		));
	}

	public function rules() 
	{
		return array(
			array('category', 'required', 'except' => 'search'),
			array('id', 'numerical', 'integerOnly' => true),
			array('id, category', 'unique', 'except' => 'search'),
			array('category', 'length', 'max' => 32),

			array('id, category', 'safe', 'on' => 'search'),
		);
	}

	public function relations() 
	{
		$relations = parent::relations();
		if(MessageSource::model()->getIsInstalled())
		{
			$relations['messageSources'] = array(self::MANY_MANY, 'MessageSource', CategoryMessage::model()->tableName().'(category_id, message_id)');
			$relations['messageSourceCount'] = array(self::STAT, 'MessageSource', CategoryMessage::model()->tableName().'(category_id, message_id)');
		}
		return $relations;
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
			'category' => TranslateModule::translate('Category'),
			
			// Relations
			'messageSources' => TranslateModule::translate('Source Messages'),
			'messageSourceCount' => TranslateModule::translate('Source Message Count'),
		);
	}
	
	public function scopes()
	{
		return array(
			'isGarbage' => array(
				'with' => array(
					'messageSources' => array(
						'joinType' => 'LEFT OUTER JOIN', 
						'together' => true,
						'select' => false
					)
				), 
				'condition' => $this->getDbConnection()->quoteColumnName('messageSources.id').' IS NULL'
			),
		);
	}

	protected function beforeSave()
	{
		$this->setAttribute('category', trim($this->getAttribute('category')));
		return parent::beforeSave();
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
		return (string)$this->category;
	}

}