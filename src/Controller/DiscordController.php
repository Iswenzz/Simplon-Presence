<?php

namespace App\Controller;

use App\Entity\Presence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
	/**
	 * Index page showing all Presences.
	 * @Route("/", name="index")
	 * @return Response
	 */
	public function index(): Response
	{
		$presences = $this->getDoctrine()->getRepository(Presence::class)->findAll();

		return $this->render("discord/index.html.twig", [
			"presences" => $presences
		]);
	}
}
