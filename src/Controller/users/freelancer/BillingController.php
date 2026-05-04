<?php

namespace App\Controller\users\freelancer;

use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/freelancer/billing')]
class BillingController extends AbstractController
{
    private function requireFreelancer(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'FREELANCER') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    #[Route('/', name: 'freelancer_billing_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, FreelancerRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $repo->findByUserId($userId);

        if (!$freelancer) {
            throw $this->createNotFoundException('Freelancer not found.');
        }

        if ($request->isMethod('POST')) {
            $iban = $request->request->get('iban');
            $swiftCode = $request->request->get('swift_code');

            // Basic validation minimum size for IBAN
            if ($iban && strlen($iban) < 15) {
                $this->addFlash('error', 'L\'IBAN semble invalide (trop court).');
            } else {
                $freelancer->setIban(str_replace(' ', '', strtoupper((string)$iban)));
                $freelancer->setSwiftCode(str_replace(' ', '', strtoupper((string)$swiftCode)));
                $em->flush();

                $this->addFlash('success', '✅ Vos coordonnées bancaires ont été sauvegardées. Elles documenteront le système Escrow pour vos futurs paiements. 💰');
                return $this->redirectToRoute('freelancer_billing_settings');
            }
        }

        return $this->render('frontOffice/freelancer/billing/index.html.twig', [
            'freelancer' => $freelancer,
        ]);
    }
}
