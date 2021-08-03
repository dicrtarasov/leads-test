<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.08.21 00:08:46
 */

declare(strict_types = 1);
namespace dicr\leads_test;

/**
 * Абстрактный логгер.
 */
interface LoggerInterface
{
    /**
     * Вывод сообщения в лог.
     *
     * @param string $msg
     */
    public function log(string $msg): void;
}
