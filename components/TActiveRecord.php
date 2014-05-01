<?php

Yii::import(TranslateModule::ID.'.widgets.configurationStatus.Installable');

class TActiveRecord extends CActiveRecord implements Installable
{
	
	private static $_formattedNames = array();
	
	public static function formatName($name, $translate = true)
	{
		if(!isset(self::$_formattedNames[$name]))
		{
			self::$_formattedNames[$name] = ucfirst(implode(' ', preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY)));
		}
		return $translate ? TranslateModule::translate(self::$_formattedNames[$name]) : self::$_formattedNames[$name];
	}
	
	public function getTableName()
	{
		return $this->tableName();
	}
	
	public function getIsInstalled()
	{
		try
		{
			return ($dbConn = $this->getDbConnection()) !== null && 
				($tableName = $this->tableName()) !== null && 
				($schema = $dbConn->getSchema()) !== null &&
				$schema->getTable($tableName) !== null;
		}
		catch(Exception $e){}
		return false;
	}
	
	public function installAttributeRules()
	{
		return array(
			array('tableName', 'required'),
			array('connectionID', 'translate.validators.ComponentValidator', 'type' => 'CDbConnection'),
		);
	}

	public function install($reinstall = false)
	{
		if(!$reinstall && $this->getIsInstalled())
		{
			return Installable::OVERWRITE; // Already installed
		}
		return Installable::SUCCESS;
	}
	
	public function behaviors()
	{
		return array(
			'LDActiveRecordInverseRelationsBehavior' => 'ext.LDActiveRecordPathBehavior.LDActiveRecordInverseRelationsBehavior',
			'LDAdvancedRelationsBehavior' => 'ext.LDAdvancedRelationsBehavior.LDAdvancedRelationsBehavior',
			'LDModelAdvancedAttributeBehavior' => 'ext.LDModelAdvancedAttributeBehavior.LDModelAdvancedAttributeBehavior',
			'LDActiveRecordGroupByBehavior' => 'ext.LDActiveRecordGroupByBehavior.LDActiveRecordGroupByBehavior',
			'LDModelFilterBehavior' => 'ext.LDModelFilterBehavior.LDModelFilterBehavior',
			'LDActiveRecordPathBehavior' => 'ext.LDActiveRecordPathBehavior.LDActiveRecordPathBehavior',
		);
	}
	
	
	public function getAttributeLabel($attribute)
	{
		return CModel::getAttributeLabel($attribute);
	}
	
	public function hasRelation($name, $excludeTypes = array())
	{
		$relations = $this->relations();
		return isset($relations[$name]) && !in_array($relations[$name][0], $excludeTypes);
	}
	
	public function getRelation($name)
	{
		$relations = $this->relations();
		return isset($relations[$name]) ? $relations[$name] : null;
	}
	
	public function getScope($scope)
	{
		$scopes = $this->scopes();
		return $scopes[$scope];
	}
	
	public function getRelationsTo($model)
	{
		$relations = array();
		$modelName = $model instanceof CActiveRecord ? get_class($model) : (string)$model;
		foreach($this->relations() as $relation => $config)
		{
			if($config[0] !== CActiveRecord::STAT && $config[1] === $modelName)
			{
				$relations[] = $relation;
			}
		}
		return $relations;
	}
	
}