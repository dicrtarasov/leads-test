<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.08.21 05:04:05
 */

declare(strict_types = 1);
namespace dicr\leads_test;

use LeadGenerator\Generator;
use LeadGenerator\Lead;
use RuntimeException;
use SysvMessageQueue;

use function msg_get_queue;
use function msg_remove_queue;
use function msg_send;
use function pcntl_waitpid;

/**
 * Липовый обработчик 10 тыс. лидов генератора.
 */
class DummyLeadsHandler implements LeadsHandlerInterface
{
    /** @var int кол-во обрабатываемых заявок */
    public const LEADS_COUNT = 10000;

    /** @var int кол-во потоков обработки */
    public const THREADS_COUNT = 50;

    /** @var int время обработки одного лида, сек */
    public const PROCESS_TIME = 2;

    /** @var ?LoggerInterface логгер */
    private $_logger;

    /**
     * Конструктор.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->_logger = $logger;

        $this->initQueue();
    }

    /**
     * Получить текущий логгер.
     *
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->_logger;
    }

    /**
     * Установить новый логгер.
     *
     * @param LoggerInterface|null $logger
     * @return $this
     */
    public function setLogger(?LoggerInterface $logger): static
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @return bool родительский процесс
     */
    public function processLeadsQueue(Generator $generator): bool
    {
        echo 'Запуск обработки ' . self::LEADS_COUNT . " лидов ...\n";

        /** @var bool $isChild дочерний процесс */
        $isChild = false;

        // размножаемся
        for ($i = 0; $i < self::THREADS_COUNT; $i++) {
            $pid = pcntl_fork();
            if ($pid < 0) {
                throw new RuntimeException('Ошибка создания потомка');
            }

            // дочерний процесс выходит из цикла
            if (empty($pid)) {
                $isChild = true;
                break;
            }
        }

        // родительский и дочерние выполняют разную работу
        if ($isChild) {
            $this->childWorker();
        } else {
            $this->parentWorker($generator);
        }

        return ! $isChild;
    }

    /** @var int ключ очереди сообщений */
    private $_queueKey;

    /**
     * Инициализация очереди.
     */
    private function initQueue(): void
    {
        // инициируем ключ очереди
        $this->_queueKey = ftok(__FILE__, 'd');

        // если очередь осталась от прошлых запусков, то удаляем
        if (msg_queue_exists($this->_queueKey)) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $queue = @msg_get_queue($this->_queueKey);
            if ($queue) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                @msg_remove_queue($queue);
            }
        }
    }

    /** @var SysvMessageQueue */
    private $_queue;

    /**
     * Подключение к очереди.
     *
     * @return SysvMessageQueue
     */
    private function queue(): SysvMessageQueue
    {
        if ($this->_queue === null) {
            $this->_queue = msg_get_queue($this->_queueKey);
            if ($this->_queue === false) {
                throw new RuntimeException('Ошибка создания очереди');
            }
        }

        return $this->_queue;
    }

    /**
     * Работа родительского процесса по отправке лидов в очередь.
     *
     * @param Generator $generator
     */
    private function parentWorker(Generator $generator): void
    {
        cli_set_process_title('LeadsTest родительский процесс');

        // подключаемся к очереди сообщений
        $queue = $this->queue();

        try {
            // отправляем в очередь лиды
            $generator->generateLeads(self::LEADS_COUNT, function(Lead $lead) use ($queue) {
                if (! msg_send($queue, 1, $lead)) {
                    throw new RuntimeException('Ошибка отправки в очередь');
                }
            });

            // ожидаем завершения потомков
            $status = null;
            while (pcntl_waitpid(0, $status, WNOHANG) !== -1) {
                // отправляем null для сигнализации окончания очереди
                if (! msg_send($queue, 1, null)) {
                    throw new RuntimeException('Ошибка отправки в очередь');
                }

                // спим 0.1 s, чтобы не грузить процессор
                usleep(100000);
            }
        } finally {
            // закрываем очередь
            if (! msg_remove_queue($queue)) {
                throw new RuntimeException('Ошибка закрытия очереди');
            }
        }
    }

    /**
     * Работа дочернего процесса по обработке лидов из очереди
     */
    private function childWorker(): void
    {
        cli_set_process_title('LeadsTest дочерний процесс');

        // получаем очередь
        $queue = $this->queue();

        // обрабатываем очередь до завершения
        while (true) {
            /** @var ?Lead $lead */
            $lead = null;
            $msgType = null;

            // получаем лида из очереди
            if (! msg_receive($queue, 0, $msgType, 10000, $lead)) {
                throw new RuntimeException('Ошибка чтения из очереди');
            }

            // окончание очереди
            if ($lead === null) {
                break;
            }

            // симулируем активную работу над лидом ;p)
            sleep(self::PROCESS_TIME);

            // пишем отчет о фиктивной работе :p)
            $this->_logger?->log(implode('|', [
                $lead->id, $lead->categoryName, date('Y-m-d H:i:s')
            ]));
        }
    }
}
