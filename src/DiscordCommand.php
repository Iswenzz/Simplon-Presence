<?php

namespace App;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Discord BOT Application.
 * @package App
 */
class DiscordCommand extends Command
{
	protected static $defaultName = "app:run";

	public Discord $discord;
	public Guild $server;
	public Channel $channel;

	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;

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
		$this->logger = new ConsoleLogger($output, [
			LogLevel::NOTICE  => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::INFO    => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::ERROR   => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
			LogLevel::DEBUG   => OutputInterface::VERBOSITY_NORMAL,
		]);
		try
		{
			$this->discord = new Discord([
				"token" => $_ENV["DISCORD_TOKEN"],
				"logging" => false
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
		$this->logger->info("BOT Ready");
		$this->discord->on("message", fn(Message $message) => $this->onMessage($message));

		// Get the right server & channel.
		$this->server = $this->discord->guilds->offsetGet($_ENV["DISCORD_SERVER_ID"]);
		$this->channel = $this->server->channels->offsetGet($_ENV["DISCORD_CHANNEL_ID"]);
	}

	/**
	 * On bot message callback.
	 * @param Message $message - The user message.
	 * TODO get/create the presence table for today with all schedules.
	 * TODO .env with all schedules.
	 * TODO when getting the precence table, get the right schedule & add the discord user.
	 */
	public function onMessage(Message $message): void
	{
		// Delete the user's message if this regex doesnt match
		if (!preg_match($_ENV["DISCORD_WRONG_MESSAGE_REGEX"], $message->content))
		{
			$message->delete();
			return;
		}
	}
}
