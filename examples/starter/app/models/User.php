<?php

use app\core\SEActiveRecordModel;

class User extends SEActiveRecordModel
{
    public ?array $primaryKeys = ['id'];

    /**
     * Active Record talks to the table directly, so this is the real table
     * name including the prefix — the #__ placeholder is only expanded for
     * SQL passed through $snem->db.
     */
    public static function getTableName(): string
    {
        return 'st_users';
    }

    public function relations(): array
    {
        return [
            'posts' => [self::SEAR_RELATION_HAS_MANY, 'author_id', Post::class],
        ];
    }
}
