<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}', requirements: ['_locale' => 'de|en'])]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/ueber-mich', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }


    #[Route('/projekte', name: 'app_projekte')]
    public function projekte(): Response
    {
        return $this->render('home/projekte.html.twig');
    }

    #[Route('/projekte/intranet-portal', name: 'app_projekt_intranet')]
    public function projektIntranet(): Response
    {
        return $this->render('home/projekt_intranet.html.twig');
    }

    #[Route('/kontakt', name: 'app_kontakt')]
    public function kontakt(): Response
    {
        return $this->render('home/kontakt.html.twig');
    }
}
