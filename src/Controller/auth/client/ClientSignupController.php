<?php

namespace App\Controller\auth\client;

use App\Entity\users\client\Client;
use App\Form\users\client\ClientProfileFormType;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/client')]
class ClientSignupController extends AbstractController
{
    /**
     * Step 2 for CLIENT role.
     * The User row already exists (created in AuthController::signup).
     * Here we create the linked Client row and associate it via $client->setUser($user).
     */
    #[Route('/setup', name: 'client_setup', methods: ['GET', 'POST'])]
    public function setup(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        ClientRepository       $clientRepo
    ): Response {
        // Guard: must have a pending user from step 1
        $userId = $request->getSession()->get('pending_user_id');

        if (!$userId) {
            $loggedInId = $request->getSession()->get('user_id');
            if ($loggedInId) return $this->redirectToRoute('user_dashboard');
            return $this->redirectToRoute('user_signup');
        }

        // Load the User object
        $user = $userRepo->find($userId);
        if (!$user || $user->getRole() !== 'CLIENT') {
            return $this->redirectToRoute('user_signup');
        }

        $client = new Client();
        $form   = $this->createForm(ClientProfileFormType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check company uniqueness
            if ($clientRepo->companyExists($client->getCompany())) {
                $this->addFlash('error', 'This company name is already registered. Please use a different name.');
                return $this->render('frontOffice/client/auth/client-signup.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }

            // Link the User object — this is the equivalent of client.setIdUser(userId) in Java
            $client->setUser($user);
            $client->setRating(0.0);
            if ($client->getAmount() === null) {
                $client->setAmount(0.0);
            }

            $em->persist($client);
            $em->flush();

            // Clear pending session and fully log the user in
            $request->getSession()->remove('pending_user_id');
            $request->getSession()->set('user_id',   $user->getIdUser());
            $request->getSession()->set('user_name', $user->getName());
            $request->getSession()->set('user_role', $user->getRole());

            $this->addFlash('success', 'Welcome to UniEarn, ' . $user->getName() . '! Your client profile is ready.');
            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('frontOffice/client/auth/client-signup.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}