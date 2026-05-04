<?php

namespace App\Controller\notification;

use App\Repository\notification\NotificationRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/freelancer/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'freelancer_notifications_list', methods: ['GET'])]
    public function list(
        Request                $request,
        NotificationRepository $notifRepo,
        FreelancerRepository   $freelancerRepo
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['notifications' => [], 'unread' => 0]);

        $notifications = $notifRepo->findUnread($userId);
        $data = array_map(fn($n) => [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'content'   => $n->getContent(),
            'link'      => $n->getLink(),
            'createdAt' => $n->getCreatedAt()->format('M d, H:i'),
        ], $notifications);

        return $this->json([
            'notifications' => $data,
            'unread'        => count($data),
        ]);
    }

    #[Route('/mark-read', name: 'freelancer_notifications_mark_read', methods: ['POST'])]
    public function markRead(
        Request                $request,
        NotificationRepository $notifRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['ok' => false]);

        $notifRepo->markAllRead($userId);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
