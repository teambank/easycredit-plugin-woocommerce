const styleCardListing = () => {
	const card = document.querySelector(
		"easycredit-box-listing.easycredit-box-listing-adjusted",
	);

	if (!card || 
        !(card instanceof HTMLElement) || 
        !card.parentElement
    ) {
        return
    }

    const siblings = [...card.parentElement.children].filter(
        (c) => c !== card,
    );
    const siblingsCard = siblings[0];

    const cardWidth = siblingsCard.clientWidth;
    const cardHeight = siblingsCard.clientHeight;
    const cardClasses = siblingsCard.className;

    card.style.width = cardWidth + "px";
    card.style.height = cardHeight + "px";
    card.style.visibility = "hidden";
    card.className = card.className + " " + cardClasses;

    if (siblingsCard.tagName === "LI") {
        card.style.display = "list-item";
        card.style.listStyle = "none";

        if (card.parentElement.tagName === "UL") {
            card.parentElement.className =
                card.parentElement.className +
                " easycredit-card-columns-adjusted";
        }
    }
}


const styleCardListingHydrated = async () => {
   	await customElements.whenDefined("easycredit-box-listing");
	const card = document.querySelector(
		"easycredit-box-listing.easycredit-box-listing-adjusted",
	);

	if (!(card instanceof HTMLElement) || !card.shadowRoot) {
        return
    }

    card.style.visibility = ""

    const listing = card.shadowRoot.querySelector(
        ".ec-box-listing",
    )
    if (listing instanceof HTMLElement) {
        listing.style.maxWidth = "100%"
        listing.style.height = "100%"
    }
    
    const listingImage = card.shadowRoot.querySelector(
        ".ec-box-listing__image",
    )

    if (listingImage instanceof HTMLElement) {
        listingImage.style.minHeight = "100%";
    }
};

const positionCardInListing = () => {
	const card = document.querySelector("easycredit-box-listing");

	if (!(card instanceof HTMLElement) ||
        !card.parentElement
    ) {
        return
    }

    const siblings = [...card.parentElement.children].filter(
        (c) => c !== card,
    );

    const position = card.getAttribute("position");
    const previousPosition = position ? Number(position) - 1 : 0;
    const appendAfterPosition = previousPosition
        ? Number(position) - 2
        : 0;

    if (!position || previousPosition <= 0) {
        return
    }
    
    if (siblings[appendAfterPosition]) {
        siblings[appendAfterPosition].after(card);
    } else {
        card.parentElement.append(card);
    }
}

export const handleMarketingComponents = async () => {
	await styleCardListing();
	await styleCardListingHydrated();
	await positionCardInListing();
};
