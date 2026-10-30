<?php

namespace app\core;

/**
 * Thrown by ADODatabase when the connection or a query fails.
 */
class SEDbException extends SEException
{
    /** @var string ADOdb operation that failed (CONNECT, EXECUTE, ...) */
    private string $operation;

    /** @var string|null the failing SQL, when the error came from a query */
    private ?string $sql;

    public function __construct(string $operation, string $message, int $code = 0, ?string $sql = null)
    {
        $this->operation = $operation;
        $this->sql = $sql;

        $text = "Database {$operation} failed: " . ($message !== '' ? $message : 'unknown error');
        if ($code) {
            $text .= " [{$code}]";
        }
        if ($sql !== null) {
            $text .= "\nSQL: {$sql}";
        }

        parent::__construct($text, $code);
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Database Exception';
    }
}
