<?php

namespace App;

use DateTime;

class TSchedule
{
	public DateTime $start;
	public DateTime $end;

	/**
	 * TSchedule constructor.
	 * @param DateTime $start - The schedule start date.
	 * @param DateTime $end - The schedule end date.
	 */
	public function __construct(DateTime $start, DateTime $end)
	{
		$this->start = $start;
		$this->end = $end;
	}

	/**
	 * Check if two dates are in range.
	 * @param DateTime $time - The date time to check.
	 * @return bool
	 */
	public function IsInRange(DateTime $time)
	{
		return $time->getTimestamp() >= $this->start->getTimestamp()
			&& $time->getTimestamp() <= $this->end->getTimestamp();
	}

	/**
	 * Check if two dates are in range starting from today.
	 * @param DateTime $time - The date time to check.
	 * @return bool
	 */
	public function IsInRangeToday(DateTime $time)
	{
		$newTime = DateTime::createFromFormat("H-i", $time->format("H-i"));
		$start = DateTime::createFromFormat("H-i", $this->start->format("H-i"));
		$end = DateTime::createFromFormat("H-i", $this->end->format("H-i"));

		return $newTime->getTimestamp() >= $start->getTimestamp()
			&& $newTime->getTimestamp() <= $end->getTimestamp();
	}
}
