.. role:: latex(raw)
   :format: latex

Häufige Fragen
============================

Die Bestellbestätigungs E-Mail wird bereits bei Weiterleitung auf das Payment Terminal von easyCredit versendet. Lässt sich dies nach hinten verschieben?
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Das Problem hängt möglicherweise mit einem der folgenden Plugins zusammen:

 * **wooCommerce Germanized**
 * **German Market**

Die Plugins verändern den E-Mail Versand in wooCommerce derart, dass die E-Mail direkt nach Absenden des Checkouts versandt wird. Die E-Mail wird dabei unabhängig von der Zahlung versandt.

Fehlerbehebung bei Verwendung von wooCommerce Germanized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Ab Plugin-Version **3.1.3** verzögert easyCredit den Versand der Germanized-Bestellbestätigung automatisch, bis die Zahlung abgeschlossen ist. Ein manueller Eingriff in der ``functions.php`` ist dafür nicht mehr nötig.

Weitere Informationen zum ursprünglichen Verhalten finden Sie im Forum des Plugins:

* https://wordpress.org/support/topic/order-receipt-sent-before-payment-confirmation/

Fehlerbehebung bei Verwendung von German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Im Plugin **German Market** hat der Hersteller die Funktion konfigurierbar gemacht. Das Verhalten kann unter **Allgemein → E-Mails → Bestelleingangsbestätigungsmail** konfiguriert werden. easyCredit verschiebt diese E-Mail bei German Market nicht automatisch.

Rechtstexte oder AGB müssen vor der Weiterleitung zur Finanzierung bestätigt werden
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Beim easyCredit-Ratenkauf wählt der Kunde zunächst einen Finanzierungsplan und wird danach zur Zahlungsseite weitergeleitet. Pflicht-Rechtstexte und die WooCommerce-AGB-Checkbox sollen dabei erst nach Rückkehr mit freigegebenem Finanzierungsplan abgefragt werden, nicht schon beim ersten Klick auf „Weiter zur Ratenzahlung“.

Ab Plugin-Version **3.1.5** übernimmt easyCredit das automatisch für:

 * **wooCommerce Germanized** (Classic Checkout und Blocks Checkout)
 * **WooCommerce German Market** (Classic Checkout und Blocks Checkout)
 * die **native WooCommerce-AGB-Checkbox** (Classic Checkout und Blocks Checkout)

Hinweise zu WooCommerce Germanized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Ab Plugin-Version **3.1.3** verzögert easyCredit zusätzlich den Versand der Germanized-Bestellbestätigung automatisch, bis die Zahlung abgeschlossen ist.

Die Classic- und Blocks-Checkout-Integration wurde in CI mit Germanized **4.0.9** unter WooCommerce **10.9.2** geprüft.

Hinweise zu WooCommerce German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Im **Blocks-Checkout** rendert German Market eigene Pflicht-Checkboxen über den Block ``german-market/checkout-checkboxes`` (z. B. AGB/Datenschutz/Widerruf und Versanddienstleister). easyCredit unterdrückt deren Validierung beim ersten Redirect zur Finanzierungsseite und fordert die Zustimmung erst nach Rückkehr mit freigegebenem Finanzierungsplan vor „Zahlungspflichtig bestellen“.

Im **Classic Checkout** greifen die German-Market-Validierungshooks für dieselben Pflicht-Checkboxen, einschließlich der Versanddienstleister-Zustimmung. Auch dort werden die Checkboxen bis nach der Finanzierungsfreigabe nicht erzwungen; die Client-Validierung im Checkout berücksichtigt German-Market-Felder beim ersten Redirect ebenfalls nicht.

Die Bestellbestätigungs-E-Mail verzögert easyCredit bei German Market **nicht** automatisch (siehe Abschnitt oben).

Classic- und Blocks-Checkout wurden manuell mit German Market **3.60** unter WooCommerce **10.9.2** und WordPress **7.0** verifiziert.

Steuern auf Zinsgebühren mit German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**German Market** kann Umsatzsteuer auch auf nicht steuerbare Gebührenpositionen anwenden. Ab Plugin-Version **3.1.2** behandelt easyCredit die Zinsgebühr dabei automatisch korrekt, sodass keine falsche Steuer auf der Zinsposition ausgewiesen wird.

Die Button-Bezeichnung im Checkout verändert sich nicht auf "Weiter zur Ratenzahlung" nach Auswahl von easyCredit. Woran liegt dies?
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Das Problem hängt möglicherweise mit einem der folgenden Plugins zusammen:

 * **wooCommerce Germanized**
 * **German Market**

Leider haben beide Hersteller die Funktionalität nicht konfigurierbar gemacht.

Im **Blocks Checkout** setzt easyCredit die Beschriftung über die Zahlungsart-Konfiguration (``placeOrderButtonLabel``). Dort sollte nach Auswahl von easyCredit-Ratenkauf „Weiter zur Ratenzahlung“ erscheinen.

Fehlerbehebung bei Verwendung von wooCommerce Germanized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Im **Classic Checkout** kann Germanized die Gateway-Beschriftung überschreiben. Bei Verwendung von **wooCommerce Germanized** kann das Standard-Verhalten mit dem folgenden Hook in der ``functions.php`` wiederhergestellt werden:

.. code-block:: php

   add_filter( 'woocommerce_available_payment_gateways', 'my_child_allow_gateway_button_text', 10, 1 );

   function my_child_allow_gateway_button_text( $gateways ) {
      foreach( $gateways as $key => $gateway ) {
         /**
            * By adding this property Germanized won't override the button text.
         */
         if ($gateway->id === 'easycredit_ratenkauf' || $gateway->id === 'easycredit_rechnung') {
            $gateway->force_order_button_text = false;
         }
      }

      return $gateways;
   }

Fehlerbehebung bei Verwendung von German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Bei Verwendung von **German Market** kann das Standard-Verhalten im **Classic Checkout** mit dem folgenden Hook in der ``functions.php`` wiederhergestellt werden:

.. code-block:: php

    remove_action( 'woocommerce_before_template_part',
        array( 'WGM_Helper', 'change_payment_gateway_order_button_text' ), 99, 4 );

weitere Fragen
---------------
Bei weiteren konkreten Fragen oder Hilfestellung bei der Integration wenden Sie sich bitte an den Support:

* https://www.easycredit-ratenkauf.de/
