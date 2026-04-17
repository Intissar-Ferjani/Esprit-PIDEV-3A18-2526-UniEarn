<?php

namespace App\Controller\auth\user;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TermsController extends AbstractController
{
    #[Route('/terms', name: 'user_terms')]
    public function index(): Response
    {
        return $this->render('frontOffice/user/auth/terms.html.twig');
    }
}
