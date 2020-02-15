<?php

namespace Phpload\UploadedNetAccount;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use phpload\core\helpers\Dictionary;
use phpload\core\models\DownloadJob;

class Bootstrap implements BootstrapInterface
{
	public function bootstrap($app)
	{
		// register Premiumaccount to phpload
		Event::on(
			DownloadJob::class,
			Dictionary::EVENT_SUBSCRIBE_ACCOUNT_TYPE,
			function () {
				// UploadedNetAccount
			}
		);
	}
}