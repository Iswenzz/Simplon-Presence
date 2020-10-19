<?php

namespace App;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Discord BOT Application.
 * @package App
 */
class DiscordCommand extends Command
{
	protected static $defaultName = "app:run";

	public Discord $discord;
	private EntityManagerInterface $entityManager;
	private OutputInterface $output;

	/**
	 * DiscordCommand constructor.
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
		parent::__construct();
	}

	/**
	 * Configure the command.
	 */
	public function configure(): void
	{
		$this->setDescription("Discord BOT Application");
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;
		try
		{
			$this->discord = new Discord([
				"token" => $_ENV["DISCORD_TOKEN"]
			]);
			$this->discord->on("ready", fn() => $this->onReady());
			$this->discord->run();
		}
		catch (IntentException $e)
		{
			print_r($e);
		}
	}

	/**
	 * On bot ready callback.
	 */
	public function onReady(): void
	{
		$this->output->writeln("[BOT] Ready");
		$this->discord->on("message", fn(string $message) => $this->onMessage($message));
	}

	/**
	 * On bot message callback.
	 * @param string $message - The discord message.
	 */
	public function onMessage(string $message): void
	{
		$this->output->writeln("[BOT] Message: " . $message);
	}
}
