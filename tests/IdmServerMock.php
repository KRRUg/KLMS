<?php

namespace App\Tests;

use Ramsey\Uuid\Nonstandard\Uuid;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdmServerMock
{
    private array $users = [
        '52e25be3-cb48-4178-97a8-cb8376b0e120' => ["email" => "admin@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "Admin", "firstname" => "Ali", "surname" => "Admin", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => true, "registeredAt" => "2023-04-10T19:39:35+02:00", "modifiedAt" => "2023-04-10T19:39:35+02:00", "id" => 1, "uuid" => "52e25be3-cb48-4178-97a8-cb8376b0e120", "clans" => []],
        '00000000-0000-0000-0000-000000000000' => ["email" => "user0@localhost.local", "emailConfirmed" => true, "infoMails" => true, "nickname" => "User 0", "firstname" => "User", "surname" => "Null", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => true, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:31+02:00", "modifiedAt" => "2023-04-10T19:39:31+02:00", "id" => 675, "uuid" => "00000000-0000-0000-0000-000000000000", "clans" => []],
        '00000000-0000-0000-0000-000000000001' => ["email" => "user1@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 1", "firstname" => "User", "surname" => "Eins", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-11T06:27:45.00+0200", "modifiedAt" => "2023-04-11T06:28:12.00+0200", "id" => 676, "uuid" => "00000000-0000-0000-0000-000000000001", "clans" => [["uuid" => '00000000-0000-0000-0000-000000000009']]],
        '00000000-0000-0000-0000-000000000002' => ["email" => "user2@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 2", "firstname" => "User", "surname" => "Zwei", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:31+02:00", "modifiedAt" => "2023-04-10T19:39:31+02:00", "id" => 16056, "uuid" => "00000000-0000-0000-0000-000000000002", "clans" => [["uuid" => '00000000-0000-0000-0000-000000000009'], ["uuid" => '00000000-0000-0000-0000-0000000003ea']]],
        '00000000-0000-0000-0000-000000000003' => ["email" => "user3@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 3", "firstname" => "User", "surname" => "Drei", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:31+02:00", "modifiedAt" => "2023-04-10T19:39:31+02:00", "id" => 16057, "uuid" => "00000000-0000-0000-0000-000000000003", "clans" => [["uuid" => '00000000-0000-0000-0000-0000000003ea']]],
        '00000000-0000-0000-0000-000000000004' => ["email" => "user4@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 4", "firstname" => "User", "surname" => "Vier", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:31+02:00", "modifiedAt" => "2023-04-10T19:39:31+02:00", "id" => 16058, "uuid" => "00000000-0000-0000-0000-000000000004", "clans" => [["uuid" => '00000000-0000-0000-0000-0000000003ea']]],
        '00000000-0000-0000-0000-000000000005' => ["email" => "user5@localhost.local", "emailConfirmed" => true, "infoMails" => true, "nickname" => "User 5", "firstname" => "User", "surname" => "F\u00fcnf", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:32+02:00", "modifiedAt" => "2023-04-10T19:39:32+02:00", "id" => 16059, "uuid" => "00000000-0000-0000-0000-000000000005", "clans" => []],
        '00000000-0000-0000-0000-000000000006' => ["email" => "user6@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 6", "firstname" => "User", "surname" => "Sechs", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:32+02:00", "modifiedAt" => "2023-04-10T19:39:32+02:00", "id" => 16060, "uuid" => "00000000-0000-0000-0000-000000000006", "clans" => []],
        '00000000-0000-0000-0000-000000000007' => ["email" => "user7@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 7", "firstname" => "User", "surname" => "Sieben", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => true, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:32+02:00", "modifiedAt" => "2023-04-10T19:39:32+02:00", "id" => 16061, "uuid" => "00000000-0000-0000-0000-000000000007", "clans" => []],
        '00000000-0000-0000-0000-000000000008' => ["email" => "user8@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 8", "firstname" => "User", "surname" => "Acht", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:32+02:00", "modifiedAt" => "2023-04-10T19:39:32+02:00", "id" => 16062, "uuid" => "00000000-0000-0000-0000-000000000008", "clans" => []],
        '00000000-0000-0000-0000-000000000009' => ["email" => "user9@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 9", "firstname" => "User", "surname" => "Neun", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:32+02:00", "modifiedAt" => "2023-04-10T19:39:32+02:00", "id" => 16063, "uuid" => "00000000-0000-0000-0000-000000000009", "clans" => []],
        '00000000-0000-0000-0000-00000000000a' => ["email" => "user10@localhost.local", "emailConfirmed" => true, "infoMails" => true, "nickname" => "User 10", "firstname" => "User", "surname" => "Zehn", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 678, "uuid" => "00000000-0000-0000-0000-00000000000a", "clans" => []],
        '00000000-0000-0000-0000-00000000000b' => ["email" => "user11@localhost.local", "emailConfirmed" => false, "infoMails" => false, "nickname" => "User 11", "firstname" => "User", "surname" => "Elf", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 16065, "uuid" => "00000000-0000-0000-0000-00000000000b", "clans" => []],
        '00000000-0000-0000-0000-00000000000c' => ["email" => "user12@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 12", "firstname" => "User", "surname" => "Zw\u00f6lf", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 16066, "uuid" => "00000000-0000-0000-0000-00000000000c", "clans" => []],
        '00000000-0000-0000-0000-00000000000d' => ["email" => "user13@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 13", "firstname" => "User", "surname" => "Dreizehn", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 16067, "uuid" => "00000000-0000-0000-0000-00000000000d", "clans" => []],
        '00000000-0000-0000-0000-00000000000e' => ["email" => "user14@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 14", "firstname" => "User", "surname" => "Vierzehn", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => true, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 16068, "uuid" => "00000000-0000-0000-0000-00000000000e", "clans" => []],
        '00000000-0000-0000-0000-00000000000f' => ["email" => "user15@localhost.local", "emailConfirmed" => true, "infoMails" => true, "nickname" => "User 15", "firstname" => "User", "surname" => "F\u00fcnfzehn", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:33+02:00", "modifiedAt" => "2023-04-10T19:39:33+02:00", "id" => 16069, "uuid" => "00000000-0000-0000-0000-00000000000f", "clans" => []],
        '00000000-0000-0000-0000-000000000010' => ["email" => "user16@localhost.local", "emailConfirmed" => false, "infoMails" => false, "nickname" => "User 16", "firstname" => "User", "surname" => "Sechzehn", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:34+02:00", "modifiedAt" => "2023-04-10T19:39:34+02:00", "id" => 16070, "uuid" => "00000000-0000-0000-0000-000000000010", "clans" => []],
        '00000000-0000-0000-0000-000000000011' => ["email" => "user17@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 17", "firstname" => "User", "surname" => "Siebzehn", "gender" => "m", "personalDataConfirmed" => false, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:34+02:00", "modifiedAt" => "2023-04-10T19:39:34+02:00", "id" => 16071, "uuid" => "00000000-0000-0000-0000-000000000011", "clans" => []],
        '00000000-0000-0000-0000-000000000012' => ["email" => "user18@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 18", "firstname" => "User", "surname" => "Achtzehn", "gender" => "m", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:34+02:00", "modifiedAt" => "2023-04-10T19:39:34+02:00", "id" => 16072, "uuid" => "00000000-0000-0000-0000-000000000012", "clans" => []],
        '00000000-0000-0000-0000-000000000013' => ["email" => "user19@localhost.local", "emailConfirmed" => true, "infoMails" => false, "nickname" => "User 19", "firstname" => "User", "surname" => "Neunzehn", "gender" => "f", "personalDataConfirmed" => true, "personalDataLocked" => false, "isSuperadmin" => false, "registeredAt" => "2023-04-10T19:39:34+02:00", "modifiedAt" => "2023-04-10T19:39:34+02:00", "id" => 16073, "uuid" => "00000000-0000-0000-0000-000000000013", "clans" => []],
    ];

    private array $clans = [
        '00000000-0000-0000-0000-000000000009' => ["id" => 123, "uuid" => "00000000-0000-0000-0000-000000000009", "name" => "Clan 1", "createdAt" => "2023-04-10T19:39:35+02:00", "modifiedAt" => "2023-04-10T19:39:35+02:00", "clantag" => "CL1", "website" => "http://localhost", "description" => "wubwub", "users" => [["uuid" => "00000000-0000-0000-0000-000000000002"], ["uuid" => "00000000-0000-0000-0000-000000000001"]], "admins" => [["uuid" => "00000000-0000-0000-0000-000000000001"]]],
        '00000000-0000-0000-0000-0000000003ea' => ["id" => 2161, "uuid" => "00000000-0000-0000-0000-0000000003ea", "name" => "Clan 2", "createdAt" => "2023-04-10T19:39:35+02:00", "modifiedAt" => "2023-04-10T19:39:35+02:00", "clantag" => "CL2", "website" => "http://localhost2", "description" => "wubwub", "users" => [["uuid" => "00000000-0000-0000-0000-000000000004"], ["uuid" => "00000000-0000-0000-0000-000000000002"], ["uuid" => "00000000-0000-0000-0000-000000000003"]], "admins" => [["uuid" => "00000000-0000-0000-0000-000000000003"]]],
        '00000000-0000-0000-0000-0000000003ef' => ["id" => 2162, "uuid" => "00000000-0000-0000-0000-0000000003ef", "name" => "Clan 3", "createdAt" => "2023-05-10T19:39:35+02:00", "modifiedAt" => "2023-05-12T07:15:21+02:00", "clantag" => "CL3", "website" => "http://localhost3", "description" => "wubwubwub", "users" => [], "admins" => []],
    ];

    /* settings */
    public function __construct(
        private readonly bool $answerWithDetails = true,
    ){}

    /* Request statistics */

    private int $invalidCalls = 0;
    private IdmServerMockRequest $lastRequest;
    private array $requests = [
        'GET' => [],
        'POST' => [],
        'PATCH' => [],
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
        if (preg_match('/\/api\/(\w+)(\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|bulk|authorize))?(\?((\w+(\[\w+])?=[^?&#]+)(&\w+(\[\w+])?=[^?&#]+)*))?$/', $url, $matches) !== 1) {
            return $this->invalidCall();
        }
        $class = $matches[1];
        $id = $matches[3] ?? '';
        $params = $matches[5] ?? '';
        if ($params) {
            $split = explode('&', $params);
            $split = array_map(fn($v) => explode('=', $v, 2), $split);
            $split = array_combine(array_column($split, 0), array_column($split, 1));
            $params = [];
            foreach ($split as $key => $value) {
                if (preg_match('/^(\w+)\[(\w+)]$/', $key, $matches) === 1) {
                    $name = $matches[1];
                    $param = $matches[2];
                    if (!array_key_exists($name, $params))
                        $params[$name] = array();
                    $params[$name][$param] = $value;
                } else {
                    $params[$key] = $value;
                }
            }
        } else {
            $params = [];
        }
        $body = isset($options['body']) ? json_decode($options['body'], true) : [];
        if (is_null($body)){
            return $this->invalidCall();
        }

        if (!in_array($class, ['users', 'clans'])) {
            return $this->invalidCall();
        }

        $request = new IdmServerMockRequest($class, $id, $params, $body);
        $this->lastRequest = $request;
        $this->requests[$method][] = $request;

        if ($method == 'POST' && empty($id)) {
            $body['uuid'] = Uuid::fromInteger($this->countRequests())->toString();
            $body['clans'] = [];
            return new MockResponse(json_encode($body));
        }

        return match ($class) {
            "users" => match ($id) {
                '' => $this->getUsers($params),
                'bulk' => $this->getUsersBulk($body),
                'authorize' => $this->getUserAuth($body),
                default => $this->getUser($id),
            },
            "clans" => match ($id) {
                '' => $this->getClans($params),
                'bulk' => $this->getClansBulk($body),
                'authorize' => $this->getClanAuth($body),
                default => $this->getClan($id),
            },
            default => $this->invalidCall(),
        };
    }

    private function checkParam(array $param): bool
    {
        foreach ($param as $key => $value) {
            $ok = match ($key) {
                'filter' => is_array($value) || is_string($value),
                'exact', 'case' => $value === 'true' || $value === 'false',
                'page', 'limit' => is_numeric($value),
                default => false
            };
            if (!$ok) {
                return false;
            }
        }
        return true;
    }

    private function formatUser(string $uuid): string
    {
        if (!isset($this->users[$uuid])) {
            return "";
        }
        if ($this->answerWithDetails) {
            // this assignment copies the array
            $user = $this->users[$uuid];
            array_walk($user['clans'], fn(&$a) => $a = $this->clans[$a['uuid']]);
            return json_encode($user);
        } else {
            return json_encode($this->users[$uuid]);
        }
    }

    private function formatClan(string $uuid): string
    {
        if (!isset($this->clans[$uuid])) {
            return "";
        }
        if ($this->answerWithDetails) {
            // this assignment copies the array
            $clan = $this->clans[$uuid];
            array_walk($clan['users'], fn(&$a) => $a = $this->users[$a['uuid']]);
            array_walk($clan['admins'], fn(&$a) => $a = $this->users[$a['uuid']]);
            return json_encode($clan);
        } else {
            return json_encode($this->clans[$uuid]);
        }
    }

    private function getUser(string $uuid): ResponseInterface
    {
        $user = $this->formatUser($uuid);
        if (empty($user)) {
            return $this->invalidCall();
        }
        return new MockResponse($user);
    }

    private function getClan(string $uuid): ResponseInterface
    {
        $clan = $this->formatClan($uuid);
        if (empty($clan)) {
            return $this->invalidCall();
        }
        return new MockResponse($clan);
    }

    private function getUsers(array $param): ResponseInterface
    {
        return $this->handleGet($param, $this->users, fn ($u) => $this->formatUser($u));
    }

    private function getClans(array $param): ResponseInterface
    {
        return $this->handleGet($param, $this->clans, fn ($c) => $this->formatClan($c));
    }

    private function handleGet(array $param, array $dataset, callable $format): ResponseInterface
    {
        if (!$this->checkParam($param)) {
            return $this->invalidCall();
        }

        $exact = ($param['exact'] ?? "true") === "true";
        $case = ($param['case'] ?? "true") === "true";
        $filter = $param['filter'] ?? '';

        $compare = fn(string $a, string $b) => $exact ?
            ($case ? strcmp($a, $b) == 0 : strcasecmp($a, $b) == 0) :
            ($case ? strpos($a, $b) !== false : stripos($a, $b) !== false);

        if (empty($filter)) {
            $data = array_keys($dataset);
        } else {
            $data = [];
            foreach ($dataset as $uuid => $user) {
                if (is_array($filter)) {
                    foreach ($filter as $key => $value) {
                        if (!array_key_exists($key, $user) || !$compare($value, $user[$key])) {
                            continue 2;
                        }
                    }
                    $data[] = $uuid;
                } else {
                    // skip clan array
                    if (array_filter($user, fn($val) => !is_array($val) && $compare($val, $filter))) {
                        $data[] = $uuid;
                    }
                }
            }
        }

        return new MockResponse(
            $this->createPaged(
                $data,
                $format,
                intval($param['limit'] ?? '10'),
                intval($param['page'] ?? '1')
            )
        );
    }

    private function getUsersBulk(array $body): ResponseInterface
    {
        return $this->handleBulk($body, $this->users, fn ($u) => $this->formatUser($u));
    }

    private function getClansBulk(array $body): ResponseInterface
    {
        return $this->handleBulk($body, $this->clans, fn ($c) => $this->formatClan($c));
    }

    private function handleBulk(array $body, array $dataset, callable $format): ResponseInterface
    {
        if (count($body) != 1 || !isset($body["uuid"]) || !is_array($body["uuid"])) {
            return $this->invalidCall();
        }
        $input = $body["uuid"];
        foreach ($input as $uuid) {
            if (!array_key_exists($uuid, $dataset)) {
                return $this->invalidCall();
            }
        }
        return new MockResponse(
            $this->createNonePaged($input, $format)
        );
    }

    private function getUserAuth(array $body): ResponseInterface
    {
        if (!isset($body["name"]) || !is_string($body["name"]) || !isset($body["secret"]) || !is_string($body["secret"])) {
            return $this->invalidCall();
        }
        $name = $body["name"];
        $user = null;
        foreach ($this->users as $key => $val) {
            if ($val['email'] == $name) {
                $user = $key;
                break;
            }
        }
        if (is_null($user)) {
            return $this->invalidCall();
        }
        return new MockResponse(
            $this->formatUser($user)
        );
    }

    private function getClanAuth(array $body): ResponseInterface
    {
        if (!isset($body["name"]) || !is_string($body["name"]) || !isset($body["secret"]) || !is_string($body["secret"])) {
            return $this->invalidCall();
        }
        $name = $body["name"];
        $clan = null;
        foreach ($this->clans as $key => $val) {
            if ($val['name'] == $name) {
                $clan = $key;
                break;
            }
        }
        if (is_null($clan)) {
            return $this->invalidCall();
        }
        return new MockResponse(
            $this->formatClan($clan)
        );
    }

    private function createNonePaged(array $data, callable $format): string
    {
        $result = '[ ';
        foreach ($data as $uuid) {
            $result .= $format($uuid) . ',';
        }
        return substr_replace($result, ']', -1);
    }

    private function createPaged(array $data, callable $format, int $limit = 10, int $page = 0): string
    {
        $total = count($data);
        $offset = ($page - 1) * $limit;
        $limit = min($limit, $total - $offset);
        $result = '{"count":' . $limit . ', "items":[';
        for ($i = 0; $i < $limit; $i++) {
            $result .= $format($data[$offset + $i]) . ($i < $limit - 1 ? ',' : '');
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

    public function getLastRequest(): IdmServerMockRequest
    {
        return $this->lastRequest;
    }

    public function getInvalidCalls(): int
    {
        return $this->invalidCalls;
    }
}