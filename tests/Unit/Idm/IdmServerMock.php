<?php

namespace App\Tests\Unit\Idm;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdmServerMock
{
    private const USERS = [
        '52e25be3-cb48-4178-97a8-cb8376b0e120' => ['"email":"admin@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"Admin","firstname":"Ali","surname":"Admin","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":true,"registeredAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","id":1,"uuid":"52e25be3-cb48-4178-97a8-cb8376b0e120"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000000' => ['"email":"user0@localhost.local","emailConfirmed":true,"infoMails":true,"nickname":"User 0","firstname":"User","surname":"Null","gender":"m","personalDataConfirmed":true,"personalDataLocked":true,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:31+02:00","modifiedAt":"2023-04-10T19:39:31+02:00","id":675,"uuid":"00000000-0000-0000-0000-000000000000"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000001' => ['"email":"user1@localhost.local","emailConfirmed":false,"infoMails":false,"nickname":"User 1","firstname":"User","surname":"Eins","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-11T06:27:45.00+0200","modifiedAt":"2023-04-11T06:28:12.00+0200","id":676,"uuid":"00000000-0000-0000-0000-000000000001"', '"clans":[{"id":123,"uuid":"00000000-0000-0000-0000-000000000009","name":"Clan 1","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL1","website":"http:\/\/localhost","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000001"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000001"}]}]', '"clans":[{"uuid":"00000000-0000-0000-0000-000000000009"}]'],
        '00000000-0000-0000-0000-00000000000a' => ['"email":"user10@localhost.local","emailConfirmed":true,"infoMails":true,"nickname":"User 10","firstname":"User","surname":"Zehn","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":678,"uuid":"00000000-0000-0000-0000-00000000000a"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-00000000000b' => ['"email":"user11@localhost.local","emailConfirmed":false,"infoMails":false,"nickname":"User 11","firstname":"User","surname":"Elf","gender":"m","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":16065,"uuid":"00000000-0000-0000-0000-00000000000b"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-00000000000c' => ['"email":"user12@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 12","firstname":"User","surname":"Zw\u00f6lf","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":16066,"uuid":"00000000-0000-0000-0000-00000000000c"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-00000000000d' => ['"email":"user13@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 13","firstname":"User","surname":"Dreizehn","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":16067,"uuid":"00000000-0000-0000-0000-00000000000d"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-00000000000e' => ['"email":"user14@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 14","firstname":"User","surname":"Vierzehn","gender":"m","personalDataConfirmed":false,"personalDataLocked":true,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":16068,"uuid":"00000000-0000-0000-0000-00000000000e"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-00000000000f' => ['"email":"user15@localhost.local","emailConfirmed":true,"infoMails":true,"nickname":"User 15","firstname":"User","surname":"F\u00fcnfzehn","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:33+02:00","modifiedAt":"2023-04-10T19:39:33+02:00","id":16069,"uuid":"00000000-0000-0000-0000-00000000000f"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000010' => ['"email":"user16@localhost.local","emailConfirmed":false,"infoMails":false,"nickname":"User 16","firstname":"User","surname":"Sechzehn","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:34+02:00","modifiedAt":"2023-04-10T19:39:34+02:00","id":16070,"uuid":"00000000-0000-0000-0000-000000000010"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000011' => ['"email":"user17@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 17","firstname":"User","surname":"Siebzehn","gender":"m","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:34+02:00","modifiedAt":"2023-04-10T19:39:34+02:00","id":16071,"uuid":"00000000-0000-0000-0000-000000000011"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000012' => ['"email":"user18@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 18","firstname":"User","surname":"Achtzehn","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:34+02:00","modifiedAt":"2023-04-10T19:39:34+02:00","id":16072,"uuid":"00000000-0000-0000-0000-000000000012"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000013' => ['"email":"user19@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 19","firstname":"User","surname":"Neunzehn","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:34+02:00","modifiedAt":"2023-04-10T19:39:34+02:00","id":16073,"uuid":"00000000-0000-0000-0000-000000000013"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000002' => ['"email":"user2@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 2","firstname":"User","surname":"Zwei","gender":"m","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:31+02:00","modifiedAt":"2023-04-10T19:39:31+02:00","id":16056,"uuid":"00000000-0000-0000-0000-000000000002"', '"clans":[{"id":123,"uuid":"00000000-0000-0000-0000-000000000009","name":"Clan 1","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL1","website":"http:\/\/localhost","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000001"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000001"}]},{"id":2161,"uuid":"00000000-0000-0000-0000-0000000003ea","name":"Clan 2","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL2","website":"http:\/\/localhost2","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000004"},{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000003"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000003"}]}]', '"clans":[{"uuid":"00000000-0000-0000-0000-000000000009"},{"uuid":"00000000-0000-0000-0000-0000000003ea"}]'],
        '00000000-0000-0000-0000-000000000003' => ['"email":"user3@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 3","firstname":"User","surname":"Drei","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:31+02:00","modifiedAt":"2023-04-10T19:39:31+02:00","id":16057,"uuid":"00000000-0000-0000-0000-000000000003"', '"clans":[{"id":2161,"uuid":"00000000-0000-0000-0000-0000000003ea","name":"Clan 2","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL2","website":"http:\/\/localhost2","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000004"},{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000003"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000003"}]}]', '"clans":[{"uuid":"00000000-0000-0000-0000-0000000003ea"}]'],
        '00000000-0000-0000-0000-000000000004' => ['"email":"user4@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 4","firstname":"User","surname":"Vier","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:31+02:00","modifiedAt":"2023-04-10T19:39:31+02:00","id":16058,"uuid":"00000000-0000-0000-0000-000000000004"', '"clans":[{"id":2161,"uuid":"00000000-0000-0000-0000-0000000003ea","name":"Clan 2","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL2","website":"http:\/\/localhost2","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000004"},{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000003"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000003"}]}]', '"clans":[{"uuid":"00000000-0000-0000-0000-0000000003ea"}]'],
        '00000000-0000-0000-0000-000000000005' => ['"email":"user5@localhost.local","emailConfirmed":true,"infoMails":true,"nickname":"User 5","firstname":"User","surname":"F\u00fcnf","gender":"m","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:32+02:00","modifiedAt":"2023-04-10T19:39:32+02:00","id":16059,"uuid":"00000000-0000-0000-0000-000000000005"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000006' => ['"email":"user6@localhost.local","emailConfirmed":false,"infoMails":false,"nickname":"User 6","firstname":"User","surname":"Sechs","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:32+02:00","modifiedAt":"2023-04-10T19:39:32+02:00","id":16060,"uuid":"00000000-0000-0000-0000-000000000006"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000007' => ['"email":"user7@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 7","firstname":"User","surname":"Sieben","gender":"f","personalDataConfirmed":true,"personalDataLocked":true,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:32+02:00","modifiedAt":"2023-04-10T19:39:32+02:00","id":16061,"uuid":"00000000-0000-0000-0000-000000000007"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000008' => ['"email":"user8@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 8","firstname":"User","surname":"Acht","gender":"m","personalDataConfirmed":false,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:32+02:00","modifiedAt":"2023-04-10T19:39:32+02:00","id":16062,"uuid":"00000000-0000-0000-0000-000000000008"', '"clans":[]', '"clans":[]'],
        '00000000-0000-0000-0000-000000000009' => ['"email":"user9@localhost.local","emailConfirmed":true,"infoMails":false,"nickname":"User 9","firstname":"User","surname":"Neun","gender":"m","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-10T19:39:32+02:00","modifiedAt":"2023-04-10T19:39:32+02:00","id":16063,"uuid":"00000000-0000-0000-0000-000000000009"', '"clans":[]', '"clans":[]'],
    ];

    private const CLANS = [
        '00000000-0000-0000-0000-000000000009' => '{"id":123,"uuid":"00000000-0000-0000-0000-000000000009","name":"Clan 1","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL1","website":"http:\/\/localhost","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000001"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000001"}]}',
        '00000000-0000-0000-0000-0000000003ea' => '{"id":2161,"uuid":"00000000-0000-0000-0000-0000000003ea","name":"Clan 2","createdAt":"2023-04-10T19:39:35+02:00","modifiedAt":"2023-04-10T19:39:35+02:00","clantag":"CL2","website":"http:\/\/localhost2","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000004"},{"uuid":"00000000-0000-0000-0000-000000000002"},{"uuid":"00000000-0000-0000-0000-000000000003"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000003"}]}',
        '00000000-0000-0000-0000-0000000003ef' => '{"id":2162,"uuid":"00000000-0000-0000-0000-0000000003ef","name":"Clan 3","createdAt":"2023-05-10T19:39:35+02:00","modifiedAt":"2023-05-12T07:15:21+02:00","clantag":"CL3","website":"http:\/\/localhost3","description":"wubwubwub","users":[],"admins":[]}',
    ];

    /* settings */
    public function __construct(
        private readonly bool $answerWithDetails = true,
    ){}

    /* Request statistics */
    private int $invalidCalls = 0;
    private array $requests = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    private function invalidCall(): ResponseInterface
    {
        $this->invalidCalls++;
        return new MockResponse();
    }

    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        if (!array_key_exists($method, $this->requests)) {
            return $this->invalidCall();
        }
        $this->requests[$method][] = $url;
        if (preg_match('/\/api\/(\w+)(\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}))?(\?((\w+=\w+)(&\w+=\w+)*))?$/', $url, $matches) !== 1) {
            return $this->invalidCall();
        }
        $class = $matches[1];
        $uuid = $matches[3] ?? '';
        $params = $matches[5] ?? '';
        $params = explode('&', $params);
        $params = array_map(fn($v) => explode('=', $v, 2), $params);
        $params = array_combine(array_column($params, 0), array_column($params, 1));

        switch ($class) {
            case "users":
                if (empty($uuid)) {
                    return $this->getUsers($params);
                } else {
                    return $this->getUser($uuid);
                }
            case "clans":
                if (empty($uuid)) {
                    return $this->getClans($params);
                } else {
                    return $this->getClan($uuid);
                }
            default:
                return $this->invalidCall();
        }
    }
    
    private function getUser(string $uuid): ResponseInterface
    {
        if (isset(self::USERS[$uuid])) {
            $line = self::USERS[$uuid];
            $string = '{' . $line[0] . ',' . ($this->answerWithDetails ? $line[1] : $line[2]) . '}';
            return new MockResponse($string);
        }
        return $this->invalidCall();
    }

    private function getUsers(array $param): ResponseInterface
    {
        // todo check $param and pass it to createPaged
        return new MockResponse($this->createPaged(array_values(self::USERS), fn($line) => '{' . $line[0] . ',' . ($this->answerWithDetails ? $line[1] : $line[2]) . '}'));
    }

    private function getClan(string $uuid): ResponseInterface
    {
        if (isset(self::CLANS[$uuid])) {
            return new MockResponse(self::CLANS[$uuid]);
        }
        return $this->invalidCall();
    }

    private function getClans(array $params): ResponseInterface
    {
        return $this->invalidCall();
    }

    private function createPaged(array $data, callable $format, int $limit = 10, int $offset = 0): string
    {
        $total = count($data);
        $limit = min($limit, $total - $offset);
        $result = '{"count":' . $limit . ', "items":[';
        for ($i = 0; $i < $limit; $i++) {
            $result .= $format($data[$offset + $i]) . ',';
        }
        $result .= '],';
        $result .= '"total":' . $total . '}';
        return $result;
    }

    public function countRequests(string $method = ''): int
    {
        if (empty($method)) {
            $sum = 0;
            foreach ($this->requests as $r) {
                $sum += count($r);
            }
            return $sum;
        } else {
            return array_key_exists($method, $this->requests) ? count($this->requests[$method]) : 0;
        }
    }

    public function getInvalidCalls(): int
    {
        return $this->invalidCalls;
    }
}