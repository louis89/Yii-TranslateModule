<?php

/**
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class DialogAction extends CAction
{
	
	const SUCCESS = 'success';
	
	const NOTICE = 'notice';
	
	const ERROR = 'error';
	
	const CONFIRM = 'confirm';
	
	const LOADING = 'loading';
	
	public $confirmRequestVarName = 'confirm';
	
	public $activeRecordConditionBehaviorName = 'LDActiveRecordConditionBehavior';

	/**
	 *
	 * @var string a callback for additional request handling.
	 */
	public $handleRequestCallback;
	
	/**
	 *
	 * @var string a callback for loading the model associated with the data being processed by the current request.
	 */
	public $loadModelCallback;

	/**
	 *
	 * @var string scripts which should be disabled on AJAX call.
	 */
	public $disableScripts = array();

	/**
	 *
	 * @var string flash message key prefix.
	 */
	public $flashKeyPrefix = TranslateModule::ID;

	/**
	 *
	 * @var boolean is this an AJAX request.
	 */
	protected $isAjaxRequest;
	
	/**
	 * 
	 * @var mixed a string, the expected request type, or an array of strings of expected request types.
	 */
	public $acceptedRequestTypes = 'get';

	/**
	 *
	 * @var array user set messages for the action.
	 */
	public $messages = array();

	/**
	 *
	 * @var mixed the redirect URL set by the user.
	 */
	public $redirectTo;

	/**
	 *
	 * @var string message category used for Yii::t method.
	 */
	public $tCategory = 'app';
	
	/**
	 * @var string the default model scenario if one is not defined for the current request type. Defaults to false meaning do not set a scenario.
	 */
	public $defaultScenario = false;
	
	/**
	 * @var array mapping of request types to scenarios.
	 */
	public $scenarioMap = array('GET' => 'search', 'POST' => 'insert', 'PUT' => 'update', 'DELETE' => 'delete');
	
	/**
	 * @var string the current model scenario. Set this to false meaning to avoid setting a scenario when laoding the model.
	 */
	public $_scenario;

	/**
	 *
	 * @var string the name of the view.
	 */
	public $view;
	
	/**
	 *
	 * @var string the name of the AJAX view.
	 */
	public $ajaxView;

	/**
	 * Initialize the action.
	 */
	protected function init()
	{
		// Create default messages array
		$defaultMessages = array(
			'error' => Yii::t($this->tCategory, 'There was an error. Please try again.'),
			'invalidRequestType' => Yii::t($this->tCategory, 'Invalid request type.'),
			'success' => Yii::t($this->tCategory, 'Action completed successfully.')
		);
		
		// Merge with user set messages if array is provided
		if(is_array($this->messages))
		{
			$this->messages = CMap::mergeArray($defaultMessages, $this->messages);
		}
		else
		{
			throw new CException(Yii::t($this->tCategory, 'Action messages need to be an array'));
		}
		
		// Check if this is an AJAX request
		if($this->getIsAjaxRequest())
		{
			// Allow only post requests
			if(!in_array(Yii::app()->getRequest()->getRequestType(), array_map('strtoupper', (array)$this->acceptedRequestTypes)))
			{
				// Output JSON encoded content
				echo CJSON::encode(array(
					'status' => EActionDialog::ERROR,
					'content' => $this->messages['invalidRequestType']
				));
		
				// Stop script execution
				Yii::app()->end();
			}
		}
			
		// If view is not set, use action id for view
		if($this->view === null)
		{
			$this->view = $this->id;
		}
		
		// If ajaxView is not set, use the view name preceeded by an underscore.
		if($this->ajaxView === null)
		{
			$this->ajaxView = '_'.$this->view;
		}
			
		if($this->redirectTo === null)
		{
			$this->redirectTo = $this->getIsAjaxRequest() ? false : Yii::app()->getRequest()->getUrlReferrer();
		}
	}
	
	public function getParams()
	{
		return $this->getController()->getActionParams();
	}
	
	public function getIsConfirmed()
	{
		$params = $this->getParams();
		return isset($params[$this->confirmRequestVarName]) && $params[$this->confirmRequestVarName] !== 'false' && (boolean)$params[$this->confirmRequestVarName];
	}
	
	/**
	 * Returns whether this is an AJAX request.
	 *
	 * @return boolean true if this is an AJAX request.
	 */
	public function getIsAjaxRequest()
	{
		if($this->isAjaxRequest === null)
		{
			$this->isAjaxRequest = Yii::app()->getRequest()->getIsAjaxRequest();
		}
		return $this->isAjaxRequest;
	}
	
	/**
	 * Get the model scenario
	 * 
	 * @return string The scenario to set when loading the model
	 */
	public function getScenario()
	{
		if($this->_scenario === null) // If the scenario has not yet been set
		{
			// If the scenarioMap is an array and the scenario for the current request type is set
			if(is_array($this->scenarioMap) && isset($this->scenarioMap[Yii::app()->getRequest()->getRequestType()]))
			{
				$this->_scenario = $this->scenarioMap[Yii::app()->getRequest()->getRequestType()];
			}
			else // If the scenarioMap is not an array or the scenario for the current request type is not set. Use the Default scenario.
			{
				$this->_scenario = $this->defaultScenario;
			}
		}
		return $this->_scenario;
	}
	
	/**
	 * Set the model scenario
	 * 
	 * @param string $scenario the model scenario
	 */
	public function setScenario($scenario)
	{
		$this->_scenario = $scenario;
	}

	/**
	 * Run the action.
	 */
	public function run()
	{
		// Initialize the action
		$this->init();
		
		// Load the model and handle the request
		$this->handleRequest($this->loadModel());
	}
	
	/**
	 * Loads the model for the current request.
	 * 
	 * @return mixed Either the load model, a CModel. Or false if the model could not be loaded.
	 */
	public function loadModel()
	{
		$model = is_callable($this->loadModelCallback) ? call_user_func($this->loadModelCallback) : false;
		if($model === false)
		{
			$modelClass = ucfirst($this->getController()->getId());
			try
			{
				$classExists = @class_exists($modelClass);
			}
			catch(Exception $e)
			{
				$classExists = false;
			}
			if($classExists)
			{
				$scenario = $this->getScenario();
				$model = $scenario === false ? new $modelClass() : new $modelClass($scenario);
			}
		}
		else
		{
			$modelClass = get_class($model);
			$scenario = $this->getScenario();
			if($scenario !== false)
			{
				$model->setScenario($scenario);
			}
		}
		if($model !== false)
		{
			$actionParams = $this->getParams();
			if(isset($actionParams[$modelClass]))
			{
				$model->setAttributes($actionParams[$modelClass]);
			}
			/*if($model->asa($this->activeRecordConditionBehaviorName) === null)
			{
				$model->attachBehavior($this->activeRecordConditionBehaviorName, 'ext.LDActiveRecordConditionBehavior.LDActiveRecordConditionBehavior');
			}*/
		}
		return $model;
	}
	
	/**
	 * Processes the current request
	 * 
	 * @param string $model The model associated with the current request
	 */
	public function handleRequest($model)
	{
		if(is_callable($this->handleRequestCallback))
		{
			call_user_func($this->handleRequestCallback, $model);
		}
	}
	
	/**
	 * Render the response
	 * 
	 * @param string $status
	 * @param string $content
	 * @throws Exception
	 * @throws CHttpException
	 */
	public function renderResponse($status, $content = '')
	{
		if($this->getIsAjaxRequest())
		{
			if($status instanceof Exception)
			{
				throw $status;
			}
			if($status === self::ERROR)
			{
				throw new CHttpException(500, $message);
			}

			echo CJSON::encode(array('status' => $status, 'content' => $content));
		}
		else
		{
			if($status instanceof Exception)
			{
				$content = $status->getMessage();
				$status = self::ERROR;
			}
			Yii::app()->getUser()->setFlash($this->flashKeyPrefix.'-'.$status, $content);
		}
		if($this->redirectTo !== false)
		{
			return $this->redirect($this->redirectTo);
		}
	}

	/**
	 * Redirect. If ajax use javascript. If not use normal redirect.
	 * Also check if this is a post request and the server's protocol is 1.1. If so status code should be 303 not 302.
	 */
	public function redirect($url, $terminate = true, $statusCode = null)
	{
		if($statusCode === null)
		{
			$statusCode = (Yii::app()->getRequest()->getIsPostRequest() && isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') ? 303 : 302;
		}
		if($this->getIsAjaxRequest())
		{
			if(is_array($url))
			{
				$url = $this->getController()->createUrl(isset($url[0]) ? $url[0] : '', array_slice($url, 1));
			}
			Yii::app()->getClientScript()->registerScript('redirect', 'window.top.location="'.$url.'"');
			if($terminate)
			{
				return Yii::app()->end($statusCode);
			}
		}
		else
		{
			return parent::redirect($url, $terminate, $statusCode);
		}
	}

}
?>