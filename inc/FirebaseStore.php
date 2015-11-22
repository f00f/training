<?php
namespace Training;

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\Token\TokenException;
use Firebase\Token\TokenGenerator;


class FirebaseStore
{
    /**
     * The uid option of the Firebase token.
     *
     * @var string
     */
    public $AuthUID = '';

    /**
     * The URL the Firebase (including the path).
     *
     * @var string
     */
    private $url = '';

    /**
     * The Firebase secret.
     *
     * @var string
     */
    private $secret;

    /**
     * The generated Firebase auth token.
     *
     * @var string
     */
	private $token;

    /**
     * The Firebase reference.
     *
     * @var Firebase
     */
	private $fbRef;

	public function __construct($url, $secret)
	{
		$this->url = $url;
		$this->secret = $secret;
	}

	public function StoreData($allPlayers, &$training)
	{
		try {
			$this->CreateFirebaseRef();
		} catch(\Exception $e) {
			return;
		}

		$this->fbRef->set('/all-players', $allPlayers);
		$this->fbRef->set('/training', $training);
	}

	private function CreateFirebaseRef()
	{
		if ($this->fbRef) { return; }

		$this->CreateToken();
		$this->fbRef = new \Firebase\FirebaseLib($this->url, $this->token);
	}

	private function CreateToken()
	{
		if ($this->token) { return; }

		try {
			$generator = new TokenGenerator($this->secret);
			$this->token = $generator
				->setData(array('uid' => $this->AuthUid))
				->create();
		} catch (TokenException $e) {
			echo "Error: ".$e->getMessage();
			$this->token = false;
		}
	}
}
