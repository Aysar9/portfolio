<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RedirectController extends AbstractController
{
    #[Route('/', name: 'app_root_redirect')]
    public function rootRedirect(): Response
    {
        return $this->redirectToRoute('app_home', ['_locale' => 'de']);
    }
}