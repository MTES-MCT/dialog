<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\Authenticator;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ProConnect\CreateProConnectUserCommand;
use App\Infrastructure\Security\Authenticator\ProConnectAuthenticator;
use Firebase\JWT\JWT;
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

    // Paire de clés RSA générée pour signer les JWT de test
    private \OpenSSLAsymmetricKey $privateKey;
    private array $jwks;

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

        $this->privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => \OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($this->privateKey);
        $this->jwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'kid' => 'test-key',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $this->base64UrlEncode($details['rsa']['n']),
                    'e' => $this->base64UrlEncode($details['rsa']['e']),
                ],
            ],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function makeJwt(array $payload): string
    {
        return JWT::encode($payload, $this->privateKey, 'RS256', 'test-key');
    }

    private function makeIdToken(array $overrides = []): string
    {
        return $this->makeJwt(array_merge([
            'iss' => $this->domain,
            'aud' => $this->clientId,
            'exp' => time() + 300,
            'sub' => '1234567890',
            'nonce' => 'valid_nonce',
        ], $overrides));
    }

    private function makeUserInfoJwt(array $overrides = []): string
    {
        return $this->makeJwt(array_merge([
            'iss' => $this->domain,
            'aud' => $this->clientId,
            'exp' => time() + 300,
            'sub' => '1234567890',
            'siret' => '12345678901234',
            'email' => 'mathieu@fairness.coop',
            'given_name' => 'Mathieu',
            'usual_name' => 'Marchois',
        ], $overrides));
    }

    /**
     * Configure le client HTTP mocké pour répondre aux endpoints /token, /jwks et /userinfo.
     */
    private function mockHttpEndpoints(array $tokenData, string $userInfoJwt): void
    {
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn($tokenData);

        $jwksResponse = $this->createMock(ResponseInterface::class);
        $jwksResponse->method('toArray')->willReturn($this->jwks);

        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $userInfoResponse->method('getContent')->willReturn($userInfoJwt);

        $this->httpClient
            ->method('request')
            ->willReturnCallback(fn (string $method, string $url) => match ($url) {
                $this->domain . '/token' => $tokenResponse,
                $this->domain . '/jwks' => $jwksResponse,
                $this->domain . '/userinfo' => $userInfoResponse,
                default => throw new \LogicException('Unexpected URL: ' . $url),
            });
    }

    private function mockValidStateAndNonce(): void
    {
        $this->session
            ->method('get')
            ->willReturnMap([
                ['oauth2_state', null, 'valid_state'],
                ['oauth2_nonce', null, 'valid_nonce'],
            ]);
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

        $this->mockValidStateAndNonce();

        $callbackUrl = 'https://example.com/callback';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('pro_connect_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($callbackUrl);

        $idToken = $this->makeIdToken();
        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $idToken],
            $this->makeUserInfoJwt(),
        );

        $this->session
            ->expects(self::once())
            ->method('set')
            ->with('id_token', $idToken);

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

    public function testAuthenticateWithTamperedIdToken(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        // L'id_token est altéré : le payload est remplacé sans re-signature
        $idToken = $this->makeIdToken();
        $parts = explode('.', $idToken);
        $forgedPayload = $this->base64UrlEncode(json_encode([
            'iss' => $this->domain,
            'aud' => $this->clientId,
            'exp' => time() + 300,
            'sub' => 'attacker',
            'nonce' => 'valid_nonce',
        ]));
        $tamperedIdToken = $parts[0] . '.' . $forgedPayload . '.' . $parts[2];

        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $tamperedIdToken],
            $this->makeUserInfoJwt(),
        );

        $this->commandBus->expects(self::never())->method('handle');

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithInvalidNonce(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        // L'id_token contient un nonce différent de celui stocké en session (rejeu)
        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $this->makeIdToken(['nonce' => 'other_nonce'])],
            $this->makeUserInfoJwt(),
        );

        $this->commandBus->expects(self::never())->method('handle');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid nonce');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithInvalidIssuer(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $this->makeIdToken(['iss' => 'https://evil.example.com'])],
            $this->makeUserInfoJwt(),
        );

        $this->commandBus->expects(self::never())->method('handle');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid token issuer');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithInvalidAudience(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $this->makeIdToken(['aud' => 'other_client'])],
            $this->makeUserInfoJwt(),
        );

        $this->commandBus->expects(self::never())->method('handle');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: Invalid token audience');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithUserInfoSubjectMismatch(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        // Le sujet du userinfo ne correspond pas à celui de l'id_token
        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $this->makeIdToken()],
            $this->makeUserInfoJwt(['sub' => 'someone_else']),
        );

        $this->commandBus->expects(self::never())->method('handle');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: UserInfo subject mismatch');
        $this->authenticator->authenticate($this->request);
    }

    public function testAuthenticateWithMissingEmail(): void
    {
        $this->request->query->set('state', 'valid_state');
        $this->request->query->set('code', 'valid_code');

        $this->mockValidStateAndNonce();
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');

        $userInfoJwt = $this->makeJwt([
            'iss' => $this->domain,
            'aud' => $this->clientId,
            'exp' => time() + 300,
            'sub' => '1234567890',
            'name' => 'Mathieu Marchois',
        ]);

        $this->mockHttpEndpoints(
            ['access_token' => 'test_access_token', 'id_token' => $this->makeIdToken()],
            $userInfoJwt,
        );

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
