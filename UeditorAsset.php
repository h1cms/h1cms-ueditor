<?php

namespace h1cms\ueditor;

use yii\web\AssetBundle;

class UeditorAsset extends AssetBundle {
	public $js = [ 
			'ueditor.config.js',
			'ueditor.all.js' 
	];
	public function init() {
		$this->sourcePath = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets';
	}
}