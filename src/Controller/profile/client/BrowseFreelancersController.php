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
        ClientRepository     $clientRepo,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        // Filters from query string
        $search       = trim($request->query->get('search', ''));
        $verification = $request->query->get('verification', 'All');
        $minRating    = $request->query->get('rating', 'Any');
        $sortBy       = $request->query->get('sortBy', 'rating_high');
        $limit        = $request->query->getInt('limit', 5);

        // Use Repository to get QueryBuilder
        $queryBuilder = $freelancerRepo->getSearchQueryBuilder($search, $verification, $minRating, $sortBy);

        // Paginate
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        $template = $request->query->get('ajax') 
            ? 'frontOffice/client/profile/_freelancer_results.html.twig'
            : 'frontOffice/client/profile/browse-freelancers.html.twig';

        return $this->render($template, [
            'pagination'   => $pagination,
            'client'       => $client,
            'user'         => $client->getUser(),
            'search'       => $search,
            'verification' => $verification,
            'minRating'    => $minRating,
            'sortBy'       => $sortBy,
            'limit'        => $limit,
            'count'        => $pagination->getTotalItemCount(),
        ]);
    }
}