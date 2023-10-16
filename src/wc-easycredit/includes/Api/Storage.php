<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

class Storage implements \Teambank\RatenkaufByEasyCreditApiV3\Integration\StorageInterface
{
    protected $key = 'wc_easycredit';

    protected $session;

    protected $logger;

    public function __construct($session, $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    public function set($key, $value): self
    {
        $this->logger->debug('storage::set ' . $key . ' = ' . $value);
        $this->session->set('easycredit[' . $key . ']', $value);

        $data = $this->session->get($this->key);
        if (!\is_array($data)) {
            $data = [];
        }
        $data[$key] = $value;
        $this->session->set($this->key, $data);

        return $this;
    }

    public function get($key)
    {
        if (!$this->session) {
            return null;
        }
        $data = $this->session->get($this->key);
        if (isset($data[$key])) {
            $this->logger->debug('storage::get ' . $key . ' = ' . $data[$key]);
            return $data[$key];
        }
    }

    public function clear(): self
    {
        $backtrace = \debug_backtrace();
        $this->logger->info('storage::clear from ' . $backtrace[1]['class'] . ':' . $backtrace[1]['function']);

        $this->session->set($this->key, null);
        return $this;
    }
}
