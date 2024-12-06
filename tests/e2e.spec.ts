import { test, expect } from '@playwright/test';

function delay(time) {
  return new Promise(function(resolve) {
      setTimeout(resolve, time)
  });
}

export async function clickWithRetry(locator, maxRetries = 3) {
  let attempt = 0;
  while (attempt < maxRetries) {
    try {
      await locator.click();
      return;
    } catch (e) {
      console.error(`Click failed on attempt ${attempt + 1}: ${e.message}`);
      attempt++;
      if (attempt === maxRetries) {
        throw new Error(`Max retries reached, click failed: ${e.message}`);
      }
    }
  }
};

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

const goThroughPaymentPage = async (page, express: boolean = false) => {
  await test.step(`easyCredit-Ratenkauf Payment`, async () => {
    await page.getByTestId("uc-deny-all-button").click();

    await page.getByRole("button", { name: "Weiter zur Dateneingabe" }).click();

    if (express) {
      await page.locator("#firstName").fill(randomize("Ralf"));
      await page.locator("#lastName").fill("Ratenkauf");
    }

    await page.locator("#dateOfBirth").fill("05.04.1972");

    if (express) {
      await page.locator("#email").getByRole('textbox').fill("ralf.ratenkauf@teambank.de");
    }

    await page.locator("tbk-vorwahldropdown .tel-wrapper").click();
    await page.locator('tbk-vorwahldropdown').locator('li').filter({ hasText: '+49' }).getByRole('paragraph').click();
    await page.locator('#mobilfunknummer').getByRole('textbox').fill('1703404848');
    await page.locator('app-ratenkauf-iban-input-dumb').getByRole('textbox').fill("DE12500105170648489890");

    if (express) {
      await page.locator("#streetAndNumber").fill("Beuthener Str. 25");
      await page.locator("#postalCode").fill("90402");
      await page.locator("#city").fill("Nürnberg");
    }

    await page.locator("#agreeAll").click();

    await delay(500);

    await clickWithRetry(
      page.getByRole("button", { name: "Zahlungswunsch prüfen" })
    );

    await delay(500);
    await page.getByRole("button", { name: "Zahlungswunsch übernehmen" }).click();
  });
};

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

  await page.getByRole('textbox', { name: 'Vorname *' }).fill(randomize('Ralf'))
  await page.getByRole('textbox', { name: 'Nachname *' }).fill('Ratenkauf');
  await page.getByRole('textbox', { name: 'Straße *' }).fill('Beuthener Str. 25');
  await page.getByRole('textbox', { name: 'Postleitzahl *' }).fill('90471');
  await page.getByRole('textbox', { name: 'Ort / Stadt *' }).fill('Nürnberg');
  await page.getByRole('textbox', { name: 'Telefon *' }).fill('012345678');
  await page.getByLabel('E-Mail-Adresse *').fill('ralf.ratenkauf@teambank.de');

  
  /* Confirm Page */
  await page.locator('easycredit-checkout-label').click()
  await page.locator('easycredit-checkout').getByRole('button', { name: 'Weiter zu easyCredit-Ratenkauf' }).click();

  await goThroughPaymentPage(page)
  await confirmOrder(page)
});

/*
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

  await goThroughPaymentPage(page)
  await confirmOrder(page)
});
*/

test('expressCheckout', async ({ page }) => {

  await goToProduct(page)

  await page.locator('a').filter({ hasText: 'Jetzt direkt in Raten zahlen' }).click();
  await page.getByText('Akzeptieren', { exact: true }).click();

  await goThroughPaymentPage(page, true)
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

  //await page.locator('#toplevel_page_woocommerce').getByRole('link', { name: 'Einstellungen' }).click();
  //await page.getByRole('link', { name: 'Zahlungen' }).click();
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout')

  //await page.getByRole('link', { name: 'easyCredit-Ratenkauf', exact: true }).click();
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=ratenkaufbyeasycredit')

  page.on('dialog', async (dialog) => {
    expect(dialog.message()).toContainText('Die Zugangsdaten sind korrekt')
    await dialog.accept()
  })
  //await page.getByRole('button', { name: 'Zugangsdaten überprüfen' }).click();
  await page.locator('#woocommerce_ratenkaufbyeasycredit_api_verify_credentials').click()

});
