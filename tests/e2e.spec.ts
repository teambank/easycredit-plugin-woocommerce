import { test, expect } from '@playwright/test';

function delay(time) {
  return new Promise(function(resolve) {
      setTimeout(resolve, time)
  });
}

test.beforeEach(async ({page}, testInfo) => {
  await page.evaluate(() => {
    document.body.style.transform = 'scale(0.75)'
  })
})

test.afterEach(async ({ page }, testInfo) => {
  if (testInfo.status !== testInfo.expectedStatus) {
    // Get a unique place for the screenshot.
    const screenshotPath = testInfo.outputPath(`failure.png`);
    // Add it to the report.
    testInfo.attachments.push({ name: 'screenshot', path: screenshotPath, contentType: 'image/png' });
    // Take the screenshot itself.
    await page.screenshot({ path: screenshotPath, timeout: 5000 });
  }
});

const randomize = (name, num = 3) => {
  for (let i = 0; i < num; i++) {
    name += String.fromCharCode(97+Math.floor(Math.random() * 26));
  }
  return name 
}

const goThroughPaymentPage = async (page, 
  { express = false, selectedInstallments = null }: { express?: boolean, selectedInstallments?: number | null }
) => {
  await test.step(`easyCredit-Ratenkauf Payment`, async() => {
    await page.getByTestId('uc-deny-all-button').click()

    if (selectedInstallments) {
      await expect(page.locator('.slider-label-bottom')).toHaveText(`in ${selectedInstallments} Raten`) // check if financingTerm is passed correctly
    }

    await page.getByRole('button', { name: 'Weiter zur Dateneingabe' }).click()

    if (express) {
      await page.locator('#vorname').fill(randomize('Ralf'));
      await page.locator('#nachname').fill('Ratenkauf');
    }

    await page.locator('#geburtsdatum').fill('05.04.1972')

    if (express) {
      await page.locator('#email').fill('ralf.ratenkauf@teambank.de')

    }
    await page.locator('#mobilfunknummer').fill('015112345678')
    await page.locator('#iban').fill('DE12500105170648489890')

    if (express) {
      await page.locator('#strasseHausNr').fill('Beuthener Str. 25')
      await page.locator('#plz').fill('90471')
      await page.locator('#ort').fill('Nürnberg')
    }

    await page.getByText('Allen zustimmen').click()

    await delay(500)
    await page.getByRole('button', { name: 'Ratenwunsch prüfen' }).click()

    await delay(500)
    await page.getByRole('button', { name: 'Ratenwunsch übernehmen' }).click()
  })
}

const confirmOrder = async (page) => {
  await test.step(`Confirm order`, async() => {

   //await page.getByText('gelesen und stimme ihnen zu').click()

    await page.getByRole('button', { name: 'pflichtig bestellen' }).click()

    /* Success Page */
    await expect(page).toHaveURL(/order-received/);
  })
}

const goToProduct = async (page, sku = 'test') => {
  await test.step(`Go to product (sku: ${sku}}`, async() => {
    await page.goto(`/index.php/produkt/${sku}/`);
  })
}

const isBlocksCheckout  = () => {
  return (process.env.VERSION.localeCompare('8.3', undefined, { numeric: true, sensitivity: 'base' }) >= 0);
}

test('legacyCheckout', async ({ page }) => {

  if (isBlocksCheckout()) {
    return
  }

  await goToProduct(page)

  await page.getByRole('button', { name: 'In den Warenkorb' }).click();
  await page.goto('index.php/checkout/')
}

const fillCheckout = async (page) => {
  await page.getByRole('textbox', { name: 'Vorname *' }).fill(randomize('Ralf'))
  await page.getByRole('textbox', { name: 'Nachname *' }).fill('Ratenkauf');
  await page.getByRole('textbox', { name: 'Straße *' }).fill('Beuthener Str. 25');
  await page.getByRole('textbox', { name: 'Postleitzahl *' }).fill('90471');
  await page.getByRole('textbox', { name: 'Ort / Stadt *' }).fill('Nürnberg');
  await page.getByRole('textbox', { name: 'Telefon *' }).fill('012345678');
  await page.getByLabel('E-Mail-Adresse *').fill('ralf.ratenkauf@teambank.de');
}

test('standardCheckoutInstallmentsPayment', async ({ page }) => {

  await goToProduct(page)

  await page.getByRole('button', { name: 'In den Warenkorb' }).click();
  await page.goto('index.php/checkout/')

  await fillCheckout(page);

  /* Payment Selection */
  await page.locator('label').getByTestId('easycredit-ratenkauf').click();
  await page.locator('easycredit-checkout').getByText('2 Monate', { exact: true }).click(); // select financingTerm
  await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zum Ratenkauf' }).click();
  await page.locator('span:text("Akzeptieren"):visible').click();

  await goThroughPaymentPage(page, { express: false, selectedInstallments: 2 })
  await confirmOrder(page)
});

test('standardCheckoutBillPayment', async ({ page }) => {

  await goToProduct(page)

  await page.getByRole('button', { name: 'In den Warenkorb' }).click();
  await page.goto('index.php/checkout/')

  await fillCheckout(page);

  /* Payment Selection */
  await page.locator('label').getByTestId('easycredit-rechnung').click();
  await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zum Rechnungskauf' }).click();

  await goThroughPaymentPage(page, {})
  await confirmOrder(page)
});

test('blocksCheckout', async ({ page }) => {

  if (!isBlocksCheckout()) {
    return
  }

  await goToProduct(page)

  await page.getByRole('button', { name: 'In den Warenkorb' }).click();
  await page.goto('index.php/checkout/')

  await page.getByLabel('E-Mail-Adresse').fill('ralf.ratenkauf@teambank.de');
  await page.getByRole('textbox', { name: 'Vorname' }).fill(randomize('Ralf'))
  await page.getByRole('textbox', { name: 'Nachname' }).fill('Ratenkauf');
  await page.getByRole('textbox', { name: 'Adresse', exact: true }).fill('Beuthener Str. 25');
  await page.getByRole('textbox', { name: 'Postleitzahl' }).fill('90471');
  await page.getByRole('textbox', { name: 'Stadt' }).fill('Nürnberg');
  await page.getByRole('textbox', { name: 'Telefon (optional)' }).fill('012345678');

  await expect(page.locator('.wc-block-components-checkout-place-order-button')).not.toBeDisabled();

  // Checkout Page
  await page.locator('easycredit-checkout-label').click()
  await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zum Ratenkauf' }).click();
  await page.locator('span:text("Akzeptieren"):visible').click();

  await goThroughPaymentPage(page)
  await confirmOrder(page)
});

test('expressCheckout', async ({ page }) => {

  await goToProduct(page)

  await page.locator('a').filter({ hasText: 'Jetzt direkt in Raten zahlen' }).click();
  await page.getByText('Akzeptieren', { exact: true }).click();

  await goThroughPaymentPage(page, { express: true })
  await confirmOrder(page)
});

test('expressCheckoutWithVariableProduct', async ({ page }) => {

  await goToProduct(page,'variable')

  await page.getByLabel('Size').selectOption('');
  await expect(page.locator('easycredit-express-button')).not.toBeVisible();

  await page.getByLabel('Size').selectOption('medium');
  await expect(page.locator('easycredit-express-button')).not.toBeVisible();

  await page.getByLabel('Size').selectOption('small');
  await expect(page.locator('easycredit-express-button')).toBeVisible();

  await page.locator('a').filter({ hasText: 'Jetzt direkt in Raten zahlen' }).click();
  await page.getByText('Akzeptieren', { exact: true }).click();

  await goThroughPaymentPage(page, true)
  await confirmOrder(page)
});

test('settingsCheck', async ({ page }) => {

  await page.goto('/wp-admin/')

  await page.getByLabel('Benutzername oder E-Mail-Adresse').fill('admin')
  await page.getByLabel('Passwort', { exact: true }).fill('password')

  await page.getByRole('button', { name: 'Anmelden' }).click();

  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=easycredit')

  page.on('dialog', async (dialog) => {
    expect(dialog.message()).toContainText('Die Zugangsdaten sind korrekt')
    await dialog.accept()
  })
  await page.locator('#woocommerce_easycredit_api_verify_credentials').click()
});
