<ul class="tables">
<?php 
$installedCount = $modelCount = 0;
if(($handle = @opendir($modelPath)))
{
	while(($file = readdir($handle)) !== false)
	{
		if(!is_dir($modelPath.DIRECTORY_SEPARATOR.$file))
		{
			$modelName = basename($file, '.php');
			if(class_exists($modelName))
			{
				$reflection = new ReflectionClass($modelName);
				if($reflection->isSubclassOf('TActiveRecord') && ($model = call_user_func(array($modelName, 'model'))) !== false)
				{
					$modelCount++;
					$modelDisplayName = ucfirst(implode(' ', preg_split('/(?=[A-Z])/', $modelName, -1, PREG_SPLIT_NO_EMPTY)));
					if($model->getIsInstalled())
					{
						$installedCount++;
						echo CHtml::tag('li', array(), CHtml::link($modelDisplayName, $this->createUrl($modelName.'/')));
					}
					else
					{
						echo CHtml::tag('li', array(), $modelDisplayName.'&nbsp;'.TranslateModule::translate('(Not installed!)'));
					}
				}
			}
		}
	}
	closedir($handle);
}
?>
</ul>
<?php
echo CHtml::tag('p', array(), TranslateModule::translate('{installedCount} of {modelCount} tables are installed. The database is {percent}% installed.', array('{installedCount}' => $installedCount, '{modelCount}' => $modelCount, '{percent}' => (integer)(100*$installedCount/$modelCount))));
if($installedCount < $modelCount)
{
	echo CHtml::tag('p', array(), TranslateModule::translate('You may install the missing database tables in the system status section below.'));
} 
?>