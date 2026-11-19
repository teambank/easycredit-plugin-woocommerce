<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit {
    if (!class_exists(Plugin::class, false)) {
        class Plugin
        {
            public function is_easycredit_method($method): bool
            {
                return is_string($method) && strpos($method, 'easycredit_') === 0;
            }

            public function integration()
            {
                throw new \RuntimeException('not needed');
            }
        }
    }
}

namespace {
    use Netzkollektiv\EasyCredit\Compatibility\GermanizedCompatibility;
    use Netzkollektiv\EasyCredit\Plugin;
    use PHPUnit\Framework\TestCase;

    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
        {
            return true;
        }
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
        {
            return true;
        }
    }

    if (!function_exists('did_action')) {
        function did_action($hook)
        {
            return $GLOBALS['test_did_action'][$hook] ?? 0;
        }
    }

    if (!function_exists('wc_get_order')) {
        function wc_get_order($order_id)
        {
            return $GLOBALS['test_wc_orders'][(int) $order_id] ?? false;
        }
    }

    if (!function_exists('WC_germanized')) {
        function WC_germanized()
        {
            return $GLOBALS['test_wc_germanized'] ?? null;
        }
    }

    if (!class_exists('WC_Order', false)) {
        class WC_Order
        {
            private int $id;
            private string $payment_method;
            private bool $needs_payment;

            public function __construct(int $id, string $payment_method, bool $needs_payment)
            {
                $this->id = $id;
                $this->payment_method = $payment_method;
                $this->needs_payment = $needs_payment;
            }

            public function get_id(): int
            {
                return $this->id;
            }

            public function get_payment_method(): string
            {
                return $this->payment_method;
            }

            public function needs_payment(): bool
            {
                return $this->needs_payment;
            }
        }
    }

    require_once dirname(__DIR__) . '/src/wc-easycredit/includes/RedirectContext.php';
    require_once dirname(__DIR__) . '/src/wc-easycredit/includes/Compatibility/GermanizedCompatibility.php';

    final class GermanizedCompatibilityEmailsTest extends TestCase
    {
        private GermanizedCompatibility $compatibility;

        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['test_wc_orders'] = [];
            $GLOBALS['test_did_action'] = [];
            $GLOBALS['test_confirm_order_calls'] = [];
            $GLOBALS['test_wc_germanized'] = null;

            $reflection = new \ReflectionClass(GermanizedCompatibility::class);
            $this->compatibility = $reflection->newInstanceWithoutConstructor();
            $pluginProperty = $reflection->getProperty('plugin');
            $pluginProperty->setAccessible(true);
            $pluginProperty->setValue($this->compatibility, new Plugin());
        }

        public function test_unpaid_easycredit_order_delays_confirmation(): void
        {
            $order = new WC_Order(42, 'easycredit_rechnung', true);
            $GLOBALS['test_wc_orders'][42] = $order;

            $this->assertFalse(
                $this->compatibility->delay_until_payment_complete(true, $order)
            );
        }

        public function test_stale_pending_context_allows_confirmation_after_payment_complete(): void
        {
            $stalePending = new WC_Order(42, 'easycredit_rechnung', true);
            $paidFresh = new WC_Order(42, 'easycredit_rechnung', false);
            $GLOBALS['test_wc_orders'][42] = $paidFresh;

            $this->assertTrue(
                $this->compatibility->delay_until_payment_complete(true, $stalePending)
            );
        }

        public function test_non_easycredit_order_is_not_delayed(): void
        {
            $order = new WC_Order(7, 'paypal', true);
            $GLOBALS['test_wc_orders'][7] = $order;

            $this->assertTrue(
                $this->compatibility->delay_until_payment_complete(true, $order)
            );
        }

        public function test_delayed_confirmation_is_sent_when_germanized_skipped(): void
        {
            $order = new WC_Order(99, 'easycredit_ratenkauf', false);
            $GLOBALS['test_wc_orders'][99] = $order;
            $GLOBALS['test_did_action']['woocommerce_germanized_order_confirmation_sent'] = 0;
            $GLOBALS['test_wc_germanized'] = (object) [
                'emails' => new class {
                    public function trigger_order_confirmation_emails($order, $order_id): void
                    {
                        unset($order);
                        $GLOBALS['test_confirm_order_calls'][] = $order_id;
                    }
                },
            ];

            $this->compatibility->send_delayed_confirmation_if_needed(99);

            $this->assertSame([99], $GLOBALS['test_confirm_order_calls']);
        }

        public function test_delayed_confirmation_skips_when_already_sent(): void
        {
            $order = new WC_Order(99, 'easycredit_ratenkauf', false);
            $GLOBALS['test_wc_orders'][99] = $order;
            $GLOBALS['test_did_action']['woocommerce_germanized_order_confirmation_sent'] = 1;
            $GLOBALS['test_wc_germanized'] = (object) [
                'emails' => new class {
                    public function trigger_order_confirmation_emails($order, $order_id): void
                    {
                        unset($order, $order_id);
                        $GLOBALS['test_confirm_order_calls'][] = $order;
                    }
                },
            ];

            $this->compatibility->send_delayed_confirmation_if_needed(99);

            $this->assertSame([], $GLOBALS['test_confirm_order_calls']);
        }
    }
}
