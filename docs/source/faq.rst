.. role:: latex(raw)
   :format: latex

Häufige Fragen
============================

Die Bestellbestätigungs E-Mail wird bereits bei Weiterleitung auf das Payment Terminal von easyCredit-Ratenkauf versendet. Lässt sich dies nach hinten verschieben?
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

Die Button-Bezeichnung im Checkout verändert sich nicht auf "Weiter zur Ratenzahlung" nach Auswahl von easyCredit-Ratenkauf. Woran liegt dies?
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