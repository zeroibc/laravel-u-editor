<?php namespace Stevenyangecho\UEditor;

use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 列表文件 for 七牛
 * Class ListsQiniu
 *
 * @package Stevenyangecho\UEditor
 */
class ListsOSS
{
	public function __construct($allowFiles, $listSize, $path, $request)
	{
		$this->allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
		$this->listSize   = $listSize;
		$this->path       = ltrim($path, '/');
		$this->request    = $request;
	}

	public function getList()
	{
		$size  = $this->request->get('size', $this->listSize);
		$start = $this->request->get('start', '');

		$accessKeyId  = config('UEditorUpload.core.oss.accessKey');
		$accessSecret = config('UEditorUpload.core.oss.secretKey');
		$endpoint     = config('UEditorUpload.core.oss.endpoint');
		$bucket       = config('UEditorUpload.core.oss.bucket');

		try
		{
			$oss = new OssClient($accessKeyId, $accessSecret, $endpoint);

			$options = [
				'delimiter' => '',
				'prefix'    => $this->path,
				'max-keys'  => $size,
				'marker'    => $start,
			];

			try
			{
				$listObjectInfo = $oss->listObjects($bucket, $options);

				$items = $listObjectInfo->getObjectList();

				$start = $listObjectInfo->getNextMarker();

				if (empty($items))
				{
					return [
						"state" => "no match file",
						"list"  => [],
						"start" => $start,
						"total" => 0
					];
				}

				$files = [];
				foreach ($items as $v)
				{
					if (preg_match("/\.(" . $this->allowFiles . ")$/i", $v->getKey()))
					{
						$files[] = [
							'url'   => rtrim(config('UEditorUpload.core.oss.url'), '/') . '/' . $v->getKey(),
							'mtime' => $v->getType(),
						];
					}
				}

				if (empty($files))
				{
					return [
						"state" => "no match file",
						"list"  => [],
						"start" => $start,
						"total" => 0
					];
				}

				/* 返回数据 */
				$result = [
					"state" => "SUCCESS",
					"list"  => $files,
					"start" => $start,
					"total" => count($files)
				];

				return $result;

			}
			catch (OssException $e)
			{
				return [
					"state" => $e->getMessage(),
					"list"  => [],
					"start" => $start,
					"total" => 0
				];
			}
		}
		catch (OssException $e)
		{
			return [
				"state" => $e->getMessage(),
				"list"  => [],
				"start" => '',
				"total" => 0
			];
		}
	}

	/**
	 * 遍历获取目录下的指定类型的文件
	 *
	 * @param       $path
	 * @param array $files
	 *
	 * @return array
	 */
	protected function getfiles($path, $allowFiles, &$files = [])
	{

		if (!is_dir($path))
		{
			return null;
		}
		if (substr($path, strlen($path) - 1) != '/')
		{
			$path .= '/';
		}
		$handle = opendir($path);
		while (false !== ($file = readdir($handle)))
		{
			if ($file != '.' && $file != '..')
			{
				$path2 = $path . $file;
				if (is_dir($path2))
				{
					$this->getfiles($path2, $allowFiles, $files);
				}
				else
				{
					if (preg_match("/\.(" . $allowFiles . ")$/i", $file))
					{
						$files[] = [
							'url'   => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
							'mtime' => filemtime($path2)
						];
					}
				}
			}
		}

		return $files;
	}

}
