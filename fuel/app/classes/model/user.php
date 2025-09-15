<?php
use Fuel\Core\DB;

class Model_User extends \Model
{
    public static function find_by_username(string $username): ?array
    {
        $row = DB::query("
            SELECT id, username, password AS password_hash
            FROM users
            WHERE username = :username
            LIMIT 1
        ")
        ->bind('username', $username)
        ->execute()
        ->current();

        return $row ?: null;
    }
}
