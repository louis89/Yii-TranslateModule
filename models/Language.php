<?php

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class Language extends TActiveRecord
{

	private $_name;
	
	private $_isAccepted;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return TranslateModule::messageSource()->languageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'code' => array('partialMatch' => true, 'escape' => true),
				)
			),
		));
	}

	public function rules()
	{
		return array(
			array('id, code', 'required', 'except' => 'search'),
			array('id, code', 'unique', 'except' => 'search'),
			array('id', 'numerical', 'integerOnly' => true),
			array('code', 'length', 'max' => 16),

			array('id, code, isAccepted', 'safe', 'on' => 'search')
		);
	}

	public function relations()
	{
		$relations = parent::relations();
		if(AcceptedLanguage::model()->getIsInstalled())
		{
			$relations['acceptedLanguage'] = array(self::HAS_ONE, 'AcceptedLanguage', 'id');
		}
		if(Message::model()->getIsInstalled())
		{
			$relations['translations'] = array(self::HAS_MANY, 'Message', 'language_id');
			$relations['translationsCount'] = array(self::STAT, 'Message', 'language_id');
			if(MessageSource::model()->getIsInstalled())
			{
				$db = $this->getDbConnection();
				$pkColumn = $db->quoteColumnName($this->getTableAlias().'.id');
				$relations['missingTranslations'] = array(
					self::HAS_MANY,
					'MessageSource',
					MessageSource::model(),
					'joinType' => 'INNER JOIN',
					'with' => array(
						'translations' => array(
							'joinType' => 'LEFT OUTER JOIN',
							'on' => $pkColumn.'='.$db->quoteColumnName('translations.language_id'),
							'together' => true,
							'select' => false,
						)
					),
					'on' => '1=1',
					'condition' => '('.$db->quoteColumnName('missingTranslations.language_id').'!='.$pkColumn.') AND ('.$db->quoteColumnName('translations.id').' IS NULL)',
					//'group' => $pkColumn,
					'together' => true,
				);
			}
		}
		if(MessageSource::model()->getIsInstalled())
		{
			$relations['messageSources'] = array(self::HAS_MANY, 'MessageSource', 'language_id');
			$relations['messageSourceCount'] = array(self::STAT, 'MessageSource', 'language_id');
		}
		if(View::model()->getIsInstalled())
		{
			$relations['views'] = array(self::HAS_MANY, 'View', 'language_id');
			$relations['viewCount'] = array(self::STAT, 'View', 'language_id');
			if(ViewSource::model()->getIsInstalled())
			{
				$relations['missingViews'] = array(
					self::HAS_MANY,
					'ViewSource',
					ViewSource::model(),
					'joinType' => 'INNER JOIN',
					'with' => array(
						'views' => array(
							'joinType' => 'LEFT OUTER JOIN',
							'on' => $pkColumn.'='.$db->quoteColumnName('views.language_id'),
							'together' => true,
							'select' => false,
						)
					),
					'on' => '1=1',
					'condition' => $db->quoteColumnName('views.id').' IS NULL',
					'together' => true,
					//'group' => $pkColumn,
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
			'code' => TranslateModule::translate('Code'),
			
			// Relations
			'translations' => TranslateModule::translate('Translations'),
			'translationCount' => TranslateModule::translate('Translation Count'),
			'messageSources' => TranslateModule::translate('Source Messages'),
			'messageSourceCount' => TranslateModule::translate('Source Message Count'),
			'views' => TranslateModule::translate('Compiled Views'),
			'viewCount' => TranslateModule::translate('Compiled View Count'),
			'acceptedLanguage' => TranslateModule::translate('Accepted Language'),
			'missingTranslations' => TranslateModule::translate('Missing Translations'),
			'missingViews' => TranslateModule::translate('Missing Views'),
			
			// Virtual Attributes
			'name' => TranslateModule::translate('Name'),
			'isAccepted' => TranslateModule::translate('Is Accepted'),
			'isMissingTranslations' => TranslateModule::translate('Is Missing Translations'),
		);
	}
	
	public function virtualAttributeNames()
	{
		return array(
			'name', 
			'isAccepted', 
			'isMissingTranslations'
		);
	}
	
	public function attributeTypes()
	{
		return array(
			'name' => 'text', 
			'isAccepted' => 'boolean', 
			'isMissingTranslations' => 'boolean'
		);
	}
	
	public function scopes()
	{
		$db = $this->getDbConnection();
		$scopes = array(
			'acceptedLanguage' => array(
				'with' => array(
					'acceptedLanguage' => array(
						'joinType' => 'INNER JOIN',
						'together' => true,
						'select' => false
					)
				)
			),
			'notAcceptedLanguage' => array(
				'with' => array(
					'acceptedLanguage' => array(
						'joinType' => 'LEFT OUTER JOIN', 
						'together' => true,
						'select' => false
					)
				), 
				'condition' => $db->quoteColumnName('acceptedLanguage.id').' IS NULL'
			),
			'isGarbage' => array(
				'with' => array(
					'translations' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					),
					'messageSources' => array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					),
				),
				'condition' => '('.$db->quoteColumnName('translations.id').' IS NULL) AND ('.$db->quoteColumnName('messageSources.id').'IS NULL)'
			),
		);
		if($this->hasRelation('views'))
		{
			$scopes['isGarbage']['with']['views'] = array(
						'joinType' => 'LEFT OUTER JOIN',
						'together' => true,
						'select' => false
					);
			$scopes['isGarbage']['condition'] .= ' AND ('.$db->quoteColumnName('views.id').'IS NULL)';
		}
		return $scopes;
	}
	
	public function getName()
	{
		if(!isset($this->_name))
		{
			if(isset($this->code))
			{
				$this->_name = TranslateModule::translator()->getLanguageDisplayName($this->code);
				if($this->_name === false)
				{
					$this->_name = (string)$this->code;
				}
			}
			else
			{
				return '';
			}
		}
		return $this->_name;
	}

	public function getIsMissingTranslations($messageId = null)
	{
		return $this->with(array('missingTranslations' => $messageId))->exists();
	}

	public function getIsAccepted($refresh = false)
	{
		if($refresh || !isset($this->_isAccepted))
		{
			$this->_isAccepted = $this->getScenario() === 'search' ? null : $this->getRelated('acceptedLanguage') !== null;
		}
		return $this->_isAccepted;
	}

	public function setIsAccepted($accepted)
	{
		$acceptedLanguage = $this->getRelated('acceptedLanguage');
		if($accepted)
		{
			if($acceptedLanguage === null)
			{
				$acceptedLanguage = new AcceptedLanguage();
				$acceptedLanguage->setAttribute('id', $this->id);
				if(!$this->getIsNewRecord())
				{
					$acceptedLanguage->save();
				}
				$this->acceptedLanguage = $acceptedLanguage;
			}
			$this->_isAccepted = true;
		}
		else 
		{
			if($acceptedLanguage !== null)
			{
				$acceptedLanguage->delete();
				unset($this->acceptedLanguage);
			}
			$this->_isAccepted = false;
		}
	}

	protected function beforeSave()
	{
		$this->setAttribute('code', trim($this->getAttribute('code')));
		return parent::beforeSave();
	}

	protected function afterSave()
	{
		parent::afterSave();
		$acceptedLanguage = $this->getRelated('acceptedLanguage');
		if($acceptedLanguage !== null && $acceptedLanguage->getIsNewRecord())
		{
			$acceptedLanguage->save();
		}
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
		return $this->getName();
	}

}