<?php

namespace App\Controller\contract\client;

use App\Repository\contract\ContractRepository;
use App\Repository\users\client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/client/contracts')]
class PaymentController extends AbstractController
{
    private function requireClient(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'CLIENT') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── PAYMENTS PAGE ────────────────────────────────────────────────────

    #[Route('/payments', name: 'client_payments_index', methods: ['GET'])]
    public function paymentsIndex(Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        if (!$client) throw $this->createNotFoundException('Client not found.');

        $allPaymentContracts = $repo->findByClientIdAndStatuses(
            $client->getIdClient(),
            ['signed', 'funded', 'released']
        );

        $toPay  = array_filter($allPaymentContracts, fn($c) => $c->getStatus() === 'signed');
        $paid   = array_filter($allPaymentContracts, fn($c) => in_array($c->getStatus(), ['funded', 'released'], true));

        return $this->render('frontOffice/contract/payments.html.twig', [
            'toPay' => $toPay,
            'paid'  => $paid,
        ]);
    }

    #[Route('/{id}/pay', name: 'client_contract_pay', methods: ['POST'])]
    public function pay(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if ($contract->getStatus() !== 'signed') {
            $this->addFlash('error', 'Le contrat doit être signé par les deux parties avant le paiement.');
            return $this->redirectToRoute('client_contract_show', ['id' => $id]);
        }

        $stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? null;

        // VRAIE INTÉGRATION STRIPE (Nécessite: composer require stripe/stripe-php)
        if ($stripeSecret && class_exists('\Stripe\Stripe')) {
            \Stripe\Stripe::setApiKey($stripeSecret);
            $successUrl = $this->generateUrl('client_contract_pay_success', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('client_contract_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

            try {
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => (int)($contract->getAmount() * 100), // Stripe prend en centimes
                            'product_data' => [
                                'name' => 'Séquestre UniEarn: ' . $contract->getTitle(),
                                'description' => 'Fonds sécurisés pour le freelancer ' . $contract->getFreelancer()->getName(),
                            ],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                ]);

                return $this->redirect($session->url);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur Stripe : ' . $e->getMessage());
                return $this->redirectToRoute('client_contract_show', ['id' => $id]);
            }
        }

        // SIMULATION POUR LE PROJET ACADÉMIQUE / DÉMO
        // Si Stripe n'est pas configuré, on simule une redirection vers la page de succès
        return $this->redirectToRoute('client_contract_pay_success', ['id' => $id]);
    }

    #[Route('/{id}/pay/success', name: 'client_contract_pay_success', methods: ['GET'])]
    public function success(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if ($contract->getStatus() === 'signed') {
            $contract->setStatus('funded'); // Le contrat passe en séquestre
            $em->flush();
            $this->addFlash('success', '💳 Paiement réussi ! L\'argent est désormais sécurisé en séquestre par UniEarn.');
        }

        return $this->redirectToRoute('client_contract_show', ['id' => $id]);
    }
}