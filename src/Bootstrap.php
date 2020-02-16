<?php

namespace Phpload\UploadedNetAccount;

use Yii;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
	public function bootstrap($phpload)
	{
		$phpload->addAccount(new Account());
	}
}