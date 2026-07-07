import { randomUUID } from "node:crypto";
import { expect } from "@playwright/test";
import { randomize } from "../helpers/utils";
import { PaymentTypes } from "../helpers/types";

export const PAYMENT_API_HOST = "https://ratenkauf.easycredit.de";

/** Must stay below the Playwright test timeout (60s) so failures surface in-step. */
export const PAYMENT_REDIRECT_TIMEOUT_MS = 25_000;
export const PAYMENT_RETURN_TIMEOUT_MS = 25_000;

const PAYMENT_URL_PATTERN = /ratenkauf\.easycredit\.de/i;
const RETURN_URL_PATTERN = /easycredit\/return|checkout/i;

const CHECKOUT_FAILURE_PATTERNS: Array<{ pattern: RegExp; message: string }> = [
	{
		pattern: /ist ausverkauft und kann nicht gekauft werden/i,
		message: "Product is sold out on checkout",
	},
	{
		pattern: /No event handler handled the submit event/i,
		message: "easyCredit checkout widget submit was not handled",
	},
];

async function collectCheckoutFailureReason(page: any): Promise<string | null> {
	const bodyText = await page.locator("body").innerText().catch(() => "");
	for (const { pattern, message } of CHECKOUT_FAILURE_PATTERNS) {
		if (pattern.test(bodyText)) {
			return message;
		}
	}

	const wcErrors = await page
		.locator(".woocommerce-error li")
		.allTextContents()
		.catch(() => []);
	if (wcErrors.length > 0) {
		return `WooCommerce checkout errors: ${wcErrors.join("; ")}`;
	}

	return null;
}

async function waitForPaymentRedirect(
	page: any,
	timeout = PAYMENT_REDIRECT_TIMEOUT_MS
): Promise<void> {
	const deadline = Date.now() + timeout;

	while (Date.now() < deadline) {
		if (PAYMENT_URL_PATTERN.test(page.url())) {
			return;
		}

		const failureReason = await collectCheckoutFailureReason(page);
		if (failureReason) {
			throw new Error(
				`Payment redirect failed (${failureReason}). URL: ${page.url()}`
			);
		}

		await page.waitForTimeout(250);
	}

	const failureReason = await collectCheckoutFailureReason(page);
	throw new Error(
		`Timed out after ${timeout}ms waiting for redirect to easyCredit payment page. URL: ${page.url()}${
			failureReason ? `. ${failureReason}` : ""
		}`
	);
}

async function waitForPaymentReturn(
	page: any,
	timeout = PAYMENT_RETURN_TIMEOUT_MS
): Promise<void> {
	try {
		await page.waitForURL(RETURN_URL_PATTERN, { timeout });
	} catch {
		throw new Error(
			`Timed out after ${timeout}ms waiting to return from easyCredit payment. URL: ${page.url()}`
		);
	}
}

export const PAYMENT_SANDBOX = {
	phone: "1703404848",
	phoneE164: "+491703404848",
	country: "DE",
	tan: "000000",
	birthDate: "1972-04-05",
	iban: "DE12500105170648489890",
	email: "test@email.com",
	expressEmail: "ralf.ratenkauf@teambank.de",
	street: "Beuthener Str. 25",
	postalCode: "90471",
	city: "Nürnberg",
	employment: "ANGESTELLTER",
	netIncome: "1750",
} as const;

const API_HEADERS = {
	accept: "application/hal+json",
	"content-type": "application/json",
};

export type PaymentPageMode = "api" | "ui";

export function shouldUsePaymentApi(explicit?: boolean): boolean {
	if (explicit !== undefined) {
		return explicit;
	}
	const env = process.env.EASYCREDIT_PAYMENT_API?.toLowerCase();
	return env === "1" || env === "true" || env === "yes";
}

export function resolvePaymentPageMode(viaApi?: boolean): PaymentPageMode {
	return shouldUsePaymentApi(viaApi) ? "api" : "ui";
}

export function extractTechnicalTransactionId(url: string): string | null {
	const match = url.match(/\/app\/payment\/([^/]+)/);
	return match ? match[1] : null;
}

async function assertOk(
	response: { ok(): boolean; status(): number; text(): Promise<string> },
	label: string
) {
	if (!response.ok()) {
		throw new Error(
			`${label} failed (${response.status()}): ${await response.text()}`
		);
	}
}

function buildEntscheidungBody({
	vorgang,
	express,
}: {
	vorgang: Record<string, any>;
	express: boolean;
}) {
	const person = vorgang.person ?? {};
	const adresse = vorgang.adresse ?? {};
	const kontakt = vorgang.kontakt ?? {};

	return {
		person: {
			anrede: person.anrede ?? "FRAU",
			vorname: express ? randomize("Ralf") : person.vorname ?? "Ralf",
			nachname: person.nachname ?? "Ratenkauf",
			geburtsdatum: PAYMENT_SANDBOX.birthDate,
			beschaeftigung: PAYMENT_SANDBOX.employment,
			nettoeinkommen: PAYMENT_SANDBOX.netIncome,
		},
		adresse: {
			strasseHausNr: adresse.strasseHausNr ?? PAYMENT_SANDBOX.street,
			plz: adresse.plz ?? PAYMENT_SANDBOX.postalCode,
			ort: adresse.ort ?? PAYMENT_SANDBOX.city,
		},
		kontakt: {
			email: express
				? PAYMENT_SANDBOX.expressEmail
				: kontakt.email ?? PAYMENT_SANDBOX.email,
			mobilfunknummer: PAYMENT_SANDBOX.phoneE164,
			pruefungMobilfunknummerUebergehen: true,
		},
		bank: {
			iban: PAYMENT_SANDBOX.iban,
		},
		zustimmung: {
			sepamandat: true,
			angebote: false,
		},
	};
}

export async function goThroughPaymentPageViaApi({
	page,
	paymentType,
	express = false,
}: {
	page: any;
	paymentType: PaymentTypes;
	express?: boolean;
}) {
	await waitForPaymentRedirect(page);

	const technicalTransactionId = extractTechnicalTransactionId(page.url());
	if (!technicalTransactionId) {
		throw new Error(
			`Could not extract technicalTransactionId from ${page.url()}`
		);
	}

	const api = page.request;
	const phonePayload = {
		telefonnummer: PAYMENT_SANDBOX.phoneE164,
		land: PAYMENT_SANDBOX.country,
	};

	const vorgangResponse = await api.get(
		`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}`,
		{ headers: API_HEADERS }
	);
	await assertOk(vorgangResponse, "GET vorgang");
	const vorgang = await vorgangResponse.json();
	const fachlicheVorgangskennung = vorgang.fachlicheVorgangskennung as string;

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/betrugserkennung`,
			{
				headers: API_HEADERS,
				data: { bioCatchSessionId: randomUUID() },
			}
		),
		"POST betrugserkennung"
	);

	if (paymentType === PaymentTypes.INSTALLMENT) {
		const laufzeit = vorgang.finanzierung?.laufzeit ?? 27;
		await assertOk(
			await api.post(
				`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/laufzeit`,
				{
					headers: API_HEADERS,
					data: { laufzeit },
				}
			),
			"POST laufzeit"
		);
	}

	await assertOk(
		await api.post(`${PAYMENT_API_HOST}/api/payment/telefonnummer`, {
			headers: API_HEADERS,
			data: phonePayload,
		}),
		"POST telefonnummer"
	);

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/mtan`,
			{
				headers: API_HEADERS,
				data: phonePayload,
			}
		),
		"POST mtan"
	);

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/mtan/confirmation`,
			{
				headers: API_HEADERS,
				data: { mtan: PAYMENT_SANDBOX.tan },
			}
		),
		"POST mtan/confirmation"
	);

	await api.put(`${PAYMENT_API_HOST}/api/payment/abtest`, {
		headers: API_HEADERS,
		data: {
			type: "TEST_CONFIRMATION_PAGE",
			term: "C",
			vorgangskennung: technicalTransactionId,
		},
	});

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/adresse?vorgangskennung=${fachlicheVorgangskennung}`,
			{
				headers: API_HEADERS,
				data: {
					strasseHausNr: PAYMENT_SANDBOX.street,
					plz: PAYMENT_SANDBOX.postalCode,
					ort: PAYMENT_SANDBOX.city,
					land: PAYMENT_SANDBOX.country,
				},
			}
		),
		"POST adresse"
	);

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/bankdaten?vorgangskennung=${fachlicheVorgangskennung}`,
			{
				headers: API_HEADERS,
				data: { iban: PAYMENT_SANDBOX.iban },
			}
		),
		"POST bankdaten"
	);

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/entscheidung`,
			{
				headers: API_HEADERS,
				data: buildEntscheidungBody({ vorgang, express }),
			}
		),
		"POST entscheidung"
	);

	await assertOk(
		await api.post(
			`${PAYMENT_API_HOST}/api/payment/vorgang/${technicalTransactionId}/annahme`,
			{
				headers: API_HEADERS,
				data: {},
			}
		),
		"POST annahme"
	);

	const returnUrl = vorgang.ruecksprungAdressen?.erfolgUrl ?? "/easycredit/return";
	await page.goto(returnUrl);
	await waitForPaymentReturn(page);

	await expect(page).not.toHaveURL(PAYMENT_URL_PATTERN);
}
