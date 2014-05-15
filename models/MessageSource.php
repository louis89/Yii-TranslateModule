<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class MessageSource extends TActiveRecord
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return TranslateModule::messageSource()->sourceMessageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'language_id' => array('partialMatch' => false, 'escape' => true),
					'message' => array('partialMatch' => true, 'escape' => true),
				)
			),
		));
	}

	public function rules()
	{
		return array(
			array('message', 'required', 'except' => 'search'),
			array('id, language_id', 'numerical', 'integerOnly' => true),
			array('language_id', 'exist', 'attributeName' => 'id', 'className' => 'Language', 'except' => 'search'),
			array('id', 'unique', 'except' => 'search'),

			array('id, message', 'safe', 'on' => 'search'),
		);
	}

	public function relations()
	{
		$relations = parent::relations();
		if(Category::model()->getIsInstalled())
		{
			$relations['categories'] = array(self::MANY_MANY, 'Category', CategoryMessage::model()->tableName().'(message_id, category_id)');
			$relations['categoryCount'] = array(self::STAT, 'Category', CategoryMessage::model()->tableName().'(message_id, category_id)');
		}
		if(Language::model()->getIsInstalled())
		{
			$relations['language'] = array(self::BELONGS_TO, 'Language', 'language_id');
		}
		if(Message::model()->getIsInstalled())
		{
			$relations['translations'] = array(self::HAS_MANY, 'Message', 'id');
			$relations['translationCount'] = array(self::STAT, 'Message', 'id');
		}
		if(ViewSource::model()->getIsInstalled())
		{
			$relations['viewSources'] = array(self::MANY_MANY, 'ViewSource', ViewMessage::model()->tableName().'(message_id, view_id)');
			$relations['viewSourceCount'] = array(self::STAT, 'ViewSource', ViewMessage::model()->tableName().'(message_id, view_id)');
			if(Language::model()->getIsInstalled())
			{
				$db = $this->getDbConnection();
				$relations['missingTranslations'] = array(
					self::HAS_MANY,
					'Language',
					Language::model(),
					'joinType' => 'INNER JOIN',
					'with' => array(
						'translations' => array(
							'joinType' => 'LEFT OUTER JOIN',
							'on' => $db->quoteColumnName($this->getTableAlias().'.id').'='.$db->quoteColumnName('translations.id'),
							'together' => true,
							'select' => false,
						)
					),
					'on' => '1=1',
					'together' => true,
					'condition' => '('.$db->quoteColumnName('missingTranslations.id').'!='.$db->quoteColumnName($this->getTableAlias().'.language_id').') AND ('.$db->quoteColumnName('translations.id').' IS NULL)',
				);
			}
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
			'language_id' => TranslateModule::translate('Language ID'),
			'message' => TranslateModule::translate('Message'),
			
			// Relations
			'viewSources' => TranslateModule::translate('Views'),
			'viewSourceCount' => TranslateModule::translate('View Count'),
			'translations' => TranslateModule::translate('Translations'),
			'translationCount' => TranslateModule::translate('Translation Count'),
			'language' => TranslateModule::translate('Language'),
			'categories' => TranslateModule::translate('Categories'),
			'categoryCount' => TranslateModule::translate('Category Count'),
			'missingTranslations' => TranslateModule::translate('Missing Translations'),
			
			// Virtual Attributes
			'isMissingTranslations' => TranslateModule::translate('Missing Translations'),
		);
	}
	
	public function scopes()
	{
		$db = $this->getDbConnection();
		return array(
			'isGarbage' => array(
				'with' => array(
					'categories' => array(
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
				'condition' => '('.$db->quoteColumnName('categories.id').' IS NULL) OR ('.$db->quoteColumnName('language.id').'IS NULL)'
			),
		);
	}
	
	public function virtualAttributeNames()
	{
		return array(
			'isMissingTranslations'
		);
	}
	
	public function attributeTypes()
	{
		return array(
			'isMissingTranslations' => 'boolean'
		);
	}

	public function getIsMissingTranslations($languageId = null)
	{
		return $this->with(array('missingTranslations' => $languageId))->exists();
	}

	protected function beforeSave()
	{
		$this->setAttribute('message', trim($this->getAttribute('message')));
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
		return (string)$this->message;
	}

}