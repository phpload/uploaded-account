<?php

declare (strict_types=1);

namespace Phpload\UploadedNetAccount;

use Yii;
use yii\httpclient\{Client,Request};
use yii\helpers\ArrayHelper;
use phpload\core\models\PremiumAccount;
use phpload\core\interfaces\PremiumAccountInterface;
use yii\helpers\BaseConsole;

final class Account extends PremiumAccount implements PremiumAccountInterface
{
	const LOGIN_URL = 'http://uploaded.net/io/login';

	private $cookies = [];

	private $contentlength;
	private $filename;

	
	/**
	 * @var string the url to the download target
	 * Differs from Links inner a DLC:
	 * The Links in a DLC points to a Webform with a Button "Download"
	 * The Formaction provides the concret download server url
	 */
	public $url;

	public function getTitle(): string
	{
		return 'Uploaded.net';
	}

	public function getCookies(): array
	{
		if (!$this->username || !$this->password) {
			throw new \yii\base\InvalidConfigException("Eihter username nore password are set.");
		}

		$response = (new Client())->post(self::LOGIN_URL,[
			'id' => $this->username,
			'pw' => $this->password
		])->send();

		return $response->getCookies()->toArray();
	}

	public function promptCredentials()
	{
		$this->username = BaseConsole::input("username:");
		$this->password = BaseConsole::input("password:");

		return;
	}

	public function setUrl(string $link): self
	{
		$this->url = $this->resolveUrlByLink($link);

		return $this;
	}

	/**
	 * resolve the url by given Downloadlink from DLC
	 *
	 * @param string $link the Link from a DLC
	 *
	 * @return string|null the url or null if no url could be found
	 */
	private function resolveUrlByLink(string $link): ?string
	{
		Yii::error("Processing DLC Link " . print_r($link,true),__METHOD__);
		$request  = (new Client())
			->get($link)
			->setCookies($this->authCookies);

		$response = $request->send();

		if ($response->getStatusCode() == 451) {
			Yii::error("file is suspended due to violations of the Terms and Conditions :/ " . $link,__METHOD__);
			return null;
		}

		$doc = new \DOMDocument();
		$doc->loadHTML($response->getContent(),LIBXML_NOWARNING | LIBXML_NOERROR);
		$form = $doc->getElementsByTagName("form");

		if (!isset ($form[0])) {
			Yii::error(print_r("No Form Element found.",true),__METHOD__);
			return null;
		}

		$link = $form[0]->getAttribute('action');

		if (preg_match("/\/\/uploaded/", $link)) {
			Yii::error(print_r("Download Ticket expired.",true),__METHOD__);
			return null;
		}

		return $link ?? null;
	}

	public function download(Client $client, $filehandler): ?Request
	{
		if (!$this->url) {
			return null;
		}

		return $client->createRequest()
			->setMethod('GET')
			->setUrl($this->url)
			->setOutputFile($filehandler)
			->setCookies($this->authCookies)
		;
	}

	public function getContentlength(): int
	{
		if (!$this->contentlength) {
			$this->setFileheader($this->getUrl());	
		}

		return (int) $this->contentlength;
	}

	public function isLoggedIn(): bool
	{

	}

	public function getFilename(): string
	{
		if (!$this->filename) {
			$this->setFileheader($this->url);
		}

		return $this->filename;
	}

	private function setFileheader($url)
	{
		$head = (new Client())
			->head($url)
			->setCookies($this->authCookies)
			->send();

		$headers = $head->getHeaders();

		preg_match(
			'/filename=\"([\w\-\.\/]+)\"/',
			$headers->get('content-disposition'),
			$matches
		);

		$this->contentlength = $headers->get('content-length');
		$this->filename = ArrayHelper::getValue($matches,1);
	}

	public function getUrl(): ?string
	{
		return $this->url;
	}

	/**
	 * http://ul.to/akdoy3af
	 */
	public function probeResponsibility(string $link): bool
	{
		return true;
		
		if (preg_match("//", $link)) {
			return true;
		} else {
			return false;
		}
	}
}