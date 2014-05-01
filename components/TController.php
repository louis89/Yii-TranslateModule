<?php

Yii::import(TranslateModule::ID.'.components.ITranslateModuleComponent');

/**
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 *
 */
class TController extends CController implements ITranslateModuleComponent
{
	
	const SUCCESS = 'success';
	
	const ERROR = 'error';
	
	const NOTICE = 'notice';
	
	const NORMAL = 'normal';
	
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs = array();
	
	public $localLayout = 'main';
	
	private $_translateModuleID = TranslateModule::ID;

	private $_assetsUrls = array();
	
	private $_oldStateKeyPrefix;

	/**
	 * (non-PHPdoc)
	 * @see CController::filters()
	*/
	public function filters()
	{
		return array(
			array('application.filters.HttpsFilter'),
		/*	'accessControl' => array(
				'srbac.components.SrbacAccessControlFilter',
				'rules' => $this->accessRules()
			),*/
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see CController::accessRules()
	 */
	public function accessRules()
	{
		return array(array('deny'));
	}
	
	public function getTranslateModuleID()
	{
		return $this->_translateModuleID;
	}
	
	public function setTranslateModuleID($translateModuleID = TranslateModule::ID)
	{
		$this->_translateModuleID = $translateModuleID;
	}
	
	public function getTranslateModule()
	{
		return TranslateModule::findModule($this->getTranslateModuleID());
	}

	/**
	 * (non-PHPdoc)
	 * @see CController::getActionParams()
	 */
	public function getActionParams()
	{
		$actionParams = parent::getActionParams();
		$request = Yii::app()->getRequest();
		if($request->getIsPostRequest())
		{
			$actionParams += $_POST;
		}
		elseif(strcasecmp($request->getRequestType(), 'GET'))
		{
			$actionParams += $request->getRestParams();
		}
		return $actionParams;
	}

	/**
	 * Given a sub directory within the assets directory this function returns the URL to that asset directory.
	 *
	 * @param string $location The sub directory in the assets directory to get a URL for.
	 * @return string The URL to the asset directory
	 */
	public function getAssetsUrl($location = null)
	{
		if(!isset($location))
		{
			$location = $this->getId();
		}
		if(!isset($this->_assetsUrls[$location]))
		{
			$assetsDir = Yii::getPathOfAlias('translate.assets.'.$location);
				
			if(is_dir($assetsDir))
			{
				$this->_assetsUrls[$location] = Yii::app()->getAssetManager()->publish($assetsDir, false, -1, YII_DEBUG);
			}
			else
			{
				$this->_assetsUrls[$location] = Yii::app()->getTheme()->getBaseUrl();
			}
		}
		return $this->_assetsUrls[$location];
	}

	/**
	 * Convenience method for getting the URL to a stylesheet asset.
	 *
	 * @param string $file The stylesheet asset file name to get a URL for
	 * @param string $location The sub directory in the assets directory to get a URL for
	 * @return string The URL to the stylesheet asset
	 */
	public function getStylesUrl($file = '', $directory = null)
	{
		return $this->getAssetsUrl($directory).'/styles/'.$file;
	}

	/**
	 * Convenience method for getting the URL to a script asset.
	 *
	 * @param string $file The script asset file name to get a URL for
	 * @param string $location The sub directory in the assets directory to get a URL for
	 * @return string The URL to the script asset
	 */
	public function getScriptsUrl($file = '', $directory = null)
	{
		return $this->getAssetsUrl($directory).'/scripts/'.$file;
	}

	/**
	 * Convenience method for getting the URL to an image asset.
	 *
	 * @param string $file The image asset file name to get a URL for
	 * @param string $location The sub directory in the assets directory to get a URL for
	 * @return string The URL to the image asset
	 */
	public function getImagesUrl($file = '', $directory = null)
	{
		return $this->getAssetsUrl($directory).'/images/'.$file;
	}
	
	/**
	 * @return string Returns the ath alias to local layouts.
	 * Local layouts are layouts applied to all views before applying the application/module/controller's global layout.
	 */
	public function getLocalLayoutPathAlias()
	{
		if(($module = $this->getModule()) === null)
		{
			$module = Yii::app();
		}
		return $module->getId().'.views.layouts.local';
	}

	/**
	 * (non-PHPdoc)
	 * @see CController::render()
	 */
	public function render($view, $data = null, $return = false)
	{
		$localLayoutPathAlias = $this->getLocalLayoutPathAlias().'.'.$this->localLayout;
		if(Yii::getPathOfAlias($localLayoutPathAlias) !== false)
		{
			return parent::render($localLayoutPathAlias, array('content' => $this->renderPartial($view, $data, true)), $return);
		}
		return parent::render($view, $data, $return);
	}
	
	public function renderMessage($status, $message = '', $redirectRoute = null)
	{
		if(Yii::app()->getRequest()->getIsAjaxRequest())
		{
			if($status instanceof Exception)
			{
				throw $status;
			}
			if($status === TController::ERROR)
			{
				throw new CHttpException(500, $message);
			}
			echo CJavaScript::jsonEncode(array('status' => $status, 'message' => $message));
		}
		else
		{
			if($status instanceof Exception)
			{
				$message = $status->getMessage();
				$status = TController::ERROR;
			}
			Yii::app()->getUser()->setFlash(TranslateModule::ID.'-'.$status, $message);
			if($redirectRoute !== false)
			{
				$this->redirect($redirectRoute === null ? Yii::app()->getRequest()->getUrlReferrer() : $redirectRoute);
			}
		}
	}
	
	/**
	 * Forwards the current action to another action and returns whatever is rendered by that other action.
	 *
	 * @param string $route The route to the other module/controller/action
	 * @return string The content rendered by the specified route.
	 */
	public function forwardAndReturn($route)
	{
		ob_start();
		ob_implicit_flush(false);
		$this->forward($route, false);
		return ob_get_clean();
	}

}
