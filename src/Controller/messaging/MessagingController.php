<?php

namespace App\Controller\messaging;

use App\Entity\messaging\Chat;
use App\Entity\messaging\Message;
use App\Entity\notification\Notification;
use App\Repository\messaging\ChatRepository;
use App\Repository\messaging\MessageRepository;
use App\Repository\notification\NotificationRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/freelancer/messages')]
class MessagingController extends AbstractController
{
    // ── LIST + SEARCH FREELANCERS ─────────────────────────────────────

    #[Route('', name: 'freelancer_messages', methods: ['GET'])]
    public function index(
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        UserRepository         $userRepo,
        ChatRepository         $chatRepo,
        MessageRepository      $msgRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $me = $freelancerRepo->findByUserId($userId);
        if (!$me) return $this->redirectToRoute('user_login');

        // Update last active
        $user = $userRepo->find($userId);
        if ($user) { $user->setLastActiveAt(new \DateTime()); $em->flush(); }

        $search = trim($request->query->get('q', ''));
        $all    = $freelancerRepo->findAll();

        $freelancers = [];
        foreach ($all as $f) {
            if ($f->getIdFreelancer() === $me->getIdFreelancer()) continue;
            if ($search && stripos($f->getName(), $search) === false) continue;
            $chat   = $chatRepo->findBetween($me->getIdFreelancer(), $f->getIdFreelancer());
            $unread = $chat ? $msgRepo->countUnreadForUser($chat->getIdChat(), $userId) : 0;
            $last   = null;
            if ($chat) {
                $msgs = $msgRepo->findByChatOrdered($chat->getIdChat());
                $last = $msgs ? end($msgs) : null;
            }
            $freelancers[] = [
                'freelancer' => $f,
                'user'       => $f->getUser(),
                'online'     => $f->getUser()->isOnline(),
                'unread'     => $unread,
                'lastMsg'    => $last,
                'chatId'     => $chat?->getIdChat(),
            ];
        }

        // Sort: unread first, then by last message date
        /** @var array<int, array{freelancer: \App\Entity\users\freelancer\Freelancer, user: \App\Entity\users\user\User, online: bool, unread: int, lastMsg: Message|null, chatId: int|null}> $freelancers */
        usort($freelancers, function (array $a, array $b): int {
            if ($a['unread'] !== $b['unread']) return $b['unread'] <=> $a['unread'];
            $aTime = $a['lastMsg'] ? $a['lastMsg']->getSentDate()->getTimestamp() : 0;
            $bTime = $b['lastMsg'] ? $b['lastMsg']->getSentDate()->getTimestamp() : 0;
            return $bTime <=> $aTime;
        });

        return $this->render('frontOffice/freelancer/messaging/messages.html.twig', [
            'me'          => $me,
            'user'        => $me->getUser(),
            'freelancers' => $freelancers,
            'search'      => $search,
            'activeChat'  => null,
            'messages'    => [],
            'chatPartner' => null,
        ]);
    }

    // ── OPEN / CREATE CONVERSATION ────────────────────────────────────

    #[Route('/with/{freelancerId}', name: 'freelancer_messages_open', methods: ['GET', 'POST'])]
    public function openChat(
        int                    $freelancerId,
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        UserRepository         $userRepo,
        ChatRepository         $chatRepo,
        MessageRepository      $msgRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $me      = $freelancerRepo->findByUserId($userId);
        $partner = $freelancerRepo->find($freelancerId);

        if (!$me || !$partner || $me->getIdFreelancer() === $freelancerId) {
            return $this->redirectToRoute('freelancer_messages');
        }

        // Update last active
        $user = $userRepo->find($userId);
        if ($user) { $user->setLastActiveAt(new \DateTime()); $em->flush(); }

        // Find or create chat
        $chat = $chatRepo->findBetween($me->getIdFreelancer(), $freelancerId);
        if (!$chat) {
            $chat = new Chat();
            $chat->setFreelancer1Id($me->getIdFreelancer());
            $chat->setFreelancer2Id($freelancerId);
            $em->persist($chat);
            $em->flush();
        }

        // Mark incoming messages as seen
        $msgRepo->markSeenInChat($chat->getIdChat(), $userId);

        // Build sidebar list
        $all         = $freelancerRepo->findAll();
        $freelancers = [];
        foreach ($all as $f) {
            if ($f->getIdFreelancer() === $me->getIdFreelancer()) continue;
            $c      = $chatRepo->findBetween($me->getIdFreelancer(), $f->getIdFreelancer());
            $unread = $c ? $msgRepo->countUnreadForUser($c->getIdChat(), $userId) : 0;
            $msgs   = $c ? $msgRepo->findByChatOrdered($c->getIdChat()) : [];
            $last   = $msgs ? end($msgs) : null;
            $freelancers[] = [
                'freelancer' => $f,
                'user'       => $f->getUser(),
                'online'     => $f->getUser()->isOnline(),
                'unread'     => $unread,
                'lastMsg'    => $last,
                'chatId'     => $c?->getIdChat(),
            ];
        }
        /** @var array<int, array{freelancer: \App\Entity\users\freelancer\Freelancer, user: \App\Entity\users\user\User, online: bool, unread: int, lastMsg: Message|null, chatId: int|null}> $freelancers */
        usort($freelancers, function (array $a, array $b): int {
            if ($a['unread'] !== $b['unread']) return $b['unread'] <=> $a['unread'];
            $aTime = $a['lastMsg'] ? $a['lastMsg']->getSentDate()->getTimestamp() : 0;
            $bTime = $b['lastMsg'] ? $b['lastMsg']->getSentDate()->getTimestamp() : 0;
            return $bTime <=> $aTime;
        });

        return $this->render('frontOffice/freelancer/messaging/messages.html.twig', [
            'me'          => $me,
            'user'        => $me->getUser(),
            'freelancers' => $freelancers,
            'search'      => '',
            'activeChat'  => $chat,
            'messages'    => $msgRepo->findByChatOrdered($chat->getIdChat()),
            'chatPartner' => $partner,
        ]);
    }

    // ── SEND MESSAGE ──────────────────────────────────────────────────

    #[Route('/{chatId}/send', name: 'freelancer_messages_send', methods: ['POST'])]
    public function send(
        int                    $chatId,
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        ChatRepository         $chatRepo,
        MessageRepository      $msgRepo,
        NotificationRepository $notifRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['error' => 'Unauthorized'], 401);

        $me   = $freelancerRepo->findByUserId($userId);
        $chat = $chatRepo->find($chatId);

        if (!$me || !$chat) return $this->json(['error' => 'Not found'], 404);
        if ($chat->getFreelancer1Id() !== $me->getIdFreelancer() &&
            $chat->getFreelancer2Id() !== $me->getIdFreelancer()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $content = trim($request->request->get('content', ''));
        if ($content === '') return $this->json(['error' => 'Empty message'], 400);

        $msg = new Message();
        $msg->setChatId($chatId);
        $msg->setSenderId($userId);
        $msg->setContent(substr($content, 0, 255));
        $em->persist($msg);

        $chat->setLastMessageAt(new \DateTime());
        $em->flush();

        // Notification for the other freelancer
        $otherId = $chat->getOtherFreelancerId($me->getIdFreelancer());
        $other   = $freelancerRepo->find($otherId);
        if ($other) {
            $notif = new Notification();
            $notif->setUserId($other->getUser()->getIdUser());
            $notif->setType('MESSAGE');
            $notif->setContent($me->getName() . ' sent you a message.');
            $notif->setLink('/freelancer/messages/with/' . $me->getIdFreelancer());
            $em->persist($notif);
            $em->flush();
        }

        return $this->json([
            'id'        => $msg->getIdMessage(),
            'content'   => $msg->getContent(),
            'sentDate'  => $msg->getSentDate()->format('H:i'),
            'senderId'  => $msg->getSenderId(),
            'myId'      => $userId,
        ]);
    }

    // ── POLL FOR NEW MESSAGES ─────────────────────────────────────────

    #[Route('/{chatId}/poll', name: 'freelancer_messages_poll', methods: ['GET'])]
    public function poll(
        int               $chatId,
        Request           $request,
        FreelancerRepository $freelancerRepo,
        ChatRepository    $chatRepo,
        MessageRepository $msgRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['error' => 'Unauthorized'], 401);

        $me   = $freelancerRepo->findByUserId($userId);
        $chat = $chatRepo->find($chatId);
        if (!$me || !$chat) return $this->json(['messages' => []]);

        $afterId = (int) $request->query->get('after', 0);
        $newMsgs = $msgRepo->findAfter($chatId, $afterId);

        // Mark incoming as seen
        $msgRepo->markSeenInChat($chatId, $userId);
        $em->flush();

        $data = array_map(fn(Message $m) => [
            'id'       => $m->getIdMessage(),
            'content'  => $m->getContent(),
            'sentDate' => $m->getSentDate()->format('H:i'),
            'senderId' => $m->getSenderId(),
            'myId'     => $userId,
        ], $newMsgs);

        return $this->json(['messages' => $data]);
    }
}
