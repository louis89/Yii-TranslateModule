<?php

/**
 * This is the model class for table "{{translate_view_message}}".
 *
 * The followings are the available columns in table '{{translate_view_message}}':
 * @property integer $message_id
 * @property integer $view_id
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 */
class ViewMessage extends TActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ViewMessage the static model class
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
		return TranslateModule::viewSource()->viewMessageTable;
	}

	public function behaviors()
	{
		return array_merge(parent::behaviors(), array(
			'LDActiveRecordConditionBehavior' => array(
				'class' => 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior',
				'columns' => array(
					'message_id' => array('partialMatch' => false, 'escape' => true),
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
			array('message_id, view_id', 'required', 'except' => 'search'),
			array('message_id, view_id', 'numerical', 'integerOnly' => true),
			array('message_id', 'exist', 'attributeName' => 'id', 'className' => 'MessageSource', 'except' => 'search'),
			array('view_id', 'exist', 'attributeName' => 'id', 'className' => 'ViewSource', 'except' => 'search'),

			array('message_id, view_id', 'safe', 'on' => 'search'),
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
			$relations['messageSource'] = array(self::BELONGS_TO, 'MessageSource', 'message_id');
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
			'message_id' => TranslateModule::translate('Message ID'),
			'view_id' => TranslateModule::translate('View ID'),
			
			// Relations
			'messageSource' => TranslateModule::translate('Message Source'),
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