<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class InstallAction extends CAction
{

	public function run($component, $overwrite = false)
	{
		$component = Yii::app()->getComponent($component);
		if($component === null)
		{
			throw new CHttpException(404, TranslateModule::translate('The component named "{component}" could not be found.', array('{component}' => $component)));
		}
		elseif(!$component instanceof Installable)
		{
			throw new CHttpException(400, TranslateModule::translate('The component named "{component}" is not installable.', array('{component}' => $component)));
		}

		if(!is_bool($overwrite))
		{
			if(is_numeric($overwrite))
			{
				$overwrite = intval($overwrite) > 0;
			}
			elseif(is_string($overwrite))
			{
				$overwrite = strcasecmp('true', $overwrite) === 0;
			}
			else
			{
				$overwrite = false;
			}
		}
		switch($component->install(false && $overwrite))
		{
			case Installable::ERROR:
				$message = array(
					'status' => 'error',
					'message' => TranslateModule::translate('An error ocurred while attempting to install the component "{component}".', array('{component}' => $component))
				);
				break;
			case Installable::SUCCESS:
				$message = array(
					'status' => 'success',
					'message' => TranslateModule::translate('The component "{component}" has been succesfully installed.', array('{component}' => $component))
				);
				break;
			case Installable::OVERWRITE:
				$message = array(
					'status' => 'notice',
					'message' => TranslateModule::translate('Unable to install component named "{component}", a previous installation already exists. If you would like to re-install the component please confirm and try again.', array('{component}' => $component))
				);
				break;
			default:
				$message = array(
					'status' => 'error',
					'message' => TranslateModule::translate('Received an unknown result while attempting to install the component named "{component}".', array('{component}' => $component))
				);
				break;
		}
		
		if(Yii::app()->getRequest()->getIsAjaxRequest())
		{
			if($status === TController::ERROR)
			{
				throw new CHttpException(500, $message);
			}
			echo CJavaScript::jsonEncode($message);
		}
		else
		{
			Yii::app()->getUser()->setFlash(TranslateModule::ID.'-'.$message['status'], $message['message']);
			$this->getController()->redirect(Yii::app()->getRequest()->getUrlReferrer());
		}
	}

}

?>
