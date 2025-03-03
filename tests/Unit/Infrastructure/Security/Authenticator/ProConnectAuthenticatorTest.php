<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\Authenticator;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use App\Infrastructure\Security\Authenticator\ProConnectAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ProConnectAuthenticatorTest extends TestCase
{
    private HttpClientInterface|MockObject $httpClient;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private CommandBusInterface|MockObject $commandBus;
    private ProConnectAuthenticator $authenticator;
    private string $clientId = 'test_client_id';
    private string $clientSecret = 'test_client_secret';
    private string $domain = 'https://auth.proconnect.fr';
    private MockObject $session;
    private MockObject $flashBag;
    private Request $request;

    public function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->authenticator = new ProConnectAuthenticator(
            $this->httpClient,
            $this->urlGenerator,
            $this->commandBus,
            $this->clientId,
            $this->clientSecret,
            $this->domain,
        );

        $this->session = $this->createMock(FlashBagAwareSessionInterface::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->session->method('getFlashBag')->willReturn($this->flashBag);

        $this->request = new Request();
        $this->request->attributes->set('_route', 'pro_connect_callback');
        $this->request->setSession($this->session);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->authenticator->supports($this->request));

        $otherRequest = new Request();
        $otherRequest->attributes->set('_route', 'other_route');
        $this->assertFalse($this->authenticator->supports($otherRequest));
    }

    public function testAuthenticateSuccessfully(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with('oauth2_state')
            ->willReturn('valid_state');

        $callbackUrl = 'https://example.com/callback';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($callbackUrl);

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse
            ->method('toArray')
            ->willReturn([
                'access_token' => 'test_access_token',
                'id_token' => 'test_id_token',
            ]);

        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $payload = json_encode([
            'sub' => '1234567890',
            'siret' => '12345678901234',
            'email' => 'mathieu@fairness.coop',
            'given_name' => 'Mathieu',
            'usual_name' => 'Marchois',
        ]);
        $encodedPayload = base64_encode($payload);
        $jwt = "header.{$encodedPayload}.signature";
        $userInfoResponse
            ->method('getContent')
            ->willReturn($jwt);

        $this->httpClient
            ->expects(self::exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->domain . '/token',
                    [
                        'body' => [
                            'grant_type' => 'authorization_code',
                            'code' => 'valid_code',
                            'client_id' => $this->clientId,
                            'client_secret' => $this->clientSecret,
                            'redirect_uri' => $callbackUrl,
                        ],
                    ],
                ],
                [
                    'GET',
                    $this->domain . '/userinfo',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer test_access_token',
                            'Accept' => 'application/json',
                        ],
                    ],
                ],
            )
            ->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $this->session
            ->expects(self::once())
            ->method('set')
            ->with('id_token', 'test_id_token');

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->callback(function (CreateProConnectUserCommand $command) {
                return $command->email === 'mathieu@fairness.coop';
            }));

        $passport = $this->authenticator->authenticate($this->request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertNotNull($userBadge);
        $this->assertEquals('mathieu@fairness.coop', $userBadge->getUserIdentifier());
    }

    public function testAuthenticateWithInvalidState(): void
    {
        $this->request->query->set('state', 'invalid_state');
        $this->request->query->set('code', 'valid_code');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with('oauth2_state')
            ->willReturn('valid_state');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid state parameter');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithEmptyState(): void
    {
        $this->request->query->set('state', '');
        $this->request->query->set('code', 'valid_code');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid state parameter');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithoutCode(): void
    {
        $this->request->query->set('state', 'valid_state');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with('oauth2_state')
            ->willReturn('valid_state');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: No authorization code provided');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithInvalidTokenResponse(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with('oauth2_state')
            ->willReturn('valid_state');

        $callbackUrl = 'https://example.com/callback';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturn($callbackUrl);

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse
            ->method('toArray')
            ->willReturn([
                // Pas d'access_token ou d'id_token
                'expires_in' => 3600,
            ]);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($tokenResponse);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid token response');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithMissingEmail(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with('oauth2_state')
            ->willReturn('valid_state');

        $callbackUrl = 'https://example.com/callback';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturn($callbackUrl);

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse
            ->method('toArray')
            ->willReturn([
                'access_token' => 'test_access_token',
                'id_token' => 'test_id_token',
            ]);

        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $payloadWithoutEmail = base64_encode(json_encode([
            'sub' => '1234567890',
            'name' => 'Mathieu Marchois',
        ]));
        $userInfoResponse
            ->method('getContent')
            ->willReturn("header.{$payloadWithoutEmail}.signature");

        $this->httpClient
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Email not found in user info');
        $this->authenticator->authenticate($this->request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->session
            ->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                ['oauth2_state'],
                ['oauth2_nonce'],
            );

        $result = $this->authenticator->onAuthenticationSuccess($this->request, $token, 'main');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailure(): void
    {
        $exception = new AuthenticationException('Auth failed');

        $this->session
            ->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                ['oauth2_state'],
                ['oauth2_nonce'],
            );

        $this->flashBag
            ->expects(self::once())
            ->method('add')
            ->with('error', 'Auth failed');

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('app_login')
            ->willReturn('/login');

        $result = $this->authenticator->onAuthenticationFailure($this->request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/login', $result->getTargetUrl());
    }
}
