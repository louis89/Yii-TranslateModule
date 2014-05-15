<?php

Yii::import('zii.widgets.jui.CJuiProgressBar');
Yii::import('zii.widgets.jui.CJuiDialog');

/**
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 *
 */
class EActionDialog extends CJuiDialog
{
	
	const SUCCESS = 'success';
	
	const NOTICE = 'notice';
	
	const ERROR = 'error';
	
	const CONFIRM = 'confirm';
	
	const LOADING = 'loading';

	/**
	 *
	 * @var string open action progress dialog after clicking these elements.
	 */
	public $target = '.action-dialog-open-link';
	
	public $options = array(
		'autoOpen' => false,
		'modal' => true,
		'width' => 'auto',
		'height' => 'auto',
	);
	
	public $actionOptions = array(
		'requestType' => 'get',
		'confirmVarName' => 'confirm',
		'messages' => array(
			'default' => ''
		),
		'progressBarClasses' => array(
			'default' => 'action-dialog-hide',
			self::LOADING => ''
		),
		'contentClasses' => array(
			'default' => '',
			self::ERROR => 'action-dialog-error',
			self::NOTICE => 'action-dialog-notice',
			self::SUCCESS => 'action-dialog-success',
		),
		'progressBarOptions' => array(),
		'dialogOptions' => array(
			'default' => array(
				'buttons' => array(),
				'position' => array('center', 'center'),
			),
			self::CONFIRM => array(
				'buttons' => array(),
				'position' => array('center', 'center'),
			),
			self::LOADING => array(
				'buttons' => array(),
				'position' => array('center', 'center'),
			),
		),
	);

	public $progressBarOptions = array(
		'value' => 100 // necessary if indeterminate
	);

	public $tCategory = TranslateModule::ID;
	
	public static function actions()
	{
		return array(
			'create' => 'translate.widgets.actionDialog.actions.CreateAction',
			'read' => 'translate.widgets.actionDialog.actions.ReadAction',
			'update' => 'translate.widgets.actionDialog.actions.UpdateAction',
			'delete' => 'translate.widgets.actionDialog.actions.DeleteAction',
		);
	}
	
	public function init()
	{
		if(!isset($this->actionPrefix))
		{
			$this->actionPrefix = get_class($this).'.';
		}
		$this->attachBehavior('LDPublishAssetsBehavior', array(
			'class' => 'ext.LDPublishAssetsBehavior.LDPublishAssetsBehavior',
			'assetsDir' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets'
		));
		
		parent::init();
	}

	public function run()
	{
		$this->render('dialogContent', array('id' => $this->getId(), 'target' => $this->target, 'actionOptions' => $this->actionOptions, 'progressBarOptions' => $this->progressBarOptions, 'tCategory' => $this->tCategory));
		parent::run();
	}
	
	public static function renderResponse()
	{
		
	}

}
?>