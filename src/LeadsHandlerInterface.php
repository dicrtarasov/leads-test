<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.08.21 03:55:15
 */

declare(strict_types = 1);
namespace dicr\leads_test;

use LeadGenerator\Generator;

/**
 * Интерфейс процессора очереди лидов.
 * (требуется ПО ТЗ)
 */
interface LeadsHandlerInterface
{
    /**
     * Установить логгер.
     * (для красоты Beans-интерфейса :p)
     *
     * @param LoggerInterface|null $logger
     * @return $this
     */
    public function setLogger(?LoggerInterface $logger): static;

    /**
     * Получить логгер.
     * (для красоты Beans-интерфейса ;p)
     *
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface;

    /**
     * Обработать очередь заявок.
     * Исключения процессор не выбрасывает, а обрабатывает сам.
     *
     * @param Generator $generator очередь (генератор заявок)
     * @return mixed служебная информация, в зависимости от конкретной реализации
     */
    public function processLeadsQueue(Generator $generator): mixed;
}
