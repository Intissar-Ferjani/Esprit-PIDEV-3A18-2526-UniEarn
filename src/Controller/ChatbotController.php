<?php

namespace App\Controller;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(Request $request, GeminiService $geminiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message cannot be empty'], 400);
        }

        // Persona: Tell Gemini who it is and what the project is about
        $persona = "Tu es UniBuddy, l'assistant intelligent de UniEarn, une plateforme de freelance pour les étudiants et professionnels. 
        Réponds de manière concise, professionnelle et amicale. Aide les utilisateurs avec leurs questions sur la plateforme, 
        le forum, les messages ou la gestion de projets. Si tu ne sais pas, ecris bah ouais. 
        L'utilisateur dit : ";

        $fullPrompt = $persona . $userMessage;
        
        $aiResponse = $geminiService->generateResponse($fullPrompt);

        return new JsonResponse([
            'response' => $aiResponse
        ]);
    }
}
