<?php 
$attributeValue = $confStatModel->getAttribute($name); 
if($attributeValue !== null)
{
	$attributeValue = $formatter->format($attributeValue, $confStatModel->getAttributeType($name));
}
?>
<tr>
	<th class="right"><?php echo $confStatModel->getAttributeLabel($name); ?></th>
	<td class="fill <?php echo $confStatModel->hasErrors($name) ? ($confStatModel->isAttributeRequired($name) ? 'attributeConfigError' : 'attributeConfigWarning') : 'attributeConfigNoError'; ?>">
		<table class="attributeConfigGrid">
			<tr>
				<th class="right"><?php echo TranslateModule::translate('Attribute:'); ?></th>
				<td class="fill"><?php echo $name; ?></td>
			</tr>
			<tr>
				<th class="right"><?php echo TranslateModule::translate('Value:'); ?></th>
				<td class="fill"><?php echo is_scalar($attributeValue) ? $attributeValue : TranslateModule::translate('Non-printable type: "{type}"', array('{type}' => gettype($attributeValue))); ?></td>
			</tr>
			<?php if($confStatModel->hasDescription($name)): ?>
				<tr>
					<th class="right"><?php echo TranslateModule::translate('Description:'); ?></th>
					<td class="fill break"><?php echo $confStatModel->getAttributeDescription($name); ?></td>
				</tr>
			<?php endif; ?>
			<?php if($confStatModel->hasErrors($name)): ?>
				<tr>
					<th class="right"><?php echo $confStatModel->isAttributeRequired($name) ? TranslateModule::translate('Error:') : TranslateModule::translate('Warning:'); ?></th>
					<td class="fill break"><?php echo ConfigurationStatusWidget::errorSummary($confStatModel, $name, ''); ?></td>
				</tr>
			<?php endif; ?>
		</table>
	</td>
</tr>
<tr class="dummySeparator"><td colspan="2"></td></tr>
	