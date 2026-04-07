<?php

namespace App\Controller\profile\client;

use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\client\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client')]
class BrowseFreelancersController extends AbstractController
{
    #[Route('/freelancers', name: 'client_browse_freelancers', methods: ['GET'])]
    public function browse(
        Request              $request,
        FreelancerRepository $freelancerRepo,
        ClientRepository     $clientRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        // Filters from query string
        $search       = trim($request->query->get('search', ''));
        $verification = $request->query->get('verification', 'All');
        $minRating    = $request->query->get('rating', 'Any');
        $sort         = $request->query->get('sort', 'rating_high');

        // Load all freelancers
        $freelancers = $freelancerRepo->findAllWithUser();

        // Search — name, bio, skills
        if ($search) {
            $s = strtolower($search);
            $freelancers = array_filter($freelancers, function($f) use ($s) {
                if (str_contains(strtolower($f->getName() ?? ''), $s)) return true;
                if (str_contains(strtolower($f->getBio()  ?? ''), $s)) return true;
                foreach ($f->getSkillsArray() as $skill) {
                    if (str_contains(strtolower($skill), $s)) return true;
                }
                return false;
            });
        }

        // Verification filter
        if ($verification !== 'All') {
            $target = strtolower($verification); // 'verified' or 'unverified'
            $freelancers = array_filter($freelancers, fn($f) => $f->getVerificationStatus() === $target);
        }

        // Rating filter
        if ($minRating !== 'Any') {
            $min = (float) str_replace('+', '', $minRating);
            $freelancers = array_filter($freelancers, fn($f) => $f->getRating() >= $min);
        }

        // Sort
        $freelancers = array_values($freelancers);
        usort($freelancers, match ($sort) {
            'rating_low'  => fn($a, $b) => $a->getRating()       <=> $b->getRating(),
            'price_low'   => fn($a, $b) => $a->getPricePerHour() <=> $b->getPricePerHour(),
            'price_high'  => fn($a, $b) => $b->getPricePerHour() <=> $a->getPricePerHour(),
            'name_asc'    => fn($a, $b) => strcmp($a->getName(), $b->getName()),
            default       => fn($a, $b) => $b->getRating()       <=> $a->getRating(), 
        });

        return $this->render('frontOffice/client/profile/browse-freelancers.html.twig', [
            'freelancers'  => $freelancers,
            'client'       => $client,
            'user'         => $client->getUser(),
            'search'       => $search,
            'verification' => $verification,
            'minRating'    => $minRating,
            'sort'         => $sort,
            'count'        => count($freelancers),
        ]);
    }
}