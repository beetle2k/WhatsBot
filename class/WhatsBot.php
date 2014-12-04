<?php
	require_once 'whatsapi/whatsprot.class.php';
	require_once 'WhatsBotListener.php';
	require_once 'WhatsBotParser.php';
	require_once 'ModuleManager.php';
	require_once 'WhatsBotCaller.php';
	require_once 'WhatsappBridge.php';
	require_once 'DB/DB.php';
	require_once 'Utils.php';

	final class WhatsBot
	{
		private $Whatsapp = null;
		private $Password = null;

		private $Listener = null;

		private $Parser = null;
		private $ModuleManager = null;
		private $Caller = null;
		private $Bridge = null;

		private $DB = null;

		public function __construct($Debug = false)
		{
			$Config = Utils::GetJson('config/WhatsBot.json');

			if($Config !== false && !empty($Config['database']['filename']) && !empty($Config['whatsapp']['username']) && !empty($Config['whatsapp']['identity']) && !empty($Config['whatsapp']['password']) && !empty($Config['whatsapp']['nickname'])) // and DB
			{
				$this->InitDB($Config['database']['filename']);

				$this->InitWhatsAPI
				(
					$Config['whatsapp']['username'],
					$Config['whatsapp']['identity'],
					$Config['whatsapp']['password'],
					$Config['whatsapp']['nickname'],
					$Debug
				);
			}
			else
				exit('Can\'t load config...');
		}

		private function InitDB($Filename)
		{
			$this->DB = new WhatsBotDB($Filename);
		}

		private function InitWhatsAPI($Username, $Identity, $Password, $Nickname, $Debug)
		{
			$this->Whatsapp = new WhatsProt($Username, $Identity, $Nickname, $Debug);

			$this->Bridge = new WhatsappBridge($this->Whatsapp);
			$this->Caller = new WhatsBotCaller($this->ModuleManager, $this->Bridge);
			$this->ModuleManager = new ModuleManager($this->Caller);
			$this->Parser = new WhatsBotParser($this->Bridge, $this->ModuleManager);
			$this->Listener = new WhatsBotListener($this->Whatsapp, $this->Parser, $this->DB);

			$this->ModuleManager->LoadModules();

			$this->Whatsapp->eventManager()->setDebug($Debug);
			$this->Whatsapp->eventManager()->bindClass($this->Listener);

			echo 'Connecting...' . PHP_EOL;
			$this->Whatsapp->connect();
			$this->Whatsapp->loginWithPassword($Password);
		}

		public function Listen()
		{
			echo 'Listening...' . PHP_EOL;

			$StartTime = time();

			while(true)
			{
				$this->Whatsapp->pollMessage();

				if(time() >= $StartTime + 30)
				{
					$this->Whatsapp->sendPresence('active');
					$this->Whatsapp->sendPing();

					$StartTime = time();
				}
			}
		}
	}

	/* To do: 
	 * Make an parser for modules (With https://github.com/nikic/PHP-Parser ?)
	 * Flood detection / protection
	 * 
	 * https://github.com/mgp25/WhatsAPI-Official/issues/164#issuecomment-64790667
	 * Add syncing before send message (Array with numbers synceds? [IF DISCONNECT?])
	 * 
	 * Implement? https://github.com/mgp25/WhatsAPI-Official/issues/169
	 *
	 * Replace echo with Utils::Write
	 */

	/* To do (new-structure): 
	 * Fix Utils::IsAdmin
	 * Fix !setstatus
	 * Test /soundcloud/
	 * Test !search (updated)
	 */

	/*
	 * Implement: https://github.com/mgp25/WhatsAPI-Official/wiki/WhatsAPI-Documentation#whatsapp-workflow
	 */