<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    /**
     * JSON login is handled by the security firewall (json_login).
     * This endpoint documents the login route for API consumers.
     */
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This endpoint is intercepted by the json_login authenticator.
        // If this code is reached, authentication failed.
        return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Initiate CAS authentication flow.
     * Redirects the user to the CAS server login page.
     */
    #[Route('/cas/login', name: 'api_cas_login', methods: ['GET'])]
    public function casLogin(): RedirectResponse
    {
        $casBaseUrl = $this->getParameter('cas_base_url');
        $serviceUrl = $this->generateUrl('api_cas_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse(sprintf('%s/login?service=%s', $casBaseUrl, urlencode($serviceUrl)));
    }

    /**
     * CAS ticket validation callback.
     * Validates the CAS ticket and returns a JWT token.
     */
    #[Route('/cas/callback', name: 'api_cas_callback', methods: ['GET'])]
    public function casCallback(Request $request, HttpClientInterface $httpClient): RedirectResponse|JsonResponse
    {
        $ticket = $request->query->get('ticket');
        if (!$ticket) {
            return new JsonResponse(['error' => 'Missing CAS ticket.'], Response::HTTP_BAD_REQUEST);
        }

        $casBaseUrl = $this->getParameter('cas_base_url');
        $serviceUrl = $this->generateUrl('api_cas_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        // Validate ticket with CAS server
        try {
            $response = $httpClient->request('GET', sprintf(
                '%s/serviceValidate?service=%s&ticket=%s&format=json',
                $casBaseUrl,
                urlencode($serviceUrl),
                urlencode($ticket),
            ));

            $data = $response->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'CAS ticket validation failed.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $serviceResponse = $data['serviceResponse'] ?? [];
        $authSuccess = $serviceResponse['authenticationSuccess'] ?? null;

        if (!$authSuccess) {
            return new JsonResponse(
                ['error' => 'CAS authentication failed.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $casUid = $authSuccess['user'];
        $attributes = $authSuccess['attributes'] ?? [];

        // Find or create user by CAS ID
        $user = $this->userRepository->findOneBy(['casId' => $casUid]);

        if (!$user) {
            $user = new User();
            $user->setCasId($casUid);
            $user->setEmail($attributes['mail'] ?? $casUid . '@univ-lille.fr');
            $user->setFirstName($attributes['givenName'] ?? '');
            $user->setLastName($attributes['sn'] ?? '');
            $user->setRoles(['ROLE_STUDENT']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $token = $this->jwtManager->create($user);
        $frontendUrl = $this->getParameter('frontend_url');

        return new RedirectResponse(sprintf('%s/auth/callback?token=%s', $frontendUrl, urlencode($token)));
    }

    /**
     * Initiate GitHub OAuth flow.
     * Returns the GitHub authorization URL for the frontend to redirect to.
     */
    #[Route('/github/login', name: 'api_github_login', methods: ['GET'])]
    public function githubLogin(): RedirectResponse
    {
        $clientId = $this->getParameter('github_client_id');
        $redirectUri = $this->generateUrl('api_github_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $url = sprintf(
            'https://github.com/login/oauth/authorize?client_id=%s&redirect_uri=%s&scope=%s',
            urlencode($clientId),
            urlencode($redirectUri),
            urlencode('user:email'),
        );

        return new RedirectResponse($url);
    }

    /**
     * GitHub OAuth callback.
     * Exchanges the authorization code for an access token, fetches user info, and returns a JWT.
     */
    #[Route('/github/callback', name: 'api_github_callback', methods: ['GET'])]
    public function githubCallback(Request $request, HttpClientInterface $httpClient): RedirectResponse|JsonResponse
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new JsonResponse(['error' => 'Missing authorization code.'], Response::HTTP_BAD_REQUEST);
        }

        $clientId = $this->getParameter('github_client_id');
        $clientSecret = $this->getParameter('github_client_secret');

        // Exchange code for access token
        try {
            $tokenResponse = $httpClient->request('POST', 'https://github.com/login/oauth/access_token', [
                'headers' => ['Accept' => 'application/json'],
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                ],
            ]);

            $tokenData = $tokenResponse->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Failed to exchange authorization code.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            return new JsonResponse(
                ['error' => 'Invalid token response from GitHub.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Fetch GitHub user info
        try {
            $userResponse = $httpClient->request('GET', 'https://api.github.com/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $githubUser = $userResponse->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Failed to fetch GitHub user info.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $githubId = (string) $githubUser['id'];

        // Fetch primary email if not public
        $email = $githubUser['email'] ?? null;
        if (!$email) {
            try {
                $emailResponse = $httpClient->request('GET', 'https://api.github.com/user/emails', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                $emails = $emailResponse->toArray();
                foreach ($emails as $emailEntry) {
                    if ($emailEntry['primary'] ?? false) {
                        $email = $emailEntry['email'];
                        break;
                    }
                }
            } catch (\Throwable) {
                // Fall back to constructed email
                $email = $githubUser['login'] . '@github.com';
            }
        }

        // Find or create user by GitHub ID
        $user = $this->userRepository->findOneBy(['githubId' => $githubId]);

        if (!$user) {
            // Check if a user with the same email exists and link the account
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $user->setGithubId($githubId);
            } else {
                $nameParts = explode(' ', $githubUser['name'] ?? $githubUser['login'], 2);
                $user = new User();
                $user->setGithubId($githubId);
                $user->setEmail($email);
                $user->setFirstName($nameParts[0] ?? '');
                $user->setLastName($nameParts[1] ?? '');
                $user->setRoles(['ROLE_STUDENT']);

                $this->entityManager->persist($user);
            }

            $this->entityManager->flush();
        }

        $token = $this->jwtManager->create($user);
        $frontendUrl = $this->getParameter('frontend_url');

        return new RedirectResponse(sprintf('%s/auth/callback?token=%s', $frontendUrl, urlencode($token)));
    }

    /**
     * Returns the current authenticated user's information.
     */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }
}
