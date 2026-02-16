<?php
declare(strict_types=1);
namespace App\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
class UserControllerTest extends WebTestCase
{
    private $client;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('users', true));
    }
    public function testRootCanCreateUser(): void
    {
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'testuser', 'phone' => '12345678', 'pass' => 'testpass'])
        );
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('testuser', $data['login']);
    }
    public function testUserCannotAccessOtherUser(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], json_encode(['login' => 'user1', 'phone' => '111', 'pass' => 'pass1']));
        $user1Id = json_decode($this->client->getResponse()->getContent(), true)['id'];
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], json_encode(['login' => 'user2', 'phone' => '222', 'pass' => 'pass2']));
        $user2Id = json_decode($this->client->getResponse()->getContent(), true)['id'];
        $this->client->request('GET', '/v1/api/users/' . $user2Id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer pass1']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->client->request('GET', '/v1/api/users/' . $user1Id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer pass1']);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
    public function testUserCannotDelete(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], json_encode(['login' => 'user3', 'phone' => '333', 'pass' => 'pass3']));
        $user3Id = json_decode($this->client->getResponse()->getContent(), true)['id'];
        $this->client->request('DELETE', '/v1/api/users/' . $user3Id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer pass3']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->client->request('DELETE', '/v1/api/users/' . $user3Id, [], [], ['HTTP_AUTHORIZATION' => 'Bearer root']);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
    public function testUserCanUpdateOwnData(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], json_encode(['login' => 'u4', 'phone' => '4', 'pass' => 'p4']));
        $id = json_decode($this->client->getResponse()->getContent(), true)['id'];
        $this->client->request('PUT', '/v1/api/users/' . $id, [], [], 
            ['HTTP_AUTHORIZATION' => 'Bearer p4', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['id' => $id, 'login' => 'u4new', 'phone' => '444', 'pass' => 'p4'])
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
    public function testValidationFailsOnLongFields(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], 
            json_encode(['login' => 'too_long_login', 'phone' => '1', 'pass' => '1'])
        );
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
    public function testUniquenessValidation(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], 
            json_encode(['login' => 'dup', 'phone' => '1', 'pass' => 'dup1'])
        );
        $this->client->request('POST', '/v1/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer root', 'CONTENT_TYPE' => 'application/json'], 
            json_encode(['login' => 'dup', 'phone' => '2', 'pass' => 'dup2'])
        );
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
