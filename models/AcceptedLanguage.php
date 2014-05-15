<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class AcceptedLanguage extends TActiveRecord
{

	private $_isMissingTranslations;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return TranslateModule::messageSource()->acceptedLanguageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
				)
			),
		));
	}

	public function rules()
	{
		return array(
			array('id', 'required', 'except' => 'search'),
			array('id', 'unique', 'except' => 'search'),
			array('id', 'numerical', 'integerOnly' => true),
			array('id', 'exist', 'attributeName' => 'id', 'className' => 'Language', 'except' => 'search'),

			array('id', 'safe', 'on' => 'search')
		);
	}

	public function relations()
	{
		$relations = parent::relations();
		if(Language::model()->getIsInstalled())
		{
			$relations['language'] = array(self::BELONGS_TO, 'Language', 'id');
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
			
			// Relations
			'language' => TranslateModule::translate('Language'),
		);
	}
	
	public function scopes()
	{
		return array(
			'isGarbage' => array(
				'with' => array(
					'language' => array(
						'joinType' => 'LEFT OUTER JOIN', 
						'together' => true,
						'select' => false
					)
				), 
				'condition' => $this->getDbConnection()->quoteColumnName('language.id').' IS NULL'
			),
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
		return (string)$this->getRelated('language');
	}

}