<?php if($confStatModel->getComponent() === null): ?>
	<tr>
		<th class="right"><?php echo TranslateModule::translate('Status:'); ?></th>
		<td class="fill"><?php echo TranslateModule::translate('This component is either disabled or has not been properly configured.'); ?></td>
	</tr>
<?php else: ?>
	<tr>
		<th class="right"><?php echo TranslateModule::translate('Type:'); ?></th>
		<td class="fill"><?php echo $formatter->format($confStatModel->getComponentType(), $confStatModel->getAttributeType('componentType')); ?></td>
	</tr>
	<?php if($confStatModel->getIsInstallable()): 
		$installed = $confStatModel->getComponent()->getIsInstalled();
		$installableModel = new InstallableModel($confStatModel->getComponent());
		$installableModel->validate();
		?>
		<tr class="status" id="<?php echo $confStatModel->getComponentId(); ?>-installStatus">
			<th class="right"><?php echo TranslateModule::translate('Status:'); ?></th>
			<td class="fill <?php echo $installed ? 'translateNoError' : 'translateError'; ?>"><?php echo $installed ? TranslateModule::translate('Installed') : TranslateModule::translate('Not Installed'); ?></td>
		</tr>
		<tr class="install" id="<?php echo $confStatModel->getComponentId(); ?>-install">
			<th class="right"></th>
			<?php if($confStatModel->hasErrors('isInstalled')): ?>
			<td class="fill attributeConfigError">
				<?php echo ConfigurationStatusWidget::errorSummary($confStatModel, 'isInstalled', ''); ?>
			</td>
			<?php endif; ?>
			<td class="fill<?php echo $installableModel->hasErrors() ? ' attributeConfigError' : '' ?>">
			<?php
			$this->widget('translate.widgets.configurationStatus.InstallableWidget', array('component' => $confStatModel->getComponentID()));
			if($installableModel->hasErrors())
			{
				echo ConfigurationStatusWidget::errorSummary($installableModel);
			}
			?>
			</td>
		</tr>
	<?php else: ?>
		<tr>
			<th class="right"><?php echo TranslateModule::translate('Status:'); ?></th>
			<td class="fill <?php echo ConfigurationStatusWidget::getStatusClass($confStatModel->getStatus()); ?>">
				<?php echo ConfigurationStatusWidget::getStatusMessage($confStatModel->getStatus()); ?>
			</td>
		</tr>
	<?php endif; ?>
<?php endif; ?>