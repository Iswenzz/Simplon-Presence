<?php

namespace App;

use App\Entity\Presence;
use App\Entity\Schedule;
use App\Entity\User;
use App\Repository\PresenceRepository;
use App\Repository\ScheduleRepository;
use DateTime;
use DateTimeZone;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
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
	 * @var TSchedule[] $schedules
	 */
	public array $schedules;

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
	 * Setup the application.
	 */
	public function setup(): void
	{
		$this->setDescription("Discord BOT Application");

		// Parse all schedules
		$schedules = explode(",", $_ENV["DISCORD_SCHEDULES"]);
		foreach ($schedules as $schedule)
		{
			$range = explode("-", $schedule);
			try
			{
				$start = new DateTime($range[0], new DateTimeZone($_ENV["DISCORD_SCHEDULE_TIMEZONE"]));
				$end = new DateTime($range[1], new DateTimeZone($_ENV["DISCORD_SCHEDULE_TIMEZONE"]));
				$this->schedules[] = new TSchedule($start, $end);
			}
			catch (Exception $e)
			{
				$this->logger->error($e);
			}
		}
	}

	/**
	 * Start the discord BOT.
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
		$this->setup();

		// Start Discord BOT
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
	 * Register a user.
	 * @param User $user - The user to register.
	 * @return bool - User registration status.
	 * @throws Exception
	 */
	public function registerUser(User $user): bool
	{
		/**
		 * @var PresenceRepository $presenceRepo
		 * @var ScheduleRepository $scheduleRepo
		 */
		$now = new DateTime("NOW", new DateTimeZone($_ENV["DISCORD_SCHEDULE_TIMEZONE"]));
		$presenceRepo = $this->entityManager->getRepository(Presence::class);
		$scheduleRepo = $this->entityManager->getRepository(Schedule::class);
		try
		{
			/**
			 * @var TSchedule $scheduleNow
			 */
			// Find/Create the Presence for the day.
			$presence = $presenceRepo->findOneByDate($now->format("y-m-d"));
			if (!$presence)
			{
				$presence = new Presence();
				$presence->setDate($now);
			}
			if (!count($this->schedules))
			{
				$this->logger->error("No schedule found!");
				return false;
			}

			// Find/Create the Schedule.
			$scheduleNow = current(array_filter($this->schedules, fn($s) => $s->IsInRangeToday($now)));
			if (!$scheduleNow)
				return false;
			$schedule = $scheduleRepo->findOneByRange($scheduleNow->start, $scheduleNow->end);
			if (!$schedule)
			{
				$schedule = new Schedule();
				$schedule->setStart($scheduleNow->start);
				$schedule->setEnd($scheduleNow->end);
				$schedule->setPresence($presence);
				$presence->addSchedule($schedule);
			}

			// Verify user
			foreach ($schedule->getUsers() as $u)
				if ($u->getName() === $user->getName())
					return false;

			// Add the user to the DB
			$schedule->addUser($user);
			$this->entityManager->persist($user);
			$this->entityManager->persist($schedule);
			$this->entityManager->persist($presence);
			$this->entityManager->flush();
			return true;
		}
		catch (NonUniqueResultException $e)
		{
			$this->logger->error($e);
		}
		return false;
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
	 */
	public function onMessage(Message $message): void
	{
		try
		{
			// Delete the user's message if this regex doesnt match
			if (!preg_match($_ENV["DISCORD_WRONG_MESSAGE_REGEX"], $message->content))
			{
				$message->delete();
				return;
			}
			$user = new User();
			$user->setName($message->author->nick);
			$user->setDate(new DateTime("NOW", new DateTimeZone($_ENV["DISCORD_SCHEDULE_TIMEZONE"])));

			// Register user
			if (!$this->registerUser($user))
				$message->delete();
		}
		catch (Exception $e)
		{
			$this->logger->error($e);
		}
	}
}
