<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$this->isGranted('ROLE_ROOT')) {
             return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
        }
        $user = new User();
        $user->setLogin($data['login'] ?? '');
        $user->setPhone($data['phone'] ?? '');
        $user->setPass($data['pass'] ?? '');
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->formatErrors($errors);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return new JsonResponse([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'phone' => $user->getPhone(),
            'pass' => $user->getPass(),
        ], Response::HTTP_CREATED);
    }
    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->isGranted('ROLE_ROOT')) {
            $currentUser = $this->getUser();
            if (!$currentUser || (method_exists($currentUser, 'getId') && $currentUser->getId() !== $id)) {
                 return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
            }
        }
        return new JsonResponse([
            'login' => $user->getLogin(),
            'phone' => $user->getPhone(),
            'pass' => $user->getPass(),
        ]);
    }
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->isGranted('ROLE_ROOT')) {
            $currentUser = $this->getUser();
            if (!$currentUser || (method_exists($currentUser, 'getId') && $currentUser->getId() !== $id)) {
                 return new JsonResponse(['message' => 'Access Denied'], Response::HTTP_FORBIDDEN);
            }
        }
        $data = json_decode($request->getContent(), true);
        if (!isset($data['login'], $data['phone'], $data['pass'], $data['id'])) {
             return new JsonResponse(['message' => 'Missing mandatory attributes'], Response::HTTP_BAD_REQUEST);
        }
        $user->setLogin($data['login']);
        $user->setPhone($data['phone']);
        $user->setPass($data['pass']);
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->formatErrors($errors);
        }
        $this->entityManager->flush();
        return new JsonResponse(['id' => $user->getId()]);
    }
    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ROOT')]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
    private function formatErrors($errors): JsonResponse
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }
        return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
    }
}
