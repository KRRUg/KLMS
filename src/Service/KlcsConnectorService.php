<?php

namespace App\Service;

use App\Entity\User;
use App\Service\SeatmapService;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;


class KlcsConnectorService
{
    private readonly UserService $userService;
    private HttpClientInterface $httpClient;
    private mixed $klcsConnectorEnabled;
    private mixed $klcsConnectorUrl;
    private mixed $klcsConnectorClientId;
    private mixed $klcsConnectorClientSecret;
    private mixed $klcsConnectorTokenEndpoint;
    private FilesystemAdapter $cache;
    private SeatmapService $seatmapService;

    public function __construct(
        HttpClientInterface $httpClient,
        SeatmapService $seatmapService,
        UserService $userService,
    )
    {
        $this->klcsConnectorEnabled = $_ENV['KLCS_CONNECTOR_ENABLED'];
        $this->klcsConnectorUrl = $_ENV['KLCS_CONNECTOR_URL'];
        $this->klcsConnectorClientId = $_ENV['KLCS_CONNECTOR_CLIENT_ID'];
        $this->klcsConnectorClientSecret = $_ENV['KLCS_CONNECTOR_CLIENT_SECRET'];
        $this->klcsConnectorTokenEndpoint = $_ENV['KLCS_CONNECTOR_TOKEN_ENDPOINT'];
        $this->httpClient = $httpClient;
        $this->seatmapService = $seatmapService;
        $this->userService = $userService;


        $this->cache = new FilesystemAdapter(
            $namespace = 'klcs_connector',
            $defaultLifetime = 300,
            $directory = null
        );
    $this->seatmapService = $seatmapService;}

    const HOLDER_NAME_MAX_LENGTH = 100;
    const ACCESSTOKEN_CACHE_KEY = 'access_token';

    public function getAccounts(): ResponseInterface
    {

        return $this->httpClient->request('GET', $this->klcsConnectorUrl . '/api/v1/accounts', [
            'auth_bearer' => $this->getAccessToken(),
        ]);
    }

    public function getAccountByKlcsAccountId(User $user, UuidInterface $klcsAccountId): ResponseInterface
    {

        return $this->httpClient->request('GET', $this->klcsConnectorUrl . '/api/v1/accounts' . $klcsAccountId->toString(), [
            'auth_bearer' => $this->getAccessToken(),
        ]);
    }

    public function getAccountsByUser(User $user, bool $locked = false): array
    {

        $request = $this->httpClient->request('GET', $this->klcsConnectorUrl . '/api/v1/accounts', [
            'auth_bearer' => $this->getAccessToken(),
            'query' => [
                'externalId' => $user->getUuid()->toString(),
            ],
        ]);

        $accounts = [];
        if($locked) {
           $accounts = $request->toArray();
        } else {
            $accounts = array_filter($request->toArray(), function($user) {
                return !$user['Locked'];
            });
        }

        return $accounts;
    }

    public function createAccount(User $user, UuidInterface $klcsAccountId, bool $locked = false): ResponseInterface
    {

        return $this->httpClient->request('POST', $this->klcsConnectorUrl . '/api/v1/accounts', [
            'auth_bearer' => $this->getAccessToken(),
            'json' => [
                'Id' => $klcsAccountId->toString(),
                'HolderName' => $this->getHolderName($user),
                'Locked' => $locked,
                'ExternalId' => $user->getUuid(),
            ],
        ]);
    }

    public function editAccount(User $user, UuidInterface $klcsAccountId, bool $locked = false): ResponseInterface
    {

        return $this->httpClient->request('PATCH', $this->klcsConnectorUrl . '/api/v1/accounts/' . $klcsAccountId->toString(), [
            'auth_bearer' => $this->getAccessToken(),
            'json' => [
                'Id' => $klcsAccountId->toString(),
                'HolderName' => $this->getHolderName($user),
                'Locked' => $locked,
                'ExternalId' => $user->getUuid()->toString(),
            ],
        ]);
    }

    /* WARNING: The Amount is in cents! */
    public function chargeAccount(UuidInterface $klcsAccountId, int $amount): bool
    {
        $request = $this->httpClient->request('POST', $this->klcsConnectorUrl . '/api/v1/accounts/' . $klcsAccountId->toString() . '/balance', [
            'auth_bearer' => $this->getAccessToken(),
            'json' => [
                'Amount' => $amount,
            ],
        ]);

        if($request->getStatusCode() == 200 && $request->toArray()['Balance'] == $amount) {
            return true;
        } else {
            return false;
        }
    }

    public function isConnectorEnabled(): bool
    {
        if($this->klcsConnectorEnabled == "true") {
            return true;
        }
        return false;
    }

    private function getAccessToken(): string
    {
        // TODO: Add Support when the Token expires but would technically still be valid (try to get new Token when AUTH Request fails)
        // Checks if the Token in Cache is still valid, otherwise get a new Token
        return $this->cache->get(self::ACCESSTOKEN_CACHE_KEY, function (ItemInterface $item) {
            $httpClient = new HttpClient();
            $request = $httpClient->create()->request('POST', $this->klcsConnectorTokenEndpoint, [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->klcsConnectorClientId,
                    'client_secret' => $this->klcsConnectorClientSecret,
                ],
            ]);
            $response = $request->toArray();
            $item->expiresAfter($response['expires_in']);

            return $response['access_token'];
        });
    }

    private function getHolderName(User $user): string
    {
        // Todo: support more then 1 Seat in Holder
        $seat = $this->seatmapService->getUserSeats($user)[0];
        $holderName = $user->getSurname() . ' ' . $user->getFirstname();
        if(!empty($seat)){
            $holderName .= ' ' . $seat->getSector() . '-' . $seat->getSeatNumber();
        }
        // This is needed for adding the Seat at the end of the HolderName
        // Todo: support Seating Sectors bigger then 1 Letter
        $holderMaxLength = self::HOLDER_NAME_MAX_LENGTH - 5;
        if (strlen($holderName) > $holderMaxLength) {
            $holderName = substr($holderName, 0, self::HOLDER_NAME_MAX_LENGTH);
        }
        return $holderName;
    }
}
