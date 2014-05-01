<table class="configurationGrid">
	<?php if($confStatModel->getComponentID() !== null): ?>
	<tr>
		<th class="right"><?php echo TranslateModule::translate('ID:'); ?></th>
		<td class="fill"><?php echo $confStatModel->getComponentID(); ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th class="right"><?php echo TranslateModule::translate('Description:'); ?></th>
		<td class="fill"><?php echo $confStatModel->hasErrors('component') ? ConfigurationStatusWidget::errorSummary($confStatModel, 'component', '') : $confStatModel->getDescription(); ?></td>
	</tr>
	<?php  
	if($showKey)
	{
		$this->render('rows/_key');
	}
	$this->render('rows/_status', array('confStatModel' => $confStatModel, 'formatter' => $formatter, 'actionPrefix' => $actionPrefix));
	if($confStatModel->getComponent() !== null):
	?>
	<tr>
		<th colspan="2" class="center"><?php echo TranslateModule::translate('Settings'); ?></th>
	</tr>
	<?php
	$confStatComponents = array();
	foreach($confStatModel->attributeNames() as $name)
	{
		if($confStatModel->isAttributeConfigurationStatus($name))
		{
			$confStatComponents[$name] = $confStatModel->getAttribute($name); 
		}
		else
		{
			$this->render('rows/_attribute', array('confStatModel' => $confStatModel, 'formatter' => $formatter, 'name' => $name));
		}
	}
	if(!empty($confStatComponents)): ?>
		<tr>
			<th colspan="2" class="center"><?php echo TranslateModule::translate('Components'); ?></th>
		</tr>
		<tr>
			<td colspan="2" class="center">
				<?php
				$tabs = array(); 
				foreach($confStatComponents as $name => $value)
				{
					$model = new ConfigurationStatusModel($value);
					$model->validate();
					$tabs[is_string($value) ? $confStatModel->getAttributeLabel($value) : $name] =  array('content' => $this->render('grid', array('id' => $this->getId().'-'.$name, 'confStatModel' => $model, 'formatter' => $this->formatter, 'showKey' => false, 'actionPrefix' => $actionPrefix), true), 'status' => $model->hasErrors() ? ($confStatModel->isAttributeRequired($name) ? 'attributeConfigError' : 'attributeConfigWarning') : 'attributeConfigNoError');
				}
				$this->widget(
						'ext.LDJuiTabs.LDJuiTabs',
						array(
							'tabs' => $tabs,
							'headerTemplate' => '<li><a href="{url}" title="{title}">{text}</a></li>',
							'headerTemplateExpression' => 'strtr($template, array("{text}" => "<span class=\'".$content["status"]."\'>$title</span>"))',
							'id' => 'componentStatuses-'.str_ireplace(' ', '_', $confStatModel->getComponentID())
						)
				);
				?>
			</td>
		</tr>
	<?php
	endif;
endif;?>
</table>
	