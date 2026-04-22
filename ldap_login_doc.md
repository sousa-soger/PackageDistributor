# LDAP Login Implementation Guide

This guide provides a single, unified file containing all the logic needed to implement LDAP-based authentication in a generic PHP application or framework (like Laravel). It focuses strictly on **login**, assuming users already exist in the LDAP directory.

## Environment Configuration (`.env`)

Before the logic will work, you **must** configure your environment variables. Add the following to your `.env` file:

```env
# LDAP Configuration
LDAP_ENABLED=
LDAP_HOST=
# LDAP_HOST=ldapdev.lab.sains.com.my
LDAP_PORT=
LDAP_BASE_DN=o=,o=
AUTH_PROVIDER=
```

## Core Logic (Single File Reference)

The following PHP script demonstrates the raw LDAP authentication logic. You can test this script directly from the command line, or adapt it into your framework's authentication classes.

```php
<?php

/**
 * ==========================================
 * OVERVIEW: THE LDAP AUTHENTICATION FLOW
 * ==========================================
 *
 * 1. Connect to LDAP server (ldap_connect)
 * 2. Set LDAP options (Protocol v3, disable referrals)
 * 3. Bind anonymously (ldap_bind with null/null) to search the directory
 * 4. Search for the user's Distinguished Name (DN) using their email/username
 * 5. Attempt a second bind (user bind) using the found DN and provided password
 * 6. If successful, user is authenticated
 */

class SimpleLdapAuthenticator
{
    private $host = 'ldap.sarawak.gov.my';
    private $port = 389;
    private $baseDn = 'o=sains,o=sarawaknet';

    /**
     * Authenticate a user against the LDAP server.
     *
     * @param string $usernameOrEmail The username or email provided by the user.
     * @param string $password The password provided by the user.
     * @return array|false Returns the LDAP user entry array on success, or false on failure.
     */
    public function authenticate(string $usernameOrEmail, string $password)
    {
        // 1. Ensure PHP LDAP extension is loaded
        if (!extension_loaded('ldap')) {
            error_log("LDAP Authentication failed: PHP 'ldap' extension is not installed or enabled.");
            return false;
        }

        // 2. Connect to the LDAP server
        $conn = @ldap_connect($this->host, $this->port);
        if (!$conn) {
            error_log("LDAP Connection failed to {$this->host}:{$this->port}");
            return false;
        }

        // 3. Set required LDAP options
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        // Extract just the username if an email was provided
        $username = $usernameOrEmail;
        if (strpos($usernameOrEmail, '@') !== false) {
            $parts = explode('@', $usernameOrEmail);
            $username = $parts[0]; // e.g., "john.doe" from "john.doe@example.com"
        }

        try {
            // 4. Perform an Anonymous Bind to allow searching
            // Note: If your LDAP server disables anonymous binds, provide a 'service account' DN and password here.
            if (@ldap_bind($conn, null, null)) {

                // 5. Define search filters to locate the user
                $filters = [
                    "(uid={$username})",
                    "(cn={$username})",
                    "(sAMAccountName={$username})",
                    "(mail={$usernameOrEmail})"
                ];

                foreach ($filters as $filter) {
                    // Search the directory using the base DN and the filter
                    $searchResult = @ldap_search($conn, $this->baseDn, $filter);

                    if ($searchResult) {
                        $entries = ldap_get_entries($conn, $searchResult);

                        // User found
                        if ($entries['count'] > 0) {
                            $userDn = $entries[0]['dn']; // The Distinguished Name of the user

                            // 6. Attempt User Bind (Actual Authentication)
                            // We try to bind utilizing the user's specific DN and the password they typed.
                            if (@ldap_bind($conn, $userDn, $password)) {
                                $userEntry = $entries[0]; // Store user info before closing connection
                                ldap_close($conn);
                                // Auth Success! Return the user's LDAP data
                                return reset_numeric_keys($userEntry);
                            } else {
                                error_log("Failed password verification for DN: {$userDn}");
                                ldap_close($conn);
                                return false; // Invalid password
                            }
                        }
                    }
                }
                error_log("User not found in LDAP directory: {$usernameOrEmail}");
            } else {
                error_log("LDAP Anonymous bind failed: " . ldap_error($conn));
            }
        } catch (\Exception $e) {
            error_log("LDAP Exception: " . $e->getMessage());
        }

        if (is_resource($conn) || get_class($conn) === 'LDAP\Connection') {
           @ldap_close($conn);
        }

        return false;
    }
}

// Helper to clean up LDAP array noise
function reset_numeric_keys(array $array) {
    return array_filter($array, fn($key) => !is_numeric($key) && $key !== 'count', ARRAY_FILTER_USE_KEY);
}

// ==========================================
// EXAMPLE USAGE:
// ==========================================
/*
$auth = new SimpleLdapAuthenticator();
$ldapUser = $auth->authenticate('user@example.com', 'securepassword123');

if ($ldapUser) {
    echo "Login Successful! Welcome, " . ($ldapUser['cn'][0] ?? 'User');
    // Proceed to create session, issue token, etc.
} else {
    echo "Invalid credentials.";
}
*/
```

## How to integrate this into your Application

If you are building your own backend or using a framework, you integrate the logic from the `SimpleLdapAuthenticator` class above into specific locations based on your architecture.

### In a standard MVC Framework (like Laravel, CodeIgniter, Symfony)

1. **The Controller ([LoginController.php](file:///c:/Users/mathi/gitlab/CBXHUB/DEV/_latest%20version/cbx-hub-main/app/Http/Controllers/Auth/LoginController.php))**
    - **Role:** Receive the HTTP Request (`$_POST['email']`, `$_POST['password']`).
    - **Action:** Pass these credentials to a Service or Auth Provider.
    - **Result:** If auth succeeds, create a session cookie/JWT and redirect. If it fails, return an error message.

2. **The Authentication Provider (`LdapAuthProvider.php` or `AuthManager.php`)**
    - **Role:** This is where the `SimpleLdapAuthenticator` logic lives.
    - **Action:** Execute the `ldap_connect` -> `search` -> `user bind` flow.
    - **Integration:** If LDAP returns success, you then look up the user locally in your Database (e.g., `SELECT * FROM users WHERE email = ?`).
        - **If User exists in DB:** Log them in.
        - **If User does NOT exist in DB:** (Auto-registration) Create a new row in your `users` table copying their Name/Email from the LDAP payload, then log them in.

### Simplified Flow for Custom PHP Application

If you are not using a heavy framework, you can map the logic directly into a single endpoint:

**File:** `login.php` (Your API endpoint or Form handler)

```php
<?php
session_start();
require_once 'SimpleLdapAuthenticator.php'; // The class from above
require_once 'database.php'; // Your DB connection (PDO)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $authenticator = new SimpleLdapAuthenticator();
    $ldapUser = $authenticator->authenticate($email, $password);

    if ($ldapUser) {
        // 1. LDAP SUCCESS
        // 2. Fetch or Sync User to Local Database
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // First time logging in, auto-create the DB stub so your app has a foreign key to use
            $name = $ldapUser['cn'][0] ?? $ldapUser['displayname'][0] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO users (email, name) VALUES (?, ?)");
            $stmt->execute([$email, $name]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['id'];
        }

        // 3. Create Session
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;

        // 4. Redirect
        header("Location: /dashboard.php");
        exit;

    } else {
        // LDAP FAIL
        $error = "Invalid username or password.";
        // Show login form again with $error
    }
}
```
