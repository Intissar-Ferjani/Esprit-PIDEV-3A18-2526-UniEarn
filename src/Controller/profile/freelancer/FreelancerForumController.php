<?php

namespace App\Controller\profile\freelancer;

use App\Entity\users\freelancer\ForumPost;
use App\Entity\users\freelancer\ForumComment;
use App\Entity\users\freelancer\ForumReaction;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\freelancer\ForumPostRepository;
use App\Repository\users\freelancer\ForumCommentRepository;
use App\Repository\users\freelancer\ForumReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    // ── FORUM PAGE ────────────────────────────────────────────────────

    #[Route('', name: 'freelancer_forum', methods: ['GET'])]
    public function index(
        Request                 $request,
        FreelancerRepository    $freelancerRepo,
        ForumPostRepository     $postRepo,
        ForumCommentRepository  $commentRepo,
        ForumReactionRepository $reactionRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        /** @var \App\Entity\users\freelancer\Freelancer|null $freelancer */
        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $category = $request->query->get('category', 'All');
        $posts = ($category === 'All')
            ? $postRepo->findAllOrderedByDate()
            : $postRepo->findByCategory($category);

        $postsData = [];
        foreach ($posts as $post) {
            $postId = $post->getPostId();
            $fId = $post->getFreelancerId();
            
            $author = $fId ? $freelancerRepo->find($fId) : null;
            $authorName = $author ? $author->getName() : 'Unknown';

            $comments = $commentRepo->findByPostId($postId);
            $enrichedComments = [];
            foreach ($comments as $comment) {
                $cfId = $comment->getFreelancerId();
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
            'currentCategory' => $category,
            'categories'      => ['All', 'Technology', 'Design', 'Business', 'Career', 'General'],
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
        ForumReactionRepository $reactionRepo,
        EntityManagerInterface  $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $type = strtoupper($type);
        if (!in_array($type, ['LIKE', 'DISLIKE'])) return $this->redirectToRoute('freelancer_forum');

        $existing = $reactionRepo->findByUserAndPost($freelancer->getIdFreelancer(), $id);
        if ($existing) {
            if ($existing->getReactionType() === $type) $em->remove($existing);
            else $existing->setReactionType($type);
        } else {
            $reaction = new ForumReaction();
            $reaction->setFreelancerId($freelancer->getIdFreelancer());
            $reaction->setPostId($id);
            $reaction->setReactionType($type);
            $em->persist($reaction);
        }

        $em->flush();
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
}
