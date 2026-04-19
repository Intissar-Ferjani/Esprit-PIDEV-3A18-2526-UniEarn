<?php

namespace App\Controller\candidature;

use App\Entity\candidature\Evaluation;
use App\Form\candidature\EvaluationType;
use App\Repository\candidature\EvaluationRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\EvaluationType as EnumEvaluationType;
use App\Service\SentimentAnalysisService;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'app_evaluation_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EvaluationRepository $evaluationRepository,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        UserRepository $userRepo,
        PaginatorInterface $paginator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);
        $currentUser = $userRepo->find($userId);

        $evaluationsGivenQuery    = $evaluationRepository->findBy(['evaluator' => $currentUser], ['createdAt' => 'DESC']);
        $evaluationsReceivedQuery = $evaluationRepository->findBy(['evaluated' => $currentUser], ['createdAt' => 'DESC']);

        $paginationGiven = $paginator->paginate(
            $evaluationsGivenQuery,
            $request->query->getInt('page_given', 1),
            5,
            ['pageParameterName' => 'page_given']
        );

        $paginationReceived = $paginator->paginate(
            $evaluationsReceivedQuery,
            $request->query->getInt('page_received', 1),
            5,
            ['pageParameterName' => 'page_received']
        );

        $reputationScore = $evaluationRepository->calculateReputation($currentUser);

        return $this->render('candidature/evaluation/index.html.twig', [
            'evaluationsGiven'    => $paginationGiven,
            'evaluationsReceived' => $paginationReceived,
            'reputationScore'     => $reputationScore,
            'freelancer' => $freelancer,
            'client'     => $client,
            'user'       => $currentUser,
            'active'     => 'evaluations',
        ]);
    }

    #[Route('/new', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        UserRepository $userRepo,
        SentimentAnalysisService $sentimentService,
        EvaluationRepository $evaluationRepository
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $user = $userRepo->find($userId);
        if (!$user) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        $evaluation = new Evaluation();
        $evaluation->setEvaluator($user);

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $evaluatedId = $request->query->get('evaluatedId');
        if ($evaluatedId) {
            $form->get('evaluatedId')->setData((int)$evaluatedId);
        }
        $projectIdParam = $request->query->get('projectId');
        if ($projectIdParam) {
            $form->get('projectId')->setData((int)$projectIdParam);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evaluatedIdFromForm = $form->get('evaluatedId')->getData();
            if ($evaluatedIdFromForm) {
                $evaluatedUser = $userRepo->find($evaluatedIdFromForm);
                if ($evaluatedUser) {
                    $evaluation->setEvaluated($evaluatedUser);
                }
            }

            if (!$evaluation->getEvaluated()) {
                $this->addFlash('error', 'The person being evaluated must be a valid User ID.');
                return $this->render('candidature/evaluation/new.html.twig', [
                    'evaluation' => $evaluation,
                    'form' => $form->createView(),
                    'freelancer' => $freelancer,
                    'client' => $client,
                    'user' => $user,
                    'active' => 'evaluations',
                ]);
            }

            if ($client) {
                $evaluation->setType(EnumEvaluationType::CLIENT_TO_FREELANCER);
            } elseif ($freelancer) {
                $evaluation->setType(EnumEvaluationType::FREELANCER_TO_CLIENT);
            } else {
                $evaluation->setType(EnumEvaluationType::USER_TO_USER);
            }

            // API Integration: Sentiment Analysis
            $sentimentResult = $sentimentService->analyze($evaluation->getComment());
            $evaluation->setSentiment($sentimentResult['label']);
            $evaluation->setSentimentScore($sentimentResult['score']);
            
            // API B (API + Metier): Detailed Flagging & Sentiment Handling
            if ($sentimentResult['label'] === 'neg') {
                if ($sentimentResult['score'] > 0.8) {
                    $evaluation->setIsFlagged(true); // Aggressive/Toxic
                } else if ($sentimentResult['score'] > 0.5) {
                    // Mild Negative - Not flagged as toxic but warned
                }
            }

            $entityManager->persist($evaluation);
            $entityManager->flush();

            // Metier Avancé: Synchronization of Freelancer rating
            if ($evaluation->getType() === EnumEvaluationType::CLIENT_TO_FREELANCER) {
                $evaluatedFreelancer = $freelancerRepo->findByUserId($evaluation->getEvaluated()->getIdUser());
                if ($evaluatedFreelancer) {
                    $newReputation = $evaluationRepository->calculateReputation($evaluation->getEvaluated());
                    $evaluatedFreelancer->setRating($newReputation);
                    $entityManager->flush();
                }
            }

            if ($evaluation->isFlagged()) {
                $this->addFlash('warning', 'Review required: Your evaluation contains high-confidence negative content and has been flagged for moderation.');
            } elseif ($evaluation->getSentiment() === 'neg') {
                $this->addFlash('warning', 'Evaluation submitted. Note: A negative tone was detected in your feedback.');
            } else {
                $this->addFlash('success', 'Great! Your evaluation has been published successfully.');
            }
            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidature/evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'freelancer' => $freelancer,
            'client' => $client,
            'user' => $user,
            'active' => 'evaluations',
        ]);
    }

    #[Route('/{id}', name: 'app_evaluation_show', methods: ['GET'])]
    public function show(
        Request $request,
        Evaluation $evaluation,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        return $this->render('candidature/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'freelancer' => $freelancer,
            'client' => $client,
            'user' => $freelancer ? $freelancer->getUser() : ($client ? $client->getUser() : null),
            'active' => 'evaluations',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        UserRepository $userRepo,
        SentimentAnalysisService $sentimentService
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        // Check if current user is the evaluator
        if ($evaluation->getEvaluator() && $evaluation->getEvaluator()->getIdUser() !== $userId) {
            $this->addFlash('error', 'You can only edit your own evaluations.');
            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        $form = $this->createForm(EvaluationType::class, $evaluation);
        if ($evaluation->getEvaluated()) {
            $form->get('evaluatedId')->setData($evaluation->getEvaluated()->getIdUser());
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evaluatedIdFromForm = $form->get('evaluatedId')->getData();
            if ($evaluatedIdFromForm) {
                $evaluatedUser = $userRepo->find($evaluatedIdFromForm);
                if ($evaluatedUser) {
                    $evaluation->setEvaluated($evaluatedUser);
                }
            }

            if (!$evaluation->getEvaluated()) {
                $this->addFlash('error', 'The person being evaluated must be a valid User ID.');
                return $this->render('candidature/evaluation/new.html.twig', [
                    'evaluation' => $evaluation,
                    'form' => $form->createView(),
                    'freelancer' => $freelancer,
                    'client' => $client,
                    'user' => $evaluation->getEvaluator(),
                    'active' => 'evaluations',
                ]);
            }

            // API Integration: Sentiment Analysis
            $sentimentResult = $sentimentService->analyze($evaluation->getComment());
            $evaluation->setSentiment($sentimentResult['label']);
            $evaluation->setSentimentScore($sentimentResult['score']);

            $entityManager->flush();

            $this->addFlash('success', 'Evaluation updated successfully! Detected sentiment: ' . ucfirst($sentimentResult['label']));
            return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidature/evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'freelancer' => $freelancer,
            'client' => $client,
            'user' => $evaluation->getEvaluator(),
            'active' => 'evaluations',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
            $this->addFlash('success', 'Evaluation deleted successfully.');
        }

        return $this->redirectToRoute('app_evaluation_index', [], Response::HTTP_SEE_OTHER);
    }
}
