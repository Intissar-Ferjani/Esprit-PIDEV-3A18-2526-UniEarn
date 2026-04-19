<?php

namespace App\Controller\auth\freelancer;

use App\Service\users\freelancer\SkillsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SkillApiController extends AbstractController
{
    #[Route('/api/skills', name: 'api_skills', methods: ['GET'])]
    public function getSkills(Request $request, SkillsApiService $skillsService): JsonResponse
    {
        $query = $request->query->get('q', '');
        $skills = $skillsService->fetchSkills($query);

        return new JsonResponse($skills);
    }
}
