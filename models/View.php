<?php

/**
 * This is the model class for table "{{translate_view}}".
 *
 * The followings are the available columns in table '{{translate_view}}':
 * @property integer $id
 * @property string $language
 * @property string $path
 * @property integer $created
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 */
class View extends TActiveRecord
{
	
	private $_isReadable;
	
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return View the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
	
	public function init()
	{
		if($this->getScenario() !== 'search')
		{
			$this->created = time();
		}
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return TranslateModule::viewSource()->viewTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'id' => array('partialMatch' => false, 'escape' => true),
					'path' => array('partialMatch' => true, 'escape' => true),
					'created' => array('partialMatch' => false, 'escape' => true),
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
			array('path', 'filter', 'filter' => 'realpath'),
			array('id, language_id, path', 'required', 'except' => 'search'),
			array('path', 'length', 'max' => 255),
			array('created', 'default', 'value' => time(), 'except' => 'search'),
			array('id, language_id, created', 'numerical', 'integerOnly' => true),
			array('id', 'exist', 'attributeName' => 'id', 'className' => 'ViewSource', 'except' => 'search'),
			array('language_id', 'exist', 'attributeName' => 'id', 'className' => 'Language', 'except' => 'search'),
			array('path', 'unique', 'except' => 'search'),
			array('id',
				'unique',
				'caseSensitive' => false,
				'criteria' => array(
					'condition' => 'language = :language',
					'params' => array(':language' => $this->language),
				),
				'message' => 'Source view {attribute} "{value}" has already been translated to "'.$this->language.'" ("'.TranslateModule::getLocalDisplayNames($this->language, TranslateModule::messageSource()->useGenericLocales, false).'").',
				'except' => 'search'
			),

			array('id, language, language_id, path, created, createdDate, isReadable, relativePath', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = parent::relations();
		if(Language::model()->getIsInstalled())
		{
			$relations['language'] = array(self::BELONGS_TO, 'Language', 'language_id');
		}
		if(ViewSource::model()->getIsInstalled())
		{
			$relations['source'] = array(self::BELONGS_TO, 'ViewSource', 'id');
		}
		return $relations;
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

	public function getRelativePath()
	{
		if (substr($this->getAttribute('path'), 0, strlen(Yii::app()->getBasePath())) == Yii::app()->getBasePath())
		{
			return substr($this->getAttribute('path'), strlen(Yii::app()->getBasePath()));
		}
		return $this->getAttribute('path');
	}

	public function setRelativePath($path)
	{
		return $this->setAttribute('path', Yii::app()->getBasePath().DIRECTORY_SEPARATOR.$path);
	}
	
	public function getCreatedDate()
	{
		return empty($this->created) ? null : date('Y-m-d H:i:s', (int)$this->created);
	}
	
	public function setCreatedDate($date)
	{
		$created = empty($date) ? null : strtotime($date);
		$this->created = $created === false || $created === -1 ? null : $created;
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
			'language_id' => TranslateModule::translate('Language ID'),
			'path' => TranslateModule::translate('Compiled Path'),
			'created' => TranslateModule::translate('Created'),
			
			// Relations
			'source' => TranslateModule::translate('Source'),
			'language' => TranslateModule::translate('Language'),
			
			// Virtual Attributes
			'relativePath' => TranslateModule::translate('Relative Path'),
			'createdDate' => TranslateModule::translate('Date Created'),
			'isReadable' => TranslateModule::translate('Readable'),
		);
	}
	
	public function virtualAttributeNames()
	{
		return array(
			'isReadable'
		);
	}
	
	public function attributeTypes()
	{
		return array(
			'created' => 'datetime', 
			'isReadable' => 'boolean'
		);
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