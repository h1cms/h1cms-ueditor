<?php

namespace h1cms\ueditor;

use Yii;
use yii\base\Action;
use yii\helpers\ArrayHelper;

class UeditorAction extends Action {
	/**
	 *
	 * @var array
	 */
	public $config = [ ];
	public function init() {
		Yii::$app->request->enableCsrfValidation = false;
		$_config = require (__DIR__ . '/config.php');
		$this->config = ArrayHelper::merge ( $_config, $this->config );
		parent::init ();
	}
	public function run() {
		$this->handleAction ();
	}
	
	/**
	 * ����action
	 */
	protected function handleAction() {
		$action = Yii::$app->request->get ( 'action' );
		switch ($action) {
			case 'config' :
				$result = json_encode ( $this->config );
				break;
			
			/* �ϴ�ͼƬ */
			case 'uploadimage' :
			/* �ϴ�Ϳѻ */
			case 'uploadscrawl' :
			/* �ϴ���Ƶ */
			case 'uploadvideo' :
			/* �ϴ��ļ� */
			case 'uploadfile' :
				$result = $this->actionUpload ();
				break;
			
			/* �г�ͼƬ */
			case 'listimage' :
			/* �г��ļ� */
			case 'listfile' :
				$result = $this->actionList ();
				break;
			
			/* ץȡԶ���ļ� */
			case 'catchimage' :
				$result = $this->actionCrawler ();
				break;
			
			default :
				$result = json_encode ( array (
						'state' => '�����ַ����' 
				) );
				break;
		}
		/* ������ */
		if (isset ( $_GET ["callback"] )) {
			if (preg_match ( "/^[\w_]+$/", $_GET ["callback"] )) {
				echo htmlspecialchars ( $_GET ["callback"] ) . '(' . $result . ')';
			} else {
				echo json_encode ( array (
						'state' => 'callback�������Ϸ�' 
				) );
			}
		} else {
			echo $result;
		}
	}
	
	/**
	 * �ϴ�
	 * 
	 * @return string
	 */
	protected function actionUpload() {
		$base64 = "upload";
		switch (htmlspecialchars ( $_GET ['action'] )) {
			case 'uploadimage' :
				$config = array (
						"pathRoot" => ArrayHelper::getValue ( $this->config, "imageRoot", $_SERVER ['DOCUMENT_ROOT'] ),
						"pathFormat" => $this->config ['imagePathFormat'],
						"maxSize" => $this->config ['imageMaxSize'],
						"allowFiles" => $this->config ['imageAllowFiles'] 
				);
				$fieldName = $this->config ['imageFieldName'];
				break;
			case 'uploadscrawl' :
				$config = array (
						"pathRoot" => ArrayHelper::getValue ( $this->config, "scrawlRoot", $_SERVER ['DOCUMENT_ROOT'] ),
						"pathFormat" => $this->config ['scrawlPathFormat'],
						"maxSize" => $this->config ['scrawlMaxSize'],
						"allowFiles" => $this->config ['scrawlAllowFiles'],
						"oriName" => "scrawl.png" 
				);
				$fieldName = $this->config ['scrawlFieldName'];
				$base64 = "base64";
				break;
			case 'uploadvideo' :
				$config = array (
						"pathRoot" => ArrayHelper::getValue ( $this->config, "videoRoot", $_SERVER ['DOCUMENT_ROOT'] ),
						"pathFormat" => $this->config ['videoPathFormat'],
						"maxSize" => $this->config ['videoMaxSize'],
						"allowFiles" => $this->config ['videoAllowFiles'] 
				);
				$fieldName = $this->config ['videoFieldName'];
				break;
			case 'uploadfile' :
			default :
				$config = array (
						"pathRoot" => ArrayHelper::getValue ( $this->config, "fileRoot", $_SERVER ['DOCUMENT_ROOT'] ),
						"pathFormat" => $this->config ['filePathFormat'],
						"maxSize" => $this->config ['fileMaxSize'],
						"allowFiles" => $this->config ['fileAllowFiles'] 
				);
				$fieldName = $this->config ['fileFieldName'];
				break;
		}
		/* �����ϴ�ʵ����������ϴ� */
		
		$up = new Uploader ( $fieldName, $config, $base64 );
		/**
		 * �õ��ϴ��ļ�����Ӧ�ĸ�������,����ṹ
		 * array(
		 * "state" => "", //�ϴ�״̬���ϴ��ɹ�ʱ���뷵��"SUCCESS"
		 * "url" => "", //���صĵ�ַ
		 * "title" => "", //���ļ���
		 * "original" => "", //ԭʼ�ļ���
		 * "type" => "" //�ļ�����
		 * "size" => "", //�ļ���С
		 * )
		 */
		
		/* �������� */
		return json_encode ( $up->getFileInfo () );
	}
	
	/**
	 * ��ȡ���ϴ����ļ��б�
	 * 
	 * @return string
	 */
	protected function actionList() {
		/* �ж����� */
		switch ($_GET ['action']) {
			/* �г��ļ� */
			case 'listfile' :
				$allowFiles = $this->config ['fileManagerAllowFiles'];
				$listSize = $this->config ['fileManagerListSize'];
				$path = $this->config ['fileManagerListPath'];
				break;
			/* �г�ͼƬ */
			case 'listimage' :
			default :
				$allowFiles = $this->config ['imageManagerAllowFiles'];
				$listSize = $this->config ['imageManagerListSize'];
				$path = $this->config ['imageManagerListPath'];
		}
		$allowFiles = substr ( str_replace ( ".", "|", join ( "", $allowFiles ) ), 1 );
		
		/* ��ȡ���� */
		$size = isset ( $_GET ['size'] ) ? htmlspecialchars ( $_GET ['size'] ) : $listSize;
		$start = isset ( $_GET ['start'] ) ? htmlspecialchars ( $_GET ['start'] ) : 0;
		$end = ( int ) $start + ( int ) $size;
		
		/* ��ȡ�ļ��б� */
		$path = $_SERVER ['DOCUMENT_ROOT'] . (substr ( $path, 0, 1 ) == "/" ? "" : "/") . $path;
		$files = $this->getfiles ( $path, $allowFiles );
		if (! count ( $files )) {
			return json_encode ( array (
					"state" => "no match file",
					"list" => array (),
					"start" => $start,
					"total" => count ( $files ) 
			) );
		}
		
		/* ��ȡָ����Χ���б� */
		$len = count ( $files );
		for($i = min ( $end, $len ) - 1, $list = array (); $i < $len && $i >= 0 && $i >= $start; $i --) {
			$list [] = $files [$i];
		}
		// ����
		// for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
		// $list[] = $files[$i];
		// }
		
		/* �������� */
		$result = json_encode ( array (
				"state" => "SUCCESS",
				"list" => $list,
				"start" => $start,
				"total" => count ( $files ) 
		) );
		
		return $result;
	}
	
	/**
	 * ץȡԶ��ͼƬ
	 * 
	 * @return string
	 */
	protected function actionCrawler() {
		/* �ϴ����� */
		$config = array (
				"pathFormat" => $this->config ['catcherPathFormat'],
				"maxSize" => $this->config ['catcherMaxSize'],
				"allowFiles" => $this->config ['catcherAllowFiles'],
				"oriName" => "remote.png" 
		);
		$fieldName = $this->config ['catcherFieldName'];
		
		/* ץȡԶ��ͼƬ */
		$list = array ();
		if (isset ( $_POST [$fieldName] )) {
			$source = $_POST [$fieldName];
		} else {
			$source = $_GET [$fieldName];
		}
		foreach ( $source as $imgUrl ) {
			$item = new Uploader ( $imgUrl, $config, "remote" );
			$info = $item->getFileInfo ();
			array_push ( $list, array (
					"state" => $info ["state"],
					"url" => $info ["url"],
					"size" => $info ["size"],
					"title" => htmlspecialchars ( $info ["title"] ),
					"original" => htmlspecialchars ( $info ["original"] ),
					"source" => htmlspecialchars ( $imgUrl ) 
			) );
		}
		
		/* ����ץȡ���� */
		return json_encode ( array (
				'state' => count ( $list ) ? 'SUCCESS' : 'ERROR',
				'list' => $list 
		) );
	}
	
	/**
	 * ������ȡĿ¼�µ�ָ�����͵��ļ�
	 * 
	 * @param
	 *        	$path
	 * @param
	 *        	$allowFiles
	 * @param array $files        	
	 * @return array|null
	 */
	protected function getfiles($path, $allowFiles, &$files = array()) {
		if (! is_dir ( $path ))
			return null;
		if (substr ( $path, strlen ( $path ) - 1 ) != '/')
			$path .= '/';
		$handle = opendir ( $path );
		while ( false !== ($file = readdir ( $handle )) ) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . $file;
				if (is_dir ( $path2 )) {
					$this->getfiles ( $path2, $allowFiles, $files );
				} else {
					if (preg_match ( "/\.(" . $allowFiles . ")$/i", $file )) {
						$files [] = array (
								'url' => substr ( $path2, strlen ( $_SERVER ['DOCUMENT_ROOT'] ) ),
								'mtime' => filemtime ( $path2 ) 
						);
					}
				}
			}
		}
		return $files;
	}
}