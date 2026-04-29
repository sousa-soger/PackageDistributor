<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LdapService
{
    /**
     * Authenticate a user against the LDAP server.
     *
     * @return array<string, mixed>|null
     */
    public function authenticate(string $usernameOrEmail, string $password): ?array
    {
        if (! extension_loaded('ldap')) {
            Log::error('LDAP Authentication failed: PHP ldap extension is not loaded.');

            return null;
        }

        $conn = $this->connect();

        if (! $conn) {
            return null;
        }

        $username = $this->extractUsername($usernameOrEmail);
        $filters = [
            "(uid={$this->escape($username)})",
            "(cn={$this->escape($username)})",
            "(sAMAccountName={$this->escape($username)})",
            "(mail={$this->escape($usernameOrEmail)})",
            "(userPrincipalName={$this->escape($usernameOrEmail)})",
        ];

        try {
            if (! $this->bindAnonymously($conn)) {
                @ldap_close($conn);

                return null;
            }

            foreach ($filters as $filter) {
                $entry = $this->firstEntry($conn, $filter);

                if (! $entry) {
                    continue;
                }

                $userDn = $entry['dn'];

                if (@ldap_bind($conn, $userDn, $password)) {
                    @ldap_close($conn);

                    return $this->normalizeDirectoryUser($entry);
                }

                Log::info("LDAP: Password verification failed for DN: {$userDn}");
                @ldap_close($conn);

                return null;
            }

            Log::info("LDAP: User not found in directory for: {$usernameOrEmail}");
        } catch (\Throwable $e) {
            Log::error('LDAP Exception: '.$e->getMessage());
        }

        @ldap_close($conn);

        return null;
    }

    /**
     * Search directory users for typeahead suggestions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchUsers(string $term, int $limit = 8): array
    {
        if (! config('ldap.enabled') || ! extension_loaded('ldap')) {
            return [];
        }

        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $conn = $this->connect();
        if (! $conn) {
            return [];
        }

        try {
            if (! $this->bindAnonymously($conn)) {
                @ldap_close($conn);

                return [];
            }

            $escaped = $this->escape($term);
            $filter = "(|".
                "(uid=*{$escaped}*)".
                "(cn=*{$escaped}*)".
                "(displayName=*{$escaped}*)".
                "(sAMAccountName=*{$escaped}*)".
                "(mail=*{$escaped}*)".
            ")";

            $result = @ldap_search(
                $conn,
                config('ldap.base_dn'),
                $filter,
                $this->searchAttributes(),
                0,
                $limit
            );

            if (! $result) {
                @ldap_close($conn);

                return [];
            }

            $entries = ldap_get_entries($conn, $result);
            $users = [];

            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $normalized = $this->normalizeDirectoryUser($entries[$i]);

                if (! $normalized['username']) {
                    continue;
                }

                $users[strtolower((string) $normalized['username'])] = $normalized;
            }

            @ldap_close($conn);

            return array_values($users);
        } catch (\Throwable $e) {
            Log::error('LDAP search failed: '.$e->getMessage());
            @ldap_close($conn);

            return [];
        }
    }

    /**
     * Find a single directory user by username or email.
     *
     * @return array<string, mixed>|null
     */
    public function findUser(string $usernameOrEmail): ?array
    {
        if (! config('ldap.enabled') || ! extension_loaded('ldap')) {
            return null;
        }

        $conn = $this->connect();
        if (! $conn) {
            return null;
        }

        $username = $this->extractUsername($usernameOrEmail);
        $filters = [
            "(uid={$this->escape($username)})",
            "(cn={$this->escape($username)})",
            "(sAMAccountName={$this->escape($username)})",
            "(mail={$this->escape($usernameOrEmail)})",
            "(userPrincipalName={$this->escape($usernameOrEmail)})",
        ];

        try {
            if (! $this->bindAnonymously($conn)) {
                @ldap_close($conn);

                return null;
            }

            foreach ($filters as $filter) {
                $entry = $this->firstEntry($conn, $filter);

                if ($entry) {
                    @ldap_close($conn);

                    return $this->normalizeDirectoryUser($entry);
                }
            }
        } catch (\Throwable $e) {
            Log::error('LDAP lookup failed: '.$e->getMessage());
        }

        @ldap_close($conn);

        return null;
    }

    public function syncLocalUser(array $directoryUser): User
    {
        $email = $directoryUser['email'] ?? null;
        $username = $directoryUser['username'] ?? null;

        if (! $email) {
            throw new \InvalidArgumentException('The selected LDAP user does not have an email address.');
        }

        $user = User::query()
            ->where(function ($query) use ($username, $email) {
                if ($username) {
                    $query->where('ldap_username', $username);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();

        $user ??= new User();

        if (! $user->exists) {
            $user->password = bcrypt(Str::random(32));
        }

        $user->fill([
            'email' => $email,
            'ldap_display_name' => $directoryUser['name'] ?? null,
            'ldap_photo' => $directoryUser['avatar'] ?: $user->ldap_photo,
            'ldap_username' => $username,
            'name' => $directoryUser['name'] ?: ($username ?: $email),
        ]);

        $user->save();

        return $user;
    }

    protected function connect()
    {
        $host = config('ldap.host');
        $port = config('ldap.port');

        $conn = @ldap_connect($host, $port);

        if (! $conn) {
            Log::error("LDAP: Could not connect to {$host}:{$port}");

            return null;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        return $conn;
    }

    protected function bindAnonymously($conn): bool
    {
        if (! @ldap_bind($conn, null, null)) {
            Log::error('LDAP: Anonymous bind failed - '.ldap_error($conn));

            return false;
        }

        return true;
    }

    protected function firstEntry($conn, string $filter): ?array
    {
        $result = @ldap_search(
            $conn,
            config('ldap.base_dn'),
            $filter,
            $this->searchAttributes(),
            0,
            1
        );

        if (! $result) {
            return null;
        }

        $entries = ldap_get_entries($conn, $result);

        if (($entries['count'] ?? 0) < 1) {
            return null;
        }

        return $entries[0];
    }

    /**
     * @return list<string>
     */
    protected function searchAttributes(): array
    {
        return [
            'cn',
            'displayName',
            'jpegPhoto',
            'mail',
            'sAMAccountName',
            'thumbnailPhoto',
            'uid',
            'userPrincipalName',
        ];
    }

    /**
     * @param  array<mixed>  $entry
     * @return array<string, mixed>
     */
    protected function normalizeDirectoryUser(array $entry): array
    {
        $username = $this->attribute($entry, 'samaccountname')
            ?: $this->attribute($entry, 'uid')
            ?: $this->attribute($entry, 'cn');

        $name = $this->attribute($entry, 'displayname')
            ?: $this->attribute($entry, 'cn')
            ?: $username;

        $email = $this->attribute($entry, 'mail')
            ?: $this->attribute($entry, 'userprincipalname');

        $avatarBinary = $this->attribute($entry, 'thumbnailphoto')
            ?: $this->attribute($entry, 'jpegphoto');

        return [
            'avatar' => $avatarBinary ? $this->toDataUri($avatarBinary) : null,
            'email' => $email,
            'name' => $name,
            'username' => $username,
        ];
    }

    /**
     * @param  array<mixed>  $entry
     */
    protected function attribute(array $entry, string $key): ?string
    {
        $normalizedKey = strtolower($key);

        foreach ($entry as $entryKey => $value) {
            if (strtolower((string) $entryKey) !== $normalizedKey) {
                continue;
            }

            if (is_array($value)) {
                $first = $value[0] ?? null;

                return is_string($first) && $first !== '' ? $first : null;
            }

            return is_string($value) && $value !== '' ? $value : null;
        }

        return null;
    }

    protected function extractUsername(string $usernameOrEmail): string
    {
        return str_contains($usernameOrEmail, '@')
            ? explode('@', $usernameOrEmail)[0]
            : $usernameOrEmail;
    }

    protected function escape(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        return addcslashes($value, '\\()*'."\0");
    }

    protected function toDataUri(string $binary): string
    {
        $mime = $this->detectImageMime($binary);

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    protected function detectImageMime(string $binary): string
    {
        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mime = finfo_buffer($finfo, $binary) ?: null;
                finfo_close($finfo);

                if (is_string($mime) && str_starts_with($mime, 'image/')) {
                    return $mime;
                }
            }
        }

        if (str_starts_with($binary, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($binary, 'GIF8')) {
            return 'image/gif';
        }

        return 'image/jpeg';
    }
}
