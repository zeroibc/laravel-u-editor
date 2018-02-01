<?php

namespace Stevenyangecho\UEditor\Uploader;

use OSS\Core\OssException;
use OSS\OssClient;

/**
 *
 *
 * trait UploadOSS
 *
 * OSS 上传 类
 *
 * @package Stevenyangecho\UEditor\Uploader
 */
trait UploadOSS
{

	public function uploadOSS($key, $content)
	{

		$accessKeyId  = config('UEditorUpload.core.oss.accessKey');
		$accessSecret = config('UEditorUpload.core.oss.secretKey');
		$endpoint     = config('UEditorUpload.core.oss.endpoint');
		$bucket       = config('UEditorUpload.core.oss.bucket');
		$object       = $key;

		try
		{
			$oss = new OssClient($accessKeyId, $accessSecret, $endpoint);

			try
			{
				$res = $oss->putObject($bucket, $object, $content);

				$this->fullName  = config('UEditorUpload.core.oss.url') . '/' . $object;
				$this->stateInfo = $this->stateMap[0];
			}
			catch (OssException $e)
			{
				$this->stateInfo = $e->getMessage();
			}
		}
		catch (OssException $e)
		{
			$this->stateInfo = $e->getMessage();
		}

		return true;
	}
}