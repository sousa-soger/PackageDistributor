<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LdapService
{
    /**
     * Authenticate a user against the LDAP server.
     *
     * Uses anonymous bind to locate the user's Distinguished Name, then
     * performs a second bind with the user's own DN and password to verify
     * their credentials.
     *
     * @return array<string, mixed>|null
     */
    public function authenticate(string $usernameOrEmail, string $password): ?array
    {
        if (! extension_loaded('ldap')) {
            Log::error('LDAP Authentication failed: PHP ldap extension is not loaded.');

            return null;
        }

        $host = config('ldap.host');
        $port = config('ldap.port');
        $baseDn = config('ldap.base_dn');

        $conn = @ldap_connect($host, $port);

        if (! $conn) {
            Log::error("LDAP: Could not connect to {$host}:{$port}");

            return null;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        // Strip domain from email to get the bare username for uid/cn searches.
        $username = str_contains($usernameOrEmail, '@')
            ? explode('@', $usernameOrEmail)[0]
            : $usernameOrEmail;

        try {
            if (! @ldap_bind($conn, null, null)) {
                Log::error('LDAP: Anonymous bind failed — '.ldap_error($conn));
                @ldap_close($conn);

                return null;
            }

            $filters = [
                "(uid={$username})",
                "(cn={$username})",
                "(sAMAccountName={$username})",
                "(mail={$usernameOrEmail})",
            ];

            foreach ($filters as $filter) {
                $result = @ldap_search($conn, $baseDn, $filter);

                if (! $result) {
                    continue;
                }

                $entries = ldap_get_entries($conn, $result);

                if ($entries['count'] < 1) {
                    continue;
                }

                $userDn = $entries[0]['dn'];

                if (@ldap_bind($conn, $userDn, $password)) {
                    $userEntry = $entries[0];
                    @ldap_close($conn);

                    return $this->cleanEntry($userEntry);
                }

                // Wrong password — DN was found but bind failed.
                Log::info("LDAP: Password verification failed for DN: {$userDn}");
                @ldap_close($conn);

                return null;
            }

            Log::info("LDAP: User not found in directory for: {$usernameOrEmail}");
        } catch (\Exception $e) {
            Log::error('LDAP Exception: '.$e->getMessage());
        }

        @ldap_close($conn);

        return null;
    }

    /**
     * Strip numeric keys and 'count' noise from an LDAP entry array.
     *
     * @param  array<mixed>  $entry
     * @return array<string, mixed>
     */
    private function cleanEntry(array $entry): array
    {
        return array_filter(
            $entry,
            fn ($key) => ! is_numeric($key) && $key !== 'count',
            ARRAY_FILTER_USE_KEY
        );
    }
}
