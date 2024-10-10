.. role:: latex(raw)
   :format: latex

Häufige Fragen
============================

Seit v2.0 wird die Transaktions-ID in der Bestellung nicht mehr gesetzt und der Bestellstatus bleibt auf "Zahlung ausstehend" stehen
-------------------------------------------------------------------------------------------------------------------------------------

Ab dieser Version enthält das Plugin die sog. Zwei-Phasen Bestätigung. Dabei ist eine eine Zahlungstransaktion in wooCommerce nicht mehr mit der Bestätigung des Kunden abgeschlossen, sondern es erfolgt eine weitere Bestätigung seitens des easyCredit Servers. Der Server ruft im Anschluss der Bestellung die URL `/easycredit/authorize` auf. Bitte prüfen Sie die Logs Ihres Web-Servers auf den folgenden Aufruf, der kurz nach der Bestellung eingehen sollte. Enthält der Aufruf den Status-Code 200, ist die Transaktion auf "in Bearbeitung" umgestellt und die Transaktions-ID ist der Bestellung zugeordnet:

.. code-block::

    127.0.0.1 - - [11/Nov/2011:11:11:11 +0200] "GET /easycredit/authorize/secToken/{secToken}/?transactionId={txId}&orderId={orderId} HTTP/1.1" 200 - mein-woocommerce-shop.de "-" "Java/1.0.0" "-

.. note:: Dieser Aufruf funktioniert möglicherweise nicht in Passwort-geschützten Staging- oder Entwicklungsumgebungen, wenn diese URL nicht explizit aus der Authentifizierung ausgeschlossen wird.

Die Bestellbestätigungs E-Mail wird bereits bei Weiterleitung auf das Payment Terminal von easyCredit versendet. Lässt sich dies nach hinten verschieben?
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Das Problem hängt möglicherweise mit einem der folgenden Plugins zusammen:

 * **wooCommerce Germanized**
 * **German Market**

Die Plugins verändern den E-Mail Versand in wooCommerce derart, dass die E-Mail direkt nach Absenden des Checkouts versandt wird. Die E-Mail wird dabei unabhängig von der Zahlung versandt. 

Fehlerbehebung bei Verwendung von wooCommerce Germanized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Das Problem ist im Forum des Plugins beschrieben: 

* https://wordpress.org/support/topic/order-receipt-sent-before-payment-confirmation/

Als Lösung schlägt der Plugin Hersteller vor, die Funktion mittels Hook in der functions.php des verwendeten Themes zu deaktivieren:

.. code-block:: php

   add_filter( 'woocommerce_gzd_instant_order_confirmation', 
      'my_child_disable_instant_order_confirmation', 1, 10 );

   function my_child_disable_instant_order_confirmation( $disable ) {
      return false;
   }

Fehlerbehebung bei Verwendung von German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Im Plugin **German Market** hat der Hersteller die Funktion konfigurierbar gemacht. Das Verhalten kann unter Allgemein -> Emails -> Bestelleingangsbestätigungsmail konfiguriert werden.

Die Button-Bezeichnung im Checkout verändert sich nicht auf "Weiter zur Ratenzahlung" nach Auswahl von easyCredit. Woran liegt dies?
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Das Problem hängt möglicherweise mit einem der folgenden Plugins zusammen:

 * **wooCommerce Germanized**
 * **German Market**
 
Leider haben beide Hersteller die Funktionalität nicht konfigurierbar gemacht. 
 
Fehlerbehebung bei Verwendung von wooCommerce Germanized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Bei Verwendung von **wooCommerce Germanized** kann das Standard-Verhalten mit dem folgenden Hook in der functions.php wiederhergestellt werden:

.. code-block:: php

   add_filter( 'woocommerce_available_payment_gateways', 'my_child_allow_gateway_button_text', 10, 1 );

   function my_child_allow_gateway_button_text( $gateways ) {
      foreach( $gateways as $key => $gateway ) {
         /**
            * By adding this property Germanized won't override the button text.
         */
         if ($gateway->id === 'ratenkaufbyeasycredit') {
            $gateway->force_order_button_text = false;
         }
      }

      return $gateways;
   }

Fehlerbehebung bei Verwendung von German Market
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Bei Verwendung von **German Market** kann das Standard-Verhalten mit dem folgenden Hook in der functions.php wiederhergestellt werden:

.. code-block:: php

    remove_action( 'woocommerce_before_template_part',
        array( 'WGM_Helper', 'change_payment_gateway_order_button_text' ), 99, 4 );

weitere Fragen
---------------
Bei weiteren konkreten Fragen oder Hilfestellung bei der Integration wenden Sie sich bitte an den Support:

* https://www.easycredit-ratenkauf.de/
