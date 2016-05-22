<?php

namespace h1cms\ueditor;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\InputWidget;

class Ueditor extends InputWidget {
	// ����ѡ�����Ueditor�����ĵ�(���Ʋ˵���)
	public $clientOptions = [ ];
	// Ĭ������
	protected $_options;
	
	public $name = 'ueditor';
	
	public function init() {
		$this->id = $this->hasModel () ? Html::getInputId ( $this->model, $this->attribute ) : $this->id;
		$this->_options = [ 
				'serverUrl' => Url::to ( [ 
						'upload' 
				] ),
				'initialFrameWidth' => '100%',
				'initialFrameHeight' => '300',
				'lang' => (strtolower ( Yii::$app->language ) == 'en-us') ? 'en' : 'zh-cn' 
		];
		$this->clientOptions = ArrayHelper::merge ( $this->_options, $this->clientOptions );
		parent::init ();
	}
	public function run() {
		$this->registerClientScript ();
		if ($this->hasModel ()) {
			return Html::activeTextarea ( $this->model, $this->attribute, [ 
					'id' => $this->id 
			] );
		} else {
			return Html::textarea ( $this->id, $this->value, [ 
					'id' => $this->id 
			] );
		}
	}
	protected function registerClientScript() {
		UEditorAsset::register ( $this->view );
		$clientOptions = Json::encode ( $this->clientOptions );
		$script = "UE.getEditor('" . $this->id . "', " . $clientOptions . ")";
		$this->view->registerJs ( $script, View::POS_READY );
	}
}
