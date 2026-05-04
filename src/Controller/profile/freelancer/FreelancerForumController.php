<?php

namespace App\Controller\profile\freelancer;

use App\Entity\users\freelancer\ForumPost;
use App\Entity\users\freelancer\ForumComment;
use App\Entity\users\freelancer\ForumReaction;
use App\Entity\notification\Notification;
use App\Repository\notification\NotificationRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\users\freelancer\ForumPostRepository;
use App\Repository\users\freelancer\ForumCommentRepository;
use App\Repository\users\freelancer\ForumReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/freelancer/forum')]
class FreelancerForumController extends AbstractController
{
    private const BAD_WORDS = [
        'fuck', 'shit', 'ass', 'bitch', 'damn', 'crap', 'dick', 'bastard',
        'idiot', 'stupid', 'dumb', 'hate', 'kill', 'merde', 'putain', 'connard',
        'salaud', 'enculé', 'nique', 'pute',
    ];

    private function isContentClean(string $text): bool
    {
        $lower = strtolower($text);
        foreach (self::BAD_WORDS as $word) {
            if (str_contains($lower, $word)) return false;
        }
        return true;
    }

    private function getFirstBadWord(string $text): ?string
    {
        $lower = strtolower($text);
        foreach (self::BAD_WORDS as $word) {
            if (str_contains($lower, $word)) return $word;
        }
        return null;
    }

    private function getOfflineAssistantReply(string $message): string
    {
        $text = strtolower(trim($message));

        if (str_contains($text, 'client') || str_contains($text, 'meeting') || str_contains($text, 'call')) {
            return 'Try this structure with clients: 1) recap the need, 2) propose deliverables, 3) confirm timeline, 4) ask for final approval in writing.';
        }

        if (str_contains($text, 'post') || str_contains($text, 'forum') || str_contains($text, 'content')) {
            return 'For a strong forum post: clear title, one concrete problem, context in 2-3 lines, and a specific question at the end.';
        }

        if (str_contains($text, 'price') || str_contains($text, 'rate') || str_contains($text, 'budget')) {
            return 'Set your rate by scope and value, not only hours. Share 2 options: a base package and a premium package with faster delivery.';
        }

        if (str_contains($text, 'message') || str_contains($text, 'reply') || str_contains($text, 'notification')) {
            return 'Quick communication rule: acknowledge in under 6 hours, give a short status update, and always end with the next action and deadline.';
        }

        if (str_contains($text, 'portfolio') || str_contains($text, 'project')) {
            return 'Improve your portfolio by showing outcome metrics for each project: problem, your solution, stack used, and measurable result.';
        }

        return 'I am in fallback mode now. Ask me about client communication, pricing, project planning, forum posts, or portfolio optimization.';
    }

    /** @param array<string, mixed> $data */
    private function extractApiErrorMessage(array $data): string
    {
        $candidates = [
            $data['error']['message'] ?? null,
            $data['error'] ?? null,
            $data['message'] ?? null,
            $data['detail'] ?? null,
            $data['code'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    // ── FORUM PAGE ────────────────────────────────────────────────────

    #[Route('', name: 'freelancer_forum', methods: ['GET'])]
    public function index(
        Request                 $request,
        FreelancerRepository    $freelancerRepo,
        ForumPostRepository     $postRepo,
        ForumCommentRepository  $commentRepo,
        ForumReactionRepository $reactionRepo,
        NotificationRepository  $notifRepo,
        PaginatorInterface      $paginator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        /** @var \App\Entity\users\freelancer\Freelancer|null $freelancer */
        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $category = $request->query->get('category', 'All');
        $qb = ($category === 'All')
            ? $postRepo->queryAllOrdered()
            : $postRepo->queryByCategory($category);

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        $postsData = [];
        foreach ($pagination as $post) {
            $postId = $post->getPostId();
            $fId    = $post->getFreelancerId();

            $author     = $fId ? $freelancerRepo->find($fId) : null;
            $authorName = $author ? $author->getName() : 'Unknown';

            $comments = $commentRepo->findByPostId($postId);
            $enrichedComments = [];
            foreach ($comments as $comment) {
                $cfId    = $comment->getFreelancerId();
                $cAuthor = $cfId ? $freelancerRepo->find($cfId) : null;
                $enrichedComments[] = [
                    'id'         => $comment->getCommentId(),
                    'comment'    => $comment,
                    'authorName' => $cAuthor ? $cAuthor->getName() : 'Unknown',
                    'authorId'   => $cfId,
                ];
            }

            $likeCount    = $reactionRepo->countByPostAndType($postId, 'LIKE');
            $dislikeCount = $reactionRepo->countByPostAndType($postId, 'DISLIKE');
            $userLiked    = $reactionRepo->hasUserReacted($freelancer->getIdFreelancer(), $postId, 'LIKE');
            $userDisliked = $reactionRepo->hasUserReacted($freelancer->getIdFreelancer(), $postId, 'DISLIKE');

            $postsData[] = [
                'post'         => $post,
                'authorName'   => $authorName,
                'comments'     => $enrichedComments,
                'commentCount' => count($comments),
                'likeCount'    => $likeCount,
                'dislikeCount' => $dislikeCount,
                'userLiked'    => $userLiked,
                'userDisliked' => $userDisliked,
            ];
        }

        return $this->render('frontOffice/freelancer/profile/forum.html.twig', [
            'freelancer'      => $freelancer,
            'user'            => $freelancer->getUser(),
            'postsData'       => $postsData,
            'pagination'      => $pagination,
            'currentCategory' => $category,
            'categories'      => ['All', 'Technology', 'Design', 'Business', 'Career', 'General'],
            'unreadNotifs'    => $notifRepo->countUnread($userId),
        ]);
    }

    // ── CREATE POST ───────────────────────────────────────────────────

    #[Route('/post/create', name: 'freelancer_forum_create_post', methods: ['POST'])]
    public function createPost(
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $post = new ForumPost();
        $post->setTitle(trim($request->request->get('title', '')));
        $post->setContent(trim($request->request->get('content', '')));
        $post->setFreelancerId($freelancer->getIdFreelancer());
        $post->setCategory($request->request->get('category', 'General'));
        $gifUrl = trim($request->request->get('gif_url', '')) ?: null;
        $post->setGifUrl($gifUrl);

        // Symfony Validator (Controle Saisie)
        $errors = $validator->validate($post);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('freelancer_forum');
        }

        // Additional custom logic (Bad Words)
        if (!$this->isContentClean($post->getTitle()) || !$this->isContentClean($post->getContent())) {
            $bad = $this->getFirstBadWord($post->getTitle()) ?? $this->getFirstBadWord($post->getContent());
            $this->addFlash('error', 'Inappropriate word detected ("' . $bad . '").');
            return $this->redirectToRoute('freelancer_forum');
        }

        $em->persist($post);
        $em->flush();

        $this->addFlash('success', 'Post created successfully!');
        return $this->redirectToRoute('freelancer_forum');
    }

    // ── EDIT POST ─────────────────────────────────────────────────────

    #[Route('/post/{id}/edit', name: 'freelancer_forum_edit_post', methods: ['POST'])]
    public function editPost(
        int                    $id,
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        ForumPostRepository    $postRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $post = $postRepo->find($id);

        if (!$post || !$freelancer || $post->getFreelancerId() !== $freelancer->getIdFreelancer()) {
            $this->addFlash('error', 'Unauthorized edit.');
            return $this->redirectToRoute('freelancer_forum');
        }

        $title   = trim($request->request->get('title', ''));
        $content = trim($request->request->get('content', ''));

        if (!$title || !$content || !$this->isContentClean($title) || !$this->isContentClean($content)) {
            $this->addFlash('error', 'Invalid content or restricted words.');
            return $this->redirectToRoute('freelancer_forum');
        }

        $post->setTitle($title);
        $post->setContent($content);
        $post->setUpdatedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Post updated!');
        return $this->redirectToRoute('freelancer_forum');
    }

    // ── DELETE POST ───────────────────────────────────────────────────

    #[Route('/post/{id}/delete', name: 'freelancer_forum_delete_post', methods: ['POST'])]
    public function deletePost(
        int                    $id,
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        ForumPostRepository    $postRepo,
        ForumCommentRepository $commentRepo,
        ForumReactionRepository $reactionRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $post = $postRepo->find($id);

        if (!$post || !$freelancer || $post->getFreelancerId() !== $freelancer->getIdFreelancer()) {
            $this->addFlash('error', 'Unauthorized deletion.');
            return $this->redirectToRoute('freelancer_forum');
        }

        $comments = $commentRepo->findBy(['postId' => $id]);
        foreach ($comments as $c) $em->remove($c);

        $reactions = $reactionRepo->findBy(['postId' => $id]);
        foreach ($reactions as $r) $em->remove($r);

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post deleted.');
        return $this->redirectToRoute('freelancer_forum');
    }

    // ── REACTION ──────────────────────────────────────────────────────

    #[Route('/post/{id}/react/{type}', name: 'freelancer_forum_react', methods: ['POST'])]
    public function toggleReaction(
        int                     $id,
        string                  $type,
        Request                 $request,
        FreelancerRepository    $freelancerRepo,
        ForumPostRepository     $postRepo,
        ForumReactionRepository $reactionRepo,
        EntityManagerInterface  $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $type = strtoupper($type);
        if (!in_array($type, ['LIKE', 'DISLIKE'])) return $this->redirectToRoute('freelancer_forum');

        $isNew    = false;
        $existing = $reactionRepo->findByUserAndPost($freelancer->getIdFreelancer(), $id);
        if ($existing) {
            if ($existing->getReactionType() === $type) { $em->remove($existing); }
            else { $existing->setReactionType($type); $isNew = true; }
        } else {
            $reaction = new ForumReaction();
            $reaction->setFreelancerId($freelancer->getIdFreelancer());
            $reaction->setPostId($id);
            $reaction->setReactionType($type);
            $em->persist($reaction);
            $isNew = true;
        }
        $em->flush();

        // Notify post author (not self)
        if ($isNew) {
            $post = $postRepo->find($id);
            if ($post && $post->getFreelancerId() !== $freelancer->getIdFreelancer()) {
                $author = $freelancerRepo->find($post->getFreelancerId());
                if ($author) {
                    $emoji  = $type === 'LIKE' ? '👍' : '👎';
                    $notif  = new Notification();
                    $notif->setUserId($author->getUser()->getIdUser());
                    $notif->setType($type);
                    $notif->setContent($freelancer->getName() . ' ' . $emoji . ' your post "' . mb_substr($post->getTitle(), 0, 40) . '".');
                    $notif->setLink('/freelancer/forum#post-' . $id);
                    $em->persist($notif);
                    $em->flush();
                }
            }
        }

        return $this->redirectToRoute('freelancer_forum');
    }

    // ── COMMENT ───────────────────────────────────────────────────────

    #[Route('/post/{id}/comment', name: 'freelancer_forum_add_comment', methods: ['POST'])]
    public function addComment(
        int                    $id,
        Request                $request,
        FreelancerRepository   $freelancerRepo,
        ForumPostRepository    $postRepo,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $comment = new ForumComment();
        $comment->setPostId($id);
        $comment->setCommentText(trim($request->request->get('content', '')));
        $comment->setFreelancerId($freelancer->getIdFreelancer());
        $commentGifUrl = trim($request->request->get('gif_url', '')) ?: null;
        $comment->setGifUrl($commentGifUrl);

        // Symfony Validator (Controle Saisie)
        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('freelancer_forum');
        }

        if (!$this->isContentClean($comment->getCommentText())) {
            $this->addFlash('error', 'Inappropriate word detected in comment.');
            return $this->redirectToRoute('freelancer_forum');
        }

        $post = $postRepo->find($id);
        if ($post) $post->incrementViews();

        $em->persist($comment);
        $em->flush();

        // Notify post author (not self)
        if ($post && $post->getFreelancerId() !== $freelancer->getIdFreelancer()) {
            $author = $freelancerRepo->find($post->getFreelancerId());
            if ($author) {
                $notif = new Notification();
                $notif->setUserId($author->getUser()->getIdUser());
                $notif->setType('COMMENT');
                $notif->setContent($freelancer->getName() . ' 💬 commented on your post "' . mb_substr($post->getTitle(), 0, 40) . '".');
                $notif->setLink('/freelancer/forum#post-' . $id);
                $em->persist($notif);
                $em->flush();
            }
        }

        $this->addFlash('success', 'Comment added!');
        return $this->redirectToRoute('freelancer_forum');
    }

    #[Route('/comment/{id}/edit', name: 'freelancer_forum_edit_comment', methods: ['POST'])]
    public function editComment(
        int                      $id,
        Request                  $request,
        FreelancerRepository     $freelancerRepo,
        ForumCommentRepository   $commentRepo,
        EntityManagerInterface   $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $comment = $commentRepo->find($id);

        if (!$comment || !$freelancer || $comment->getFreelancerId() !== $freelancer->getIdFreelancer()) {
            $this->addFlash('error', 'Unauthorized edit.');
            return $this->redirectToRoute('freelancer_forum');
        }

        $text = trim($request->request->get('content', ''));
        if ($text && $this->isContentClean($text)) {
            $comment->setCommentText($text);
            $em->flush();
            $this->addFlash('success', 'Comment updated!');
        }

        return $this->redirectToRoute('freelancer_forum');
    }

    #[Route('/comment/{id}/delete', name: 'freelancer_forum_delete_comment', methods: ['POST'])]
    public function deleteComment(
        int                      $id,
        Request                  $request,
        FreelancerRepository     $freelancerRepo,
        ForumCommentRepository   $commentRepo,
        EntityManagerInterface   $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $comment = $commentRepo->find($id);

        if ($comment && $freelancer && $comment->getFreelancerId() === $freelancer->getIdFreelancer()) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('freelancer_forum');
    }

    // ── PERSPECTIVE TOXICITY CHECK ────────────────────────────────────

    #[Route('/check-toxicity', name: 'freelancer_forum_check_toxicity', methods: ['POST'])]
    public function checkToxicity(
        Request             $request,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['ok' => true]);

        $apiKey = $_ENV['PERSPECTIVE_API_KEY'] ?? $_SERVER['PERSPECTIVE_API_KEY'] ?? null;
        if (!$apiKey) return $this->json(['ok' => true]);

        $payload = json_decode($request->getContent(), true);
        $text = trim((string) ($payload['text'] ?? ''));
        if ($text === '') return $this->json(['ok' => true]);

        try {
            $response = $httpClient->request('POST',
                'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=' . urlencode($apiKey),
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => [
                        'comment' => ['text' => $text],
                        'requestedAttributes' => ['TOXICITY' => new \stdClass()],
                        'languages' => ['en', 'fr'],
                    ],
                ]
            );
            $data = $response->toArray(false);
            $score = (float) ($data['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0);
            return $this->json(['ok' => $score < 0.7, 'score' => round($score * 100)]);
        } catch (\Throwable) {
            return $this->json(['ok' => true]);
        }
    }

    // ── GIPHY SEARCH PROXY ────────────────────────────────────────────

    #[Route('/giphy-search', name: 'freelancer_forum_giphy_search', methods: ['GET'])]
    public function giphySearch(
        Request             $request,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['error' => 'Unauthorized'], 401);

        $apiKey = $_ENV['GIPHY_API_KEY'] ?? $_SERVER['GIPHY_API_KEY'] ?? null;
        if (!$apiKey) return $this->json(['data' => [], 'error' => 'GIPHY_API_KEY not configured']);

        $query = trim($request->query->get('q', ''));
        if ($query === '') return $this->json(['data' => []]);

        try {
            $response = $httpClient->request('GET', 'https://api.giphy.com/v1/gifs/search', [
                'query' => [
                    'api_key' => $apiKey,
                    'q'       => $query,
                    'limit'   => 12,
                    'rating'  => 'pg',
                    'lang'    => 'en',
                ],
            ]);
            $raw = $response->toArray(false);
            $gifs = array_map(fn($g) => [
                'id'       => $g['id'] ?? '',
                'preview'  => $g['images']['fixed_height_small']['url'] ?? ($g['images']['preview_gif']['url'] ?? ''),
                'original' => $g['images']['fixed_height']['url'] ?? ($g['images']['original']['url'] ?? ''),
                'title'    => $g['title'] ?? '',
            ], $raw['data'] ?? []);
            return $this->json(['data' => $gifs]);
        } catch (\Throwable) {
            return $this->json(['data' => []]);
        }
    }

    #[Route('/chatbot/reply', name: 'freelancer_forum_chatbot_reply', methods: ['POST'])]
    public function chatbotReply(
        Request $request,
        FreelancerRepository $freelancerRepo,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->json(['error' => 'Message is required'], Response::HTTP_BAD_REQUEST);
        }

        $xaiApiKey = $_ENV['XAI_API_KEY'] ?? $_SERVER['XAI_API_KEY'] ?? null;
        $openAiApiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? null;
        $geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? null;

        $providerName = null;
        $apiKey = null;
        $baseUrl = null;
        $model = null;

        if ($geminiApiKey) {
            $providerName = 'Gemini';
            $apiKey = $geminiApiKey;
            $baseUrl = rtrim((string) ($_ENV['GEMINI_BASE_URL'] ?? $_SERVER['GEMINI_BASE_URL'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
            $model = (string) ($_ENV['GEMINI_MODEL'] ?? $_SERVER['GEMINI_MODEL'] ?? 'gemini-flash-latest');
        } elseif ($xaiApiKey) {
            $providerName = 'xAI';
            $apiKey = $xaiApiKey;
            $baseUrl = rtrim((string) ($_ENV['XAI_BASE_URL'] ?? $_SERVER['XAI_BASE_URL'] ?? 'https://api.x.ai/v1'), '/');
            $model = (string) ($_ENV['XAI_MODEL'] ?? $_SERVER['XAI_MODEL'] ?? 'grok-2-latest');
        } elseif ($openAiApiKey) {
            $providerName = 'OpenAI';
            $apiKey = $openAiApiKey;
            $baseUrl = rtrim((string) ($_ENV['OPENAI_BASE_URL'] ?? $_SERVER['OPENAI_BASE_URL'] ?? 'https://api.openai.com/v1'), '/');
            $model = (string) ($_ENV['OPENAI_MODEL'] ?? $_SERVER['OPENAI_MODEL'] ?? 'gpt-4o-mini');
        }

        if (!$apiKey || !$baseUrl || !$model) {
            return $this->json([
                'reply' => 'Chatbot is not configured yet. Add GEMINI_API_KEY, XAI_API_KEY, or OPENAI_API_KEY in your .env.local file and reload the page.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $skills        = $freelancer->getSkillsArray() ? implode(', ', $freelancer->getSkillsArray()) : 'not specified';
        $rate          = $freelancer->getPricePerHour() ? '$' . number_format($freelancer->getPricePerHour(), 2) . '/hr' : 'not set';
        $verified      = $freelancer->getVerificationStatus() === 'verified' ? 'verified ✅' : $freelancer->getVerificationStatus();

        $systemPrompt = <<<PROMPT
You are UniAssist, the intelligent virtual assistant of UniEarn — a freelance marketplace platform connecting skilled freelancers with clients.

## YOUR IDENTITY
- Name: UniAssist
- Personality: professional, warm, proactive, and encouraging
- Tone: concise, helpful, and human — never robotic or overly formal
- Language: respond in the same language the user writes in (English or French)

## PLATFORM KNOWLEDGE — UniEarn Features
You have deep knowledge of every feature on the platform:

### Dashboard
- Overview of the freelancer's profile stats (rating, earnings, tasks)
- Quick access to all platform sections

### Community Forum (/freelancer/forum)
- Freelancers can CREATE posts (title: letters/spaces only, content: min 10 chars)
- 5 categories: Technology, Design, Business, Career, General
- LIKE 👍 / DISLIKE 👎 reactions on posts
- Comments on posts (min 2 chars)
- GIF support via GIPHY: click "🎞 Add GIF" in post or comment form
- Toxicity guard: the platform auto-blocks inappropriate language using AI scoring
- Spam guard: repeated characters (aaaaaaa) are auto-blocked
- AI Chatbot (you!) is available on every forum page
- Filter posts by category using the filter bar
- Pagination: 10 posts per page, navigate with Prev/Next buttons
- Authors see a sentiment bar on their posts: 🟢 green (well received), 🟡 orange (mixed), 🔴 red (poorly received)

### Messages (/freelancer/messages)
- Direct 1-on-1 messaging between freelancers
- Search for freelancers by name using the search bar at the top of the left panel
- Green dot 🟢 on avatar = freelancer is currently online (active in last 5 min)
- Messages are real-time (auto-refresh every 2 seconds — no need to reload)
- Send a message: type in the input box and press Enter or click ➤
- Unread messages show a purple badge on the conversation
- Message notifications appear in the 🔔 bell icon

### Notifications (🔔 bell in top bar)
- Real-time notification bell on Forum and Messages pages
- You get notified when: someone 👍 likes your post, 👎 dislikes your post, 💬 comments on your post, or ✉️ sends you a message
- Click the bell to see all recent notifications
- Click "Mark all read" to clear the badge
- The Messages link in the sidebar shows a red dot when you have unread message notifications

### Portfolio (/freelancer/portfolio)
- Showcase your work with a portfolio title and description
- Add portfolio items: project title, description, technologies used, live URL, GitHub URL
- Upload a project image for each item
- Clients browse portfolios to evaluate freelancers

### Profile (/freelancer/profile/edit)
- Edit your name, email, hourly rate, skills, and bio
- Upload a new profile picture
- Change your password (requires current password)
- Deactivate your account (type "DEACTIVATE" to confirm)

### Available Projects
- Browse client-posted projects matching your skills (coming soon / in development)

### My Contracts
- View signed contracts with clients (in development)

### Task Board
- Manage assigned tasks for ongoing projects (in development)

### Payments (/freelancer/payments)
- View your earnings and payment history (in development)

## CURRENT USER CONTEXT
- Name: {$freelancer->getName()}
- Skills: {$skills}
- Hourly rate: {$rate}
- Verification status: {$verified}
- Bio: {$freelancer->getBio()}

## YOUR BEHAVIOR RULES
1. ALWAYS give actionable, step-by-step guidance when a user asks "how to" do something
2. Reference exact UI elements: button names, menu items, page paths — be specific
3. If a feature is still in development, say so clearly and suggest an alternative
4. Keep responses SHORT (3-6 lines max) unless a step-by-step guide is needed
5. Use emojis sparingly to highlight key points — do not overuse them
6. Never make up features that don't exist on the platform
7. If the user seems frustrated, acknowledge it first before answering
8. Proactively suggest related features the user might find useful
9. REFUSE politely if asked for: harmful content, hacking, spam generation, or anything unrelated to the platform
10. If unsure about something, say "I'm not certain, but here's what I know..." — never guess

## EXAMPLE INTERACTIONS
User: "how do i send a message to another freelancer?"
You: "Go to Messages in the sidebar (/freelancer/messages). You'll see all freelancers listed on the left. Search by name if needed, then click on a freelancer to open the chat. Type your message and press Enter to send. 🟢 Green dots show who's currently online."

User: "my post got blocked"
You: "Your post may have been blocked by the toxicity or spam filter. Make sure your title uses letters and spaces only, your content is at least 10 characters, and there's no inappropriate language or repeated characters (like 'aaaaaaa'). Try rewording and resubmitting."

User: "how do i add a gif to my post?"
You: "When writing a post, click '🎞 Add GIF' below the content field. A search modal will open — type a keyword (like 'celebrate') and press Search. Click any GIF to attach it. It'll preview in your form before you publish."
PROMPT;


        try {
            if ($providerName === 'Gemini') {
                $apiResponse = $httpClient->request('POST', $baseUrl . '/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $systemPrompt . "\n\nUser message:\n" . $message,
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.4,
                            'maxOutputTokens' => 500,
                        ],
                    ],
                ]);
            } else {
                $apiResponse = $httpClient->request('POST', $baseUrl . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'temperature' => 0.4,
                        'max_tokens' => 500,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $message],
                        ],
                    ],
                ]);
            }

            $statusCode = $apiResponse->getStatusCode();
            $data = $apiResponse->toArray(false);

            if ($statusCode >= 400) {
                $apiError = $this->extractApiErrorMessage($data);

                if ($statusCode === 429) {
                    $fallback = $this->getOfflineAssistantReply($message);
                    return $this->json([
                        'reply' => $apiError !== ''
                            ? $providerName . ' limit reached: ' . $apiError . "\n\nFallback advice: " . $fallback
                            : $providerName . ' limit reached. Check your billing/quota and try again.' . "\n\nFallback advice: " . $fallback,
                    ], Response::HTTP_TOO_MANY_REQUESTS);
                }

                if ($statusCode === 401) {
                    return $this->json([
                        'reply' => 'Invalid ' . $providerName . ' API key. Update your key in .env.local and restart the server.',
                    ], Response::HTTP_UNAUTHORIZED);
                }

                return $this->json([
                    'reply' => $apiError !== ''
                        ? $providerName . ' API error: ' . $apiError
                        : $providerName . ' API returned an error. Please try again later.',
                ], Response::HTTP_BAD_GATEWAY);
            }

            if ($providerName === 'Gemini') {
                $parts = $data['candidates'][0]['content']['parts'] ?? [];
                $replyText = '';
                if (is_array($parts)) {
                    foreach ($parts as $part) {
                        if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                            $replyText .= $part['text'];
                        }
                    }
                }
                $reply = trim($replyText);
            } else {
                $reply = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
            }

            if ($reply === '') {
                $reply = 'I could not generate a response right now. Please try again.';
            }

            return $this->json(['reply' => $reply]);
        } catch (\Throwable) {
            return $this->json([
                'reply' => 'Assistant is temporarily unavailable. Fallback advice: ' . $this->getOfflineAssistantReply($message),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
