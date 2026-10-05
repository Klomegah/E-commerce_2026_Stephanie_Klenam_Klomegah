<?php

/* core validation.php - one shared source of truth for field validation. */

class Validator
{
    // ---------- PASSWORD ----------.

    const PASS_MIN = 8;
    const PASS_MAX = 128;

    const PASS_SPECIALS = '!@#$%^&*()-_=+[]{}<>?/\\|~.,;:';

/* Passwords that are already known to be in every wordlist. */
    const PASS_COMMON = [
        'password', 'passw0rd', 'password1', 'password123', 'password!',
        'passw0rd!', 'passw0rd1', '12345678', '123456789', '1234567890',
        'qwertyui', 'qwerty123', 'letmein123', 'iloveyou1', 'admin123',
        'welcome1', 'welcome123', 'abc12345', 'abcd1234', 'shoppn123',
        'shoppn1234', 'shoppnadmin', '11111111', '00000000', '87654321',
    ];

/**
 * @return array<int, array{key:string, test:string, label:string}>
 */
    public static function passwordRules()
    {
        return [
            [
                'key'   => 'length',
                'test'  => 'length',
                'label' => 'At least ' . self::PASS_MIN . ' characters long',
            ],
            [
                'key'   => 'upper',
                'test'  => 'upper',
                'label' => 'At least 1 uppercase letter (A-Z)',
            ],
            [
                'key'   => 'lower',
                'test'  => 'lower',
                'label' => 'At least 1 lowercase letter (a-z)',
            ],
            [
                'key'   => 'number',
                'test'  => 'number',
                'label' => 'At least 1 number (0-9)',
            ],
            [
                'key'   => 'special',
                'test'  => 'special',
                'label' => 'At least 1 special character (!@#$%^&* etc.)',
            ],
            [
                'key'   => 'not_common',
                'test'  => 'notcommon',
                'label' => 'Not a common or easily guessed password',
            ],
            [
                'key'   => 'not_sequential',
                'test'  => 'notsequential',
                'label' => 'No run of 4+ letters or digits in order (e.g. abcd, 1234)',
            ],
            [
                'key'   => 'not_repeated',
                'test'  => 'notrepeated',
                'label' => 'No run of 3+ of the same character (e.g. aaaa, 111)',
            ],
        ];
    }

/**
 * @param string $pass       the plaintext password
 * @param string $email      the address being registered for, so the
 * @param string $confirm    optional second entry, for confirm fields
 * @return string|null the first failing rule, or null when it passes
 */
    public static function checkPassword($pass, $email = '', $confirm = null)
    {
        if ($pass === '' || $pass === null) {
            return 'Please enter a password.';
        }

        if (strlen($pass) < self::PASS_MIN) {
            return 'Password must be at least ' . self::PASS_MIN . ' characters long.';
        }

        // bcrypt silently ignores everything past 72 bytes, so two.
        // different 200-character passwords would log in identically.
        // denial of service on the hashing step.
        if (strlen($pass) > self::PASS_MAX) {
            return 'Password must be ' . self::PASS_MAX . ' characters or fewer.';
        }

        if (strlen($pass) > 72) {
            return 'Password must be 72 characters or fewer.';
        }

        if ($confirm !== null && $pass !== $confirm) {
            return 'The two passwords do not match.';
        }

        if (!preg_match('/[A-Z]/', $pass)) {
            return 'Password needs at least 1 uppercase letter (A-Z).';
        }

        if (!preg_match('/[a-z]/', $pass)) {
            return 'Password needs at least 1 lowercase letter (a-z).';
        }

        if (!preg_match('/[0-9]/', $pass)) {
            return 'Password needs at least 1 number (0-9).';
        }

        if (self::specialCount($pass) < 1) {
            return 'Password needs at least 1 special character (' . self::PASS_SPECIALS . ').';
        }

        if (in_array(strtolower($pass), self::PASS_COMMON, true)) {
            return 'That password is too common. Please choose another.';
        }

        if (self::hasSequentialRun($pass)) {
            return 'Password contains 4 or more letters or digits in order (e.g. abcd, 1234).';
        }

        if (self::hasRepeatedRun($pass)) {
            return 'Password contains 3 or more of the same character in a row.';
        }

        // "jsmith@shoppn.com" must not be accepted as a strong password just.
        if ($email !== '' && self::passwordEchoesEmail($pass, $email)) {
            return 'Password must not be based on your email address.';
        }

        if ($email !== '' && strtolower($pass) === strtolower($email)) {
            return 'Password must not be the same as your email address.';
        }

        return null;
    }

    /* How many characters from the special set appear in the password. */
    private static function specialCount($pass)
    {
        return preg_match_all('/[' . preg_quote(self::PASS_SPECIALS, '/') . ']/', $pass);
    }

    private static function hasSequentialRun($pass)
    {
        $length = strlen($pass);

        for ($i = 0; $i < $length - 3; $i++) {
            $a = ord($pass[$i]);
            $b = ord($pass[$i + 1]);
            $c = ord($pass[$i + 2]);
            $d = ord($pass[$i + 3]);

            $step = $b - $a;

            if ($step !== 0
                && $c - $b === $step
                && $d - $c === $step
                && abs($step) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function hasRepeatedRun($pass)
    {
        return (bool) preg_match('/(.)\1{2,}/u', $pass);
    }

/* True when the password is built out of the email address itself, e.g. */
    private static function passwordEchoesEmail($pass, $email)
    {
        $local = strtolower(explode('@', $email)[0] ?? '');

        if ($local === '' || strlen($local) < 4) {
            return false;
        }

        $bare = preg_replace('/[^a-z0-9]/', '', strtolower($pass));
        $localBare = preg_replace('/[^a-z0-9]/', '', $local);

        if ($localBare === '' || strlen($localBare) < 4) {
            return false;
        }

        return strpos($bare, $localBare) !== false;
    }



/* A deliberately stricter email check than FILTER_VALIDATE_EMAIL alone. */
    const EMAIL_PATTERN = '/^[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+'
                        . '(?:\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*'
                        . '@'
                        . '(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+'
                        . '[A-Za-z]{2,63}$/';

/**
 * @return string|null the reason the address is unacceptable, or null
 */
    public static function checkEmail($email)
    {
        if ($email === '' || $email === null) {
            return 'Please enter your email address.';
        }

        if (strlen($email) > 254) {
            return 'Email address is too long.';
        }

        if (preg_match('/[\s\x00-\x1F\x7F]/', $email)) {
            return 'Email address cannot contain spaces or control characters.';
        }

        if (!preg_match(self::EMAIL_PATTERN, $email)) {
            return 'Please enter a valid email address, for example name@example.com.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        return null;
    }



    const COUNTRY_DIAL_PLAN = [
        'Ghana'           => ['dial' => '233', 'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'Nigeria'         => ['dial' => '234', 'nsn_min' => 10, 'nsn_max' => 10, 'trunk' => '0'],
        'Kenya'           => ['dial' => '254', 'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'South Africa'    => ['dial' => '27',  'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'United Kingdom'  => ['dial' => '44',  'nsn_min' => 9, 'nsn_max' => 10, 'trunk' => '0'],
        'United States'   => ['dial' => '1',   'nsn_min' => 10, 'nsn_max' => 10, 'trunk' => ''],
        'Canada'          => ['dial' => '1',   'nsn_min' => 10, 'nsn_max' => 10, 'trunk' => ''],
        'Australia'       => ['dial' => '61',  'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'India'           => ['dial' => '91',  'nsn_min' => 10, 'nsn_max' => 10, 'trunk' => '0'],
        'Germany'         => ['dial' => '49',  'nsn_min' => 6, 'nsn_max' => 11, 'trunk' => '0'],
        'France'          => ['dial' => '33',  'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'Spain'           => ['dial' => '34',  'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => ''],
        'Italy'           => ['dial' => '39',  'nsn_min' => 6, 'nsn_max' => 11, 'trunk' => ''],
        'Netherlands'     => ['dial' => '31',  'nsn_min' => 9, 'nsn_max' => 9, 'trunk' => '0'],
        'Belgium'         => ['dial' => '32',  'nsn_min' => 8, 'nsn_max' => 9, 'trunk' => '0'],
        'Ireland'         => ['dial' => '353', 'nsn_min' => 7, 'nsn_max' => 9, 'trunk' => '0'],
        'South Korea'     => ['dial' => '82',  'nsn_min' => 9, 'nsn_max' => 10, 'trunk' => '0'],
        'United Arab Emirates' => ['dial' => '971', 'nsn_min' => 8, 'nsn_max' => 9, 'trunk' => '0'],
        'Brazil'          => ['dial' => '55',  'nsn_min' => 10, 'nsn_max' => 11, 'trunk' => '0'],
        'Mexico'          => ['dial' => '52',  'nsn_min' => 10, 'nsn_max' => 10, 'trunk' => '0'],
    ];

    const COUNTRY_OTHER = 'Other';

    const NSN_MAX_E164 = 15;

    public static function countries()
    {
        return array_merge(array_keys(self::COUNTRY_DIAL_PLAN), [self::COUNTRY_OTHER]);
    }

    public static function phoneHint($country)
    {
        if (!isset(self::COUNTRY_DIAL_PLAN[$country])) {
            return 'Use international format: +<country code> then the number, '
                 . 'for example +1 202 555 0143 or +233 24 123 4567.';
        }

        $plan  = self::COUNTRY_DIAL_PLAN[$country];
        $dial  = '+' . $plan['dial'];
        $count = $plan['nsn_min'] === $plan['nsn_max']
            ? (string) $plan['nsn_min']
            : $plan['nsn_min'] . '-' . $plan['nsn_max'];

        return 'Enter ' . $dial . ' followed by ' . $count . ' digits, '
             . 'for example ' . $dial . ' 20 7946 0018. Spaces, hyphens and '
             . 'brackets are fine.';
    }

/**
 * @param string $phone
 * @param string $country  when given and known, a national number is
 * @return string the canonical form, best stored in the database
 */
    public static function normalisePhone($phone, $country = '')
    {
        $phone = trim((string) $phone);
        $hasPlus = strncmp($phone, '+', 1) === 0;

        $digits = preg_replace('/[^0-9]/', '', $hasPlus ? substr($phone, 1) : $phone);

        if (!isset(self::COUNTRY_DIAL_PLAN[$country])) {
            return $hasPlus ? '+' . $digits : $digits;
        }

        $plan = self::COUNTRY_DIAL_PLAN[$country];
        $dial = $plan['dial'];

        if ($hasPlus) {
            if (strncmp($digits, $dial, strlen($dial)) === 0) {
                return '+' . $digits;
            }

            return '+' . $digits;
        }

        $nsn = $digits;

        if ($plan['trunk'] !== '' && strncmp($nsn, $plan['trunk'], 1) === 0) {
            $nsn = substr($nsn, 1);
        }

        return '+' . $dial . $nsn;
    }

/**
 * @param string $phone
 * @param string $country  must match a key in COUNTRY_DIAL_PLAN
 * @param int    $maxChars the column width. Applied to the number AS
 * @return string|null the reason it was rejected, or null when valid
 */
    public static function checkPhone($phone, $country, $maxChars = 15)
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return 'Please enter your contact number.';
        }

        if (strncmp($phone, '(', 1) === 0) {
            $close = strpos($phone, ')');

            if ($close === false || $close === 1) {
                return 'Contact number has an unfinished bracket.';
            }

            $phone = substr($phone, 1, $close - 1) . substr($phone, $close + 1);
        }

        if (!preg_match('/^\+?[0-9 .\/-]+$/', $phone)) {
            return 'Contact number may only contain digits, spaces, +, -, brackets and dots.';
        }

        $plusPos = strpos($phone, '+');

        if ($plusPos !== false && $plusPos !== 0) {
            return 'Put the + at the very start of the number, or leave it out entirely.';
        }

        if (preg_match('/[.\s\/-]{2,}/', $phone)) {
            return 'Contact number has a stray space or symbol.';
        }

        $hasPlus = $plusPos === 0;
        $digits  = preg_replace('/[^0-9]/', '', $hasPlus ? substr($phone, 1) : $phone);

        if (strlen($digits) > self::NSN_MAX_E164) {
            return 'That is more digits than any phone number can have.';
        }

        if (!isset(self::COUNTRY_DIAL_PLAN[$country])) {
            if (!$hasPlus || !preg_match('/^[1-9][0-9]{7,14}$/', $digits)) {
                return 'Use international format: +<country code> then the number, '
                     . 'for example +233 24 123 4567.';
            }

            if (strlen($digits) + 1 > $maxChars) {
                return 'Contact number is too long for the ' . $maxChars
                     . ' character limit. Leave out the country code.';
            }

            return null;
        }

        $plan = self::COUNTRY_DIAL_PLAN[$country];
        $dial = $plan['dial'];

        if ($hasPlus) {
            if (strncmp($digits, $dial, strlen($dial)) !== 0) {
                return 'That country code does not match ' . $country
                     . '. Use ' . $country . ' with +' . $dial . ', '
                     . 'or leave the + out for the local format.';
            }

            if (strlen($digits) > strlen($dial) && $digits[strlen($dial)] === '0') {
                return 'Remove the 0 straight after the country code.';
            }

            $nsn = substr($digits, strlen($dial));
        } else {
            $nsn = $digits;

            if ($plan['trunk'] !== '' && strncmp($nsn, $plan['trunk'], 1) === 0) {
                $nsn = substr($nsn, 1);
            }
        }

        $len = strlen($nsn);

        if ($len < $plan['nsn_min'] || $len > $plan['nsn_max']) {
            $expected = $plan['nsn_min'] === $plan['nsn_max']
                ? $plan['nsn_min'] . ' digits'
                : $plan['nsn_min'] . '-' . $plan['nsn_max'] . ' digits';

            return 'A ' . $country . ' number needs ' . $expected
                 . ' after the country code. Yours has ' . $len . '.';
        }

        if (strlen($dial) + $len + 1 > $maxChars) {
            return 'That number is too long to store. Please check it and try again.';
        }

        return null;
    }
}
