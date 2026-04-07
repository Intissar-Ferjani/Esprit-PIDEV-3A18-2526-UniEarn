<?php

namespace App\Controller\backoffice;

use App\Entity\users\freelancer\ForumPost;
use App\Repository\users\freelancer\ForumPostRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\freelancer\ForumCommentRepository;
use App\Repository\users\freelancer\ForumReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    #[Route('/', name: 'admin_forum_index', methods: ['GET'])]
    public function index(
        Request              $request,
        ForumPostRepository  $postRepo,
        FreelancerRepository $freelancerRepo
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $query = $request->query->get('q', '');
        $posts = $query ? $postRepo->searchByContent($query) : $postRepo->findAllOrderedByDate();

        $postsData = [];

        foreach ($posts as $post) {
            $author = $post->getFreelancerId() ? $freelancerRepo->find($post->getFreelancerId()) : null;
            $postsData[] = [
                'post'       => $post,
                'authorName' => $author ? $author->getName() : 'Unknown',
            ];
        }

        return $this->render('backoffice/forum/index.html.twig', [
            'postsData' => $postsData,
            'searchQuery' => $query,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_forum_post_delete', methods: ['POST'])]
    public function delete(
        int                    $id,
        ForumPostRepository    $postRepo,
        ForumCommentRepository $commentRepo,
        ForumReactionRepository $reactionRepo,
        EntityManagerInterface $em,
        Request                $request
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $post = $postRepo->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        // Verify CSRF token
        if ($this->isCsrfTokenValid('delete_post_' . $id, $request->request->get('_token'))) {
            // Delete related comments
            $comments = $commentRepo->findBy(['postId' => $id]);
            foreach ($comments as $c) {
                $em->remove($c);
            }

            // Delete related reactions
            $reactions = $reactionRepo->findBy(['postId' => $id]);
            foreach ($reactions as $r) {
                $em->remove($r);
            }

            $em->remove($post);
            $em->flush();

            $this->addFlash('success', 'Post and all related content deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_forum_index');
    }

    #[Route('/{id}', name: 'admin_forum_post_show', methods: ['GET'])]
    public function show(
        int                    $id,
        ForumPostRepository    $postRepo,
        FreelancerRepository   $freelancerRepo,
        ForumCommentRepository $commentRepo,
        Request                $request
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $post = $postRepo->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        $author = $post->getFreelancerId() ? $freelancerRepo->find($post->getFreelancerId()) : null;
        $comments = $commentRepo->findByPostId($id);

        $commentsData = [];
        foreach ($comments as $comment) {
            $cAuthor = $comment->getFreelancerId() ? $freelancerRepo->find($comment->getFreelancerId()) : null;
            $commentsData[] = [
                'comment'    => $comment,
                'authorName' => $cAuthor ? $cAuthor->getName() : 'Unknown',
            ];
        }

        return $this->render('backoffice/forum/show.html.twig', [
            'post'         => $post,
            'authorName'   => $author ? $author->getName() : 'Unknown',
            'commentsData' => $commentsData,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'admin_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(
        int                    $id,
        ForumCommentRepository $commentRepo,
        EntityManagerInterface $em,
        Request                $request
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $comment = $commentRepo->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found.');
        }

        $postId = $comment->getPostId();

        if ($this->isCsrfTokenValid('delete_comment_' . $id, $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $postId]);
    }
}
