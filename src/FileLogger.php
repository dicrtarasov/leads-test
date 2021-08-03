<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.08.21 04:16:34
 */

declare(strict_types = 1);
namespace dicr\leads_test;

/**
 * Логгер в файл.
 */
class FileLogger implements LoggerInterface
{
    /** @var string путь файла */
    private $_path;

    /**
     * Конструктор.
     *
     * @param string $path путь файла.
     */
    public function __construct(string $path)
    {
        $this->_path = $path;

        // очищаем прошлый лог
        if (is_file($path)) {
            // используем LOCK_EX в многопоточном окружении
            file_put_contents($this->_path, '', LOCK_EX);
        }
    }

    /**
     * @inheritDoc
     */
    public function log(string $msg): void
    {
        // добавляем сообщение в конец файла, используя эксклюзивный lock
        file_put_contents($this->_path, rtrim($msg) . "\n", LOCK_EX | FILE_APPEND);
    }
}
