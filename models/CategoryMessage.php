<?php

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class CategoryMessage extends TActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CategoryMessage the static model class
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
		return TranslateModule::messageSource()->categoryMessageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'message_id' => array('partialMatch' => false, 'escape' => true),
					'category_id' => array('partialMatch' => false, 'escape' => true),
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
			array('message_id, category_id', 'required', 'except' => 'search'),
			array('message_id, category_id', 'numerical', 'integerOnly' => true),
			array('message_id', 'exist', 'attributeName' => 'id', 'className' => 'MessageSource', 'except' => 'search'),
			array('category_id', 'exist', 'attributeName' => 'id', 'className' => 'Category', 'except' => 'search'),

			array('message_id, category_id', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = parent::relations();
		if(Category::model()->getIsInstalled())
		{
			$relations['category'] = array(self::BELONGS_TO, 'Category', 'category_id');
		}
		if(MessageSource::model()->getIsInstalled())
		{
			$relations['messageSource'] = array(self::BELONGS_TO, 'MessageSource', 'message_id');
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
			'message_id' => TranslateModule::translate('Message ID'),
			'category_id' => TranslateModule::translate('Category ID'),
			
			// Relations
			'messageSource' => TranslateModule::translate('Message Source'),
			'category' => TranslateModule::translate('Category'),
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