======================
Verwendung für Händler
======================

Eine neue Bestellung eines Kunden ist nach ihrem Eingang in WooCommerce gegenüber ratenkauf by easyCredit genehmigt. Das heisst, die Zahlung und Bestellung wurde von ratenkauf by easyCredit gegenüber dem Kunden bestätigt. Eine Auszahlung durch ratenkauf by easyCredit an den Händler erfolgt erst, wenn der Händler die Lieferung der bestellten Artikel gemeldet hat. Dies erfolgt in WooCommerce entweder durch eine Bestätigung über die Transaktionsverwaltung oder durch eine Änderung des Bestellstatus gemäß folgender Einstellungen.

Alternativ ist die weitere Verwaltung des Transaktionsstatus über das `Händler-Interface <https://app.easycredit.de>`_ möglich.

Transaktionsmanager
-------------------

Der Transaktionsmanager in der Detailansicht einer mit ratenkauf by easyCredit bezahlten Bestellung zeigt eine Übersicht über die zur Bestellung gehörende Zahlungstransaktion und deren Historie. Über den Transaktionsmanager kann der Transaktionsstatus aus der Wordpress Administration heraus direkt an ratenkauf by easyCredit gemeldet werden.

.. image:: ./_static/merchant-tx-manager.png

Statusmeldung über den Transaktionsmanager
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Zur Meldung des Transaktionsstatus wählen Sie den gewünschten Status und senden Sie das Formular ab. Der Transaktionsmanager aktualisiert nach Absenden die Historie und die Transaktionsdetails oberhalb.

Bei Rückabwicklung wählen Sie bitte den Grund und geben Sie bei einer Teil-Rückabwicklung den entsprechenden Betrag ein.

.. image:: ./_static/merchant-tx-manager-options.png
           :scale: 50%

Statusmeldung über die Bestellverarbeitung
----------------------------------------------------

Neben der expliziten Meldung über den :ref:`Transaktionsmanager` integriert das Plugin die Statusmeldung auch in die Bestellverarbeitung von wooComnmerce. Bei der Änderung des Bestellstatus meldet das Plugin den Status implizit je nach Einstellung im Plugin.

Lieferung melden
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Wird der Status einer Bestellung in WooCommerce auf den eingestellen Status verändert, wird die Transaktion als "ausgeliefert" an ratenkauf by easyCredit gemeldet. Die Änderung ist im Transaktionsmanager ersichtlich.

.. note:: Die automatische Meldung entspricht dem Status "Lieferung melden" über den Transaktionsmanager.

Rückabwicklung
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Wird der Status einer Bestellung in WooCommerce auf den eingestellen Status verändert, wird die Transaktion widerrufen bzw. rückgängig gemacht und an ratenkauf by easyCredit gemeldet. Die Änderung ist im Transaktionsmanager ersichtlich.

.. note:: Die automatische Meldung entspricht dem Status "Widerruf vollständig" über den Transaktionsmanager.

Anzeige des Transaktionsstatus
--------------------------------------

Der Transaktionsstatus kann einen der folgenden Werte annehmen:

* Wartend: die Transaktion ist noch nicht verfügbar. Es kann bis zu einem Tag dauern bis die Transaktion verfügbar ist.
* Lieferung melden: Die Transaktion ist vorhanden. Die Lieferung kann gemeldet werden.
* In Abrechnung: Die Lieferung wurde gemeldet. Die Auszahlung an den Händler wird bearbeitet.
* Abgerechnet: Die Auszahlung an den Händler ist erfolgt.
* Rückerstattet: Die Transaktion wurde widerrufen.